<?php
/**
 * DokuWiki Plugin No Highlight
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Kazutaka Miyasaka <kazmiya@gmail.com>
 */

// must be run within DokuWiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_nohighlight extends DokuWiki_Action_Plugin {
    /**
     * Returns some info
     */
    function getInfo() {
        return array(
            'author' => 'Kazutaka Miyasaka',
            'email'  => 'kazmiya@gmail.com',
            'date'   => '2010-01-21',
            'name'   => 'No Highlight Plugin',
            'desc'   => 'Disables search term highlighting',
            'url'    => 'http://www.dokuwiki.org/plugin:nohighlight'
        );
    }

    /**
     * Registers event handlers
     */
    function register(&$controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'disableHighlight', array());
    }

    /**
     * Disables search term highlighting
     */
    function disableHighlight(&$event, $param) {
        global $HIGH;
        $HIGH = '';
    }
}
