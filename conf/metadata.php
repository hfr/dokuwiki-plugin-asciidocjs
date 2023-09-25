<?php

/**
 * Options for the asciidocjs plugin
 *
 * @author Rüdiger Kessel <ruediger.kessel@gmail.com>
 */

$meta['save_mode'] = array('multichoice','_choices' => array('unsave','save','server','secure'));
$meta['adoc2html'] = array('multichoice','_choices' => array('browser','server'));
$meta['exec_node'] = array('string');
