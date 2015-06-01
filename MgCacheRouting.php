<?php
/**
 * MgCacheRouting
 *
 * Routing Class
 *
 * @package MG Cache
 * @author kchevalier@mindgruve.com
 * @version 1.1
 */

defined('ABSPATH') or die();

class MgCacheRouting
{

    /**
     *
     * METHODS
     *
     */

    /**
     * Init
     *
     * @return null
     */
    public static function init()
    {
        self::registerFilters();
    }

    /**
     * Add Rewrite Rules
     *
     * @deprecated since version 1.1, use MgCacheRouting::rewriteRulesFilter() instead
     * @return null
     */
    public static function addRewriteRules()
    {

        // MgAssetController::assetStylesheetAction()
        add_rewrite_rule('wp/wp-content/plugins/mg-cache/cache/(\w+)\.css$', 'index.php?pagename=mg_asset_css&fingerprint=$matches[1]', 'top');

        // MgAssetController::assetScriptAction()
        add_rewrite_rule('wp/wp-content/plugins/mg-cache/cache/(\w+)\.js$', 'index.php?pagename=mg_asset_js&fingerprint=$matches[1]', 'top');
    }

    /**
     * Rewrite Rules Filter
     *
     * @param array $rules
     * @return array
     */
    public function rewriteRulesFilter($rules)
    {

        if (MgCache::$active) {

            // MgAssetController::assetStylesheetAction()
            $rules['wp/wp-content/plugins/mg-cache/cache/(\w+)\.css$'] = 'index.php?pagename=mg_asset_css&fingerprint=$matches[1]';

            // MgAssetController::assetScriptAction()
            $rules['wp/wp-content/plugins/mg-cache/cache/(\w+)\.js$'] = 'index.php?pagename=mg_asset_js&fingerprint=$matches[1]';
        }

        return $rules;
    }

    /**
     * Register Filters
     *
     * @return null
     */
    public static function registerFilters()
    {

        // query variables filter
        add_filter('query_vars', array('MgCacheRouting', 'insertQueryVars'));

        // add controllers
        add_filter('template_redirect', array('MgAssetController', 'assetStylesheetAction'));
        add_filter('template_redirect', array('MgAssetController', 'assetScriptAction'));
    }

    /**
     * Insert Query Vars
     *
     *  Adding the id var so that WP recognizes it
     *
     * @param array $vars
     * @return array
     */
    public static function insertQueryVars($vars)
    {
        array_push($vars, 'files');
        array_push($vars, 'fingerprint');
        return $vars;
    }
}
