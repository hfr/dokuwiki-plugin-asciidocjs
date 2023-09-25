<?php
/**
 * Plugin asciidocjs - Use asciidoc inside dokuwiki
 * 
 * To be run with Dokuwiki only
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Rüdiger Kessel  <ruediger.kessel@gmail.com>
 */
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
     
    /**
     * All DokuWiki plugins to extend the parser/rendering mechanism
     * need to inherit from this class
     */
    class syntax_plugin_asciidocjs extends DokuWiki_Syntax_Plugin {
     
     
       public $scriptid = 0;
       /**
        * Get the type of syntax this plugin defines.
        *
        * @param none
        * @return String <tt>'substition'</tt> (i.e. 'substitution').
        * @public
        * @static
        */
        function getType(){
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
        function getSort(){
            return 1;
        }
     
     
       /**
        * Connect lookup pattern to lexer.
        *
        * @param $aMode String The desired rendermode.
        * @return none
        * @public
        * @see render()
        */
        function connectTo($mode) {
          $this->Lexer->addEntryPattern('<asciidoc>',$mode,'plugin_asciidocjs');
          $this->Lexer->addEntryPattern('//--asciidoc--//',$mode,'plugin_asciidocjs');
        }
     
       function postConnect() {
          $this->Lexer->addExitPattern('</asciidoc>','plugin_asciidocjs');
        }
     
   
       /**
        * Handler to prepare matched data for the rendering process.
        *
        * <p>
        * The <tt>$aState</tt> parameter gives the type of pattern
        * which triggered the call to this method:
        * </p>
        * @param $aAscdoc String The asciidoc text to convert.
        */
       function run_asciidoctor($node,$ascdoc,$save_mode) {
           if ($node==''){
               return '<!-- ascii-doc no node command -->';
           }
           $html = '';
           $return_value = 1;       
           $descriptorspec = array(
               0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
               1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
               2 => array("pipe", "w")   // stderr
           );
           $cwd = DOKU_PLUGIN.'asciidocjs';
           $env = array();
           $CMD=$node." asciidoc.js ".$save_mode;
           $process = proc_open($CMD, $descriptorspec, $pipes, $cwd, $env);
           if (is_resource($process)) {
               // $pipes now looks like this:
               // 0 => writeable handle connected to child stdin
               // 1 => readable handle connected to child stdout
               // Any error output will be appended to /tmp/error-output.txt

               fwrite($pipes[0],$ascdoc);
               fclose($pipes[0]);

               $html=stream_get_contents($pipes[1]);
               fclose($pipes[1]);

               $error=stream_get_contents($pipes[2]);
               fclose($pipes[2]);

               // It is important that you close any pipes before calling
               // proc_close in order to avoid a deadlock
               $return_value = proc_close($process);
           }           
           if ($return_value==0) {
               return $html;
           } else {
               return "<!-- ascii-doc error $return_value: $error -->";
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
        function handle($match, $state, $pos, Doku_Handler $handler){
            switch ($state) {
              case DOKU_LEXER_ENTER : 
                $data ='';
                if ($this->scriptid==0){
                  $data .= '<script type="module" src="'.DOKU_BASE.'lib/plugins/asciidocjs/asciidoc.js'.'" defer></script>'.PHP_EOL;
                  $data .= '<script type="text/javascript">'.PHP_EOL;
                  $data .= 'save_mode="'.$this->getConf('save_mode').'";</script>'.PHP_EOL;
                }
                return array($state, $data, '');
              case DOKU_LEXER_MATCHED :
                break;
              case DOKU_LEXER_UNMATCHED :
                $data ='';
                if ($this->getConf('save_mode')=='server'){
                  $data.='<!-- ascii-doc start -->';
                  $data.=$this->run_asciidoctor($this->getConf('exec_node'),$match,$this->getConf('save_mode'));
                  $data.='<!-- ascii-doc end -->';
                } else {   
                  $SID="asciidoc_c".strval($this->scriptid);
                  $DID="asciidoc_t".strval($this->scriptid);
                  $this->scriptid+=1;
                  $data.='<div id="'.$DID.'"></div>'.PHP_EOL;
                  $data.='<script type="text/javascript">if (typeof asciidocs === "undefined") asciidocs=[];'.PHP_EOL;
                  $data.='asciidocs.push({"SID":"'.$SID.'","DID":"'.$DID.'"});</script>'.PHP_EOL;
                  $data.='<script id="'.$SID.'" type="text/json">';
                  $data.='{"text":'.json_encode($match).'}';
                  $data.='</script>';
                }
                return array($state, $data, $match); 
              case DOKU_LEXER_EXIT :
                return array($state, '', '');
              case DOKU_LEXER_SPECIAL :
                break;
            }
            return array();
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
        function render($mode, Doku_Renderer $renderer, $data) {
            if($mode == 'xhtml'){
                if(is_a($renderer,'renderer_plugin_dw2pdf')){
                  // this is the PDF export, render simple HTML here
                  $renderer->doc .= $this->run_asciidoctor($this->getConf('exec_node'),$data[2],$this->getConf('save_mode'));  
                }else{
                  // this is normal XHTML for Browsers, be fancy here
                    $renderer->doc .= $data[1];
                }
                return true;
            }
            return false;
        }
    }

?>

