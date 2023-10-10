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
    public function runAsciidoctor($node, $ascdoc, $extensions, $params)
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
        $CMD = $node . " asciidoc.js '" .  json_encode($extensions) . "' '" . json_encode($params) . "'";
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
    public function getExtensions()
    {
        return array("kroki" => $this->getConf('use_kroki'));
    }
    public function getParams()
    {
        $params = array("safe" => $this->getConf('save_mode'),
                        "header_footer" => false,
                        "attributes" => array(
                            "DOKUWIKI_BASE" => DOKU_BASE,
                            "DOKUWIKI_URL" => DOKU_URL
                            )
                       );
        if ($this->getExtensions()["kroki"]) {
            if ($this->getConf('kroki_server') != '') {
                $params["attributes"]["kroki-server-url"] = $this->getConf('kroki_server');
            }
        }
        return $params;
    }
    public function getAscdoc2html()
    {
        $data = '';
        $data .= '<script type="module">'. PHP_EOL;
        $data .= 'var extensions=' . json_encode($this->getExtensions()) . ';' . PHP_EOL;
        $data .= 'var params = '.json_encode($this->getParams()).';' . PHP_EOL;
        $data .= file_get_contents(DOKU_PLUGIN.'asciidocjs/asciidocinc.js'); 
        $data .= '</script>' . PHP_EOL;
        return $data;
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
                        $data .= $this->getAscdoc2html();
                    }
                }
                return [$state, $data, ''];
            case DOKU_LEXER_MATCHED:
                break;
            case DOKU_LEXER_UNMATCHED:
                $data = '';
                $data .= '<!-- ascii-doc start -->' . PHP_EOL;
                if ($this->getConf('adoc2html') == 'server') {
                    $extensions = $this->getExtensions();
                    $params = $this->getParams();
                    $data .= $this->runAsciidoctor($this->getConf('exec_node'), $match, $extensions, $params);
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
                $extensions = $this->getExtensions();
                $params = $this->getParams();
                $renderer->doc .=
                  $this->runAsciidoctor($this->getConf('exec_node'), $data[2], $extensions, $params);
            } else {
              // this is normal XHTML for Browsers, be fancy here
                $renderer->doc .= $data[1];
            }
            return true;
        }
        return false;
    }
}


