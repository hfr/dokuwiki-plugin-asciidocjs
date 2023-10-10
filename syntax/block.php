<?php

/**
 * Plugin asciidocjs - Use asciidoc inside dokuwiki
 *
 * To be run with Dokuwiki only
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Rüdiger Kessel  <ruediger.kessel@gmail.com>
 */

// @codingStandardsIgnoreLine
include_once 'base.php';

class syntax_plugin_asciidocjs_block extends SyntaxPlugin_asciidocjs_base
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
        $this->Lexer->addEntryPattern('<asciidoc>', $mode, 'plugin_asciidocjs_block');
    }

    public function postConnect()
    {
          $this->Lexer->addExitPattern('</asciidoc>', 'plugin_asciidocjs_block');
    }
}
