<?php

/**
 * MgCacheRouting
 *
 * Routing Class
 *
 * @package     WordPress
 * @subpackage  MgCache
 * @version     1.0
 * @since       MgCache 1.0
 * @author      kchevalier@mindgruve.com
 */

defined('ABSPATH') or die();

class MgCacheRouting
{

    /**
     * Initialize MgCacheRouting class.
     *
     * @since MgCache 1.0
     *
     * @return null
     */
    public static function init()
    {
        self::registerFilters();
    }

    /**
     * Rewrite Rules Filter
     *
     * @since MgCache 1.0
     *
     * @param array $rules
     * @return array
     */
    public static function rewriteRulesFilter($rules)
    {

        if (MgCache::$active) {

            if (!class_exists('MgCache')) {
                include_once(__DIR__ . '/mg-cache.php');
            }
            MgCache::load();

            $cachePath = preg_replace(
                '/^(?:https?:)?\/\/' . $_SERVER['SERVER_NAME'] . '\//',
                '',
                MgCacheHelper::$cachePath
            );
            
            $rules[$cachePath . '/(\w+)\.css$'] = 'index.php?pagename=mg_asset_css&fingerprint=$matches[1]';
            $rules[$cachePath . '/(\w+)\.js$']  = 'index.php?pagename=mg_asset_js&fingerprint=$matches[1]';
        }

        return $rules;
    }

    /**
     * Register Filters
     *
     * @since MgCache 1.0
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
     * @since MgCache 1.0
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
