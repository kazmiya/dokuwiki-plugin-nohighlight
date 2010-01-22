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
     * Stores original array of highlight candidates
     */
    var $highlight_orig = array();

    /**
     * Registers event handlers
     */
    function register(&$controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'disableHighlight', array());
        $controller->register_hook('SEARCH_QUERY_FULLPAGE', 'AFTER', $this, 'removeUrlParams', array('do' => 'modify'));
        $controller->register_hook('FULLTEXT_SNIPPET_CREATE', 'BEFORE', $this, 'removeUrlParams', array('do' => 'restore'));
    }

    /**
     * Disables search term highlighting
     */
    function disableHighlight(&$event, $param) {
        global $HIGH;

        switch ($this->getConf('disable_highlight')) {
            case 'none':
                return;
            case 'all':   // disable all highlighting features
                $HIGH = '';
                break;
            case 'query': // disable highlighting by URL param ?s[]=term
                if (!empty($_REQUEST['s'])) $HIGH = '';
                break;
            case 'auto':  // disable auto-highlight via search engines
                $HIGH = $_REQUEST['s'];
                break;
        }
    }

    /**
     * Manipulates highlight candidates to remove ?s[]=term from search result URLs
     */
    function removeUrlParams(&$event, $param) {
        if (!$this->getConf('remove_url_params')) return;

        switch ($param['do']) {
            case 'modify':
                $this->modifyCandidates($event->data['highlight']);
                break;
            case 'restore':
                $this->restoreCandidates($event->data['highlight']);
                break;
        }
    }

    /**
     * Modifies highlight candidates
     */
    function modifyCandidates(&$highlight) {
        $functions = array();
        $traces = debug_backtrace();
        foreach ($traces as $trace) $functions[] = $trace['function'];

        // hack if called via html_search()
        if (in_array('html_search', $functions)) {
            $this->highlight_orig = $highlight;
            $highlight = array();
        } else {
            $this->highlight_orig = array();
        }
    }

    /**
     * Restores highlight candidates
     */
    function restoreCandidates(&$highlight) {
        // snippet creation with no highlight term causes heavy load
        if (!empty($this->highlight_orig)) {
            $highlight = $this->highlight_orig;
        }
    }
}
