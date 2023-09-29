<?php

/**
 * Plugin asciidocjs - Use asciidoc inside dokuwiki
 *
 * To be run with Dokuwiki only
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Rüdiger Kessel  <ruediger.kessel@gmail.com>
 */

use dokuwiki\Extension\SyntaxPlugin;

// phpcs:disable
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
// phpcs:enable

    /**
     * All DokuWiki plugins to extend the parser/rendering mechanism
     * need to inherit from this class
     */
class SyntaxPlugin_asciidocjs_base extends SyntaxPlugin
{
    public $scriptid = 0;
   /**
    * Get the type of syntax this plugin defines.
    *
    * @param none
    * @return String <tt>'substition'</tt> (i.e. 'substitution').
    * @public
    * @static
    */
    public function getType()
    {
        return 'protected';
    }

   /**
    * Where to sort in?
    *
    * @param none
    * @return Integer <tt>6</tt>.
    * @public
    * @static
    */
    public function getSort()
    {
        return 1;
    }

   /**
    * runAsciidoctor runs node with Asciidoctor to produce html5
    *
    * <p>
    * The <tt>$aAscdoc</tt> parameter gives the asciidoc source text
    * </p>
    * @param $aNode String Path to the node.js exe.
    * @param $aAscdoc String The asciidoc text to convert.
    * @param $aSave_mode String AsciiDoctor save mode for the conversion.
    */
    public function runAsciidoctor($node, $ascdoc, $save_mode)
    {
        if ($node == '') {
            return '<!-- ascii-doc no node command -->' . PHP_EOL;
        }
        $html = '';
        $return_value = 1;
        $descriptorspec = [
            0 => ["pipe", "r"],
            // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],
            // stdout is a pipe that the child will write to
            2 => ["pipe", "w"],
        ];
        $cwd = DOKU_PLUGIN . 'asciidocjs';
        $env = [];
        $CMD = $node . " asciidoc.js " . $save_mode;
        $process = proc_open($CMD, $descriptorspec, $pipes, $cwd, $env);
        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // Any error output will be appended to /tmp/error-output.txt

            fwrite($pipes[0], $ascdoc);
            fclose($pipes[0]);

            $html = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);
        }
        if ($return_value == 0) {
            return $html . PHP_EOL;
        } else {
            return "<!-- ascii-doc error $return_value: $error -->" . PHP_EOL;
        }
    }
       /**
        * Handler to prepare matched data for the rendering process.
        *
        * <p>
        * The <tt>$aState</tt> parameter gives the type of pattern
        * which triggered the call to this method:
        * </p>
        * <dl>
        * <dt>DOKU_LEXER_ENTER</dt>
        * <dd>a pattern set by <tt>addEntryPattern()</tt></dd>
        * <dt>DOKU_LEXER_MATCHED</dt>
        * <dd>a pattern set by <tt>addPattern()</tt></dd>
        * <dt>DOKU_LEXER_EXIT</dt>
        * <dd> a pattern set by <tt>addExitPattern()</tt></dd>
        * <dt>DOKU_LEXER_SPECIAL</dt>
        * <dd>a pattern set by <tt>addSpecialPattern()</tt></dd>
        * <dt>DOKU_LEXER_UNMATCHED</dt>
        * <dd>ordinary text encountered within the plugin's syntax mode
        * which doesn't match any pattern.</dd>
        * </dl>
        * @param $aMatch String The text matched by the patterns.
        * @param $aState Integer The lexer state for the match.
        * @param $aPos Integer The character position of the matched text.
        * @param $aHandler Object Reference to the Doku_Handler object.
        * @return Integer The current lexer state for the match.
        * @public
        * @see render()
        * @static
        */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $data = '';
                if ($this->getConf('adoc2html') != 'server') {
                    if ($this->scriptid == 0) {
                        $data .= '<script type="module">';
                        $data .= 'var save_mode="' . $this->getConf('save_mode') . '";' . PHP_EOL;
                        $data .= 'jQuery( function() {' . PHP_EOL;
                        $data .= 'var asciidoctor = Asciidoctor();' . PHP_EOL;
                        $data .= 'const registry = asciidoctor.Extensions.create();' . PHP_EOL;
                        $data .= 'AsciidoctorKroki.register(registry);' . PHP_EOL;
                        $data .= 'for (let i = 0; i < asciidocs.length; i++) {' . PHP_EOL;
                        $data .= 'var json = document.getElementById(asciidocs[i]["SID"]).textContent;' . PHP_EOL;
                        $data .= 'var target = document.getElementById(asciidocs[i]["DID"]);' . PHP_EOL;
                        $data .= 'var doc = JSON.parse(json);' . PHP_EOL;
                        $data .= 'var html = asciidoctor.convert(doc.text, ' . PHP_EOL;
                        $data .= '  {safe: save_mode, header_footer: false, extension_registry: registry});' . PHP_EOL;
                        $data .= 'target.innerHTML = html;}});' . PHP_EOL;
                        $data .= '</script>' . PHP_EOL;
                    }
                }
                return [$state, $data, ''];
            case DOKU_LEXER_MATCHED:
                break;
            case DOKU_LEXER_UNMATCHED:
                $data = '';
                $data .= '<!-- ascii-doc start -->' . PHP_EOL;
                if ($this->getConf('adoc2html') == 'server') {
                    $data .= $this->runAsciidoctor($this->getConf('exec_node'), $match, $this->getConf('save_mode'));
                } else {
                    $SID = "asciidoc_c" . $this->scriptid;
                    $DID = "asciidoc_t" . $this->scriptid;
                    ++$this->scriptid;
                    $data .= '<div id="' . $DID . '"></div>' . PHP_EOL;
                    $data .= '<script type="text/javascript">';
                    $data .= 'if (typeof asciidocs === "undefined") asciidocs=[];' . PHP_EOL;
                    $data .= 'asciidocs.push({"SID":"' . $SID . '","DID":"' . $DID . '"});</script>' . PHP_EOL;
                    $data .= '<script id="' . $SID . '" type="text/json">';
                    $data .= '{"text":' . json_encode($match) . '}';
                    $data .= '</script>' . PHP_EOL;
                }
                $data .= '<!-- ascii-doc end -->' . PHP_EOL;
                return [$state, $data, $match];
            case DOKU_LEXER_EXIT:
                return [$state, '', ''];
            case DOKU_LEXER_SPECIAL:
                break;
        }
        return [];
    }

       /**
        * Handle the actual output creation.
        *
        * <p>
        * The method checks for the given <tt>$aFormat</tt> and returns
        * <tt>FALSE</tt> when a format isn't supported. <tt>$aRenderer</tt>
        * contains a reference to the renderer object which is currently
        * handling the rendering. The contents of <tt>$aData</tt> is the
        * return value of the <tt>handle()</tt> method.
        * </p>
        * @param $aFormat String The output format to generate.
        * @param $aRenderer Object A reference to the renderer object.
        * @param $aData Array The data created by the <tt>handle()</tt>
        * method.
        * @return Boolean <tt>TRUE</tt> if rendered successfully, or
        * <tt>FALSE</tt> otherwise.
        * @public
        * @see handle()
        */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode == 'xhtml') {
            if (is_a($renderer, 'renderer_plugin_dw2pdf')) {
              // this is the PDF export, render simple HTML here
                $renderer->doc .=
                  $this->runAsciidoctor(
                      $this->getConf('exec_node'),
                      $data[2],
                      $this->getConf('save_mode')
                  );
            } else {
              // this is normal XHTML for Browsers, be fancy here
                $renderer->doc .= $data[1];
            }
            return true;
        }
        return false;
    }
}

class syntax_plugin_asciidocjs extends SyntaxPlugin_asciidocjs_base
{
   /**
    * Connect lookup pattern to lexer.
    *
    * @param $aMode String The desired rendermode.
    * @return none
    * @public
    * @see render()
    */
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('^//#--asciidoc--#//', $mode, 'plugin_asciidocjs');
    }

    public function postConnect()
    {
          $this->Lexer->addExitPattern('^\b$', 'plugin_asciidocjs');
    }
}

class syntax_plugin_asciidocjsblk extends SyntaxPlugin_asciidocjs_base
{
   /**
    * Connect lookup pattern to lexer.
    *
    * @param $aMode String The desired rendermode.
    * @return none
    * @public
    * @see render()
    */
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<asciidoc>', $mode, 'plugin_asciidocjsblk');
    }

    public function postConnect()
    {
          $this->Lexer->addExitPattern('</asciidoc>', 'plugin_asciidocjsblk');
    }
}
