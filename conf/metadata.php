<?php

/**
 * Options for the asciidocjs plugin
 *
 * @author Rüdiger Kessel <ruediger.kessel@gmail.com>
 */

$meta['use_css'] = array('onoff');
$meta['safe_mode'] = array('multichoice','_choices' => array('unsafe','safe','server','secure'));
$meta['adoc2html'] = array('multichoice','_choices' => array('browser','server'));
$meta['exec_node'] = array('string');
$meta['use_kroki'] = array('onoff');
$meta['kroki_server'] = array('string');
