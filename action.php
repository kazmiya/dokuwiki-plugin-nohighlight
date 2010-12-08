<?php
/**
 * NoHighlight Plugin for DokuWiki / action.php
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Kazutaka Miyasaka <kazmiya@gmail.com>
 */

// must be run within DokuWiki
if (!defined('DOKU_INC')) {
    die();
}

if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}

require_once DOKU_PLUGIN . 'action.php';

class action_plugin_nohighlight extends DokuWiki_Action_Plugin
{
    /**
     * Stores original array of highlight candidates
     */
    var $highlight_orig = array();

    /**
     * Configurations (may be overridden)
     */
    var $disable_highlight = 'all';
    var $remove_url_params = 1;

    /**
     * Registers event handlers
     */
    function register(&$controller)
    {
        $controller->register_hook(
            'DOKUWIKI_STARTED', 'BEFORE',
            $this, 'disableHighlight', array()
        );

        $controller->register_hook(
            'DOKUWIKI_STARTED', 'AFTER',
            $this, 'removeUrlParams', array('do' => 'get')
        );

        $controller->register_hook(
            'SEARCH_QUERY_FULLPAGE', 'AFTER',
            $this, 'removeUrlParams', array('do' => 'modify')
        );

        $controller->register_hook(
            'FULLTEXT_SNIPPET_CREATE', 'BEFORE',
            $this, 'removeUrlParams', array('do' => 'restore')
        );
    }

    /**
     * Disables search term highlighting
     */
    function disableHighlight(&$event, $param)
    {
        global $HIGH;
        global $ACT;

        if ($ACT !== 'show' && $ACT !== 'search') {
            return;
        }

        $this->setConfig();

        switch ($this->disable_highlight) {
            case 'none':
                return;

            // disable all highlighting features
            case 'all':
                $HIGH = '';
                break;

            // disable highlighting by URL param ?s[]=term
            case 'query':
                if (!empty($_REQUEST['s'])) {
                    $HIGH = '';
                }
                break;

            // disable auto-highlight via search engines
            case 'auto':
                $HIGH = isset($_REQUEST['s']) ? (string) $_REQUEST['s'] : '';
                break;
        }
    }

    /**
     * Sets per-user or site-wide configurations
     */
    function setConfig()
    {
        @session_start();

        if (isset($_REQUEST['nohighlight'])) {
            if ($_REQUEST['nohighlight']) {
                $_SESSION[DOKU_COOKIE]['nohighlight'] = 1;
            } else {
                unset($_SESSION[DOKU_COOKIE]['nohighlight']);
            }
        }

        if ($_SESSION[DOKU_COOKIE]['nohighlight']) {
            // set per-user config (disable all features only)
            $this->disable_highlight = 'all';
            $this->remove_url_params = 1;
        } else {
            // set site-default config
            $this->disable_highlight = $this->getConf('disable_highlight');
            $this->remove_url_params = $this->getConf('remove_url_params');
        }
    }

    /**
     * Manipulates highlight terms to remove ?s[]=term from search result URLs
     */
    function removeUrlParams(&$event, $param)
    {
        if (!$this->remove_url_params) {
            return;
        }

        switch ($param['do']) {
            case 'get':
                $this->getCandidatesFromReferer();
                break;

            case 'modify':
                $this->modifyCandidates($event->data['highlight']);
                break;

            case 'restore':
                $this->restoreCandidates($event->data['highlight']);
                break;
        }
    }

    /**
     * Gets highlight candidates from HTTP_REFERER info
     * (A compensation for "remove_url_params" option)
     */
    function getCandidatesFromReferer()
    {
        global $HIGH;
        global $ACT;

        if (
            $ACT !== 'show' ||
            !empty($HIGH) ||
            !isset($_SERVER['HTTP_REFERER']) ||
            in_array($this->disable_highlight, array('all', 'auto'))
        ) {
            return;
        }

        $referer = (string) $_SERVER['HTTP_REFERER'];

        if (
            strpos($referer, DOKU_URL) === 0 &&
            preg_match('/[?&]do=search&id=([^&]+)/', $referer, $matches)
        ) {
            // users seem to have jumped from search result link in this wiki
            require_once DOKU_INC . 'inc/fulltext.php';

            // set highlight candidates
            $parsed_query = ft_queryParser(urldecode($matches[1]));
            $HIGH = $parsed_query['highlight'];
        }
    }

    /**
     * Modifies highlight candidates
     */
    function modifyCandidates(&$highlight)
    {
        $functions = array();

        foreach (debug_backtrace() as $trace) {
            $functions[] = $trace['function'];
        }

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
    function restoreCandidates(&$highlight)
    {
        // snippet creation with no highlight term causes heavy load
        if (!empty($this->highlight_orig)) {
            $highlight = $this->highlight_orig;
        }
    }
}
