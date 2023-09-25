<?php
/**
 * Plugin asciidocjs - Use asciidoc inside dokuwiki
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Rüdiger Kessel  <ruediger.kessel@gmail.com>
 */

if (!defined('DOKU_INC')) {
    die();
}

class action_plugin_asciidocjs extends DokuWiki_Action_Plugin
{

 public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this,
                                   '_loadasciidocjs');
    }

    public function _loadasciidocjs(Doku_Event $event, $param) {
        $event->data['link'][] = array (
                            'rel'     => 'stylesheet',
                            'type'    => 'text/css',
                            'href'    => DOKU_BASE."lib/plugins/asciidocjs/node_modules/@asciidoctor/core/dist/css/asciidoctor.css",
                    );

    }

}

