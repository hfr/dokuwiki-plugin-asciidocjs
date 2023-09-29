<?php

/**
 * Plugin asciidocjs - Use asciidoc inside dokuwiki
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Rüdiger Kessel  <ruediger.kessel@gmail.com>
 */

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;

// phpcs:disable
if (!defined('DOKU_INC')) {
    die();
}
// phpcs:enable

class action_plugin_asciidocjs extends ActionPlugin
{
    public function register(EventHandler $controller)
    {
        $controller->register_hook(
            'TPL_METAHEADER_OUTPUT',
            'BEFORE',
            $this,
            'loadasciidocjs'
        );
    }

    public function loadasciidocjs(Event $event, $param)
    {
        $event->data['script'][] = ['charset' => 'utf-8', 'defer' => "defer", 'src' => DOKU_BASE .
          "lib/plugins/asciidocjs/node_modules/@asciidoctor/core/dist/browser/asciidoctor.js"];

        if ($this->getConf('use_kroki')) {
            $event->data['script'][] = ['charset' => 'utf-8', 'defer' => "defer", 'src' => DOKU_BASE .
              "lib/plugins/asciidocjs/node_modules/asciidoctor-kroki/dist/browser/asciidoctor-kroki.js"];
        }
        
        if ($this->getConf('use_css')) {
            $event->data['link'][] = ['rel'  => 'stylesheet', 'type' => 'text/css', 'href' => DOKU_BASE .
               "lib/plugins/asciidocjs/node_modules/@asciidoctor/core/dist/css/asciidoctor.css"];
        }
    }
}
