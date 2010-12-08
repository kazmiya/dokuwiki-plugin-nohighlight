<?php
/**
 * Configuration metadata for nohighlight plugin
 * 
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Kazutaka Miyasaka <kazmiya@affrc.go.jp>
 */

$meta['disable_highlight'] = array(
    'multichoice',
    '_choices' => array('all', 'query', 'auto', 'none')
);

$meta['remove_url_params'] = array('onoff');
