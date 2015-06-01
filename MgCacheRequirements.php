<?php
/**
 * MgCacheRequirements
 *
 * Technical requirements for the MgCache plugin
 *
 * @package MG Cache
 * @author kchevalier@mindgruve.com
 * @version 1.0
 */

defined('ABSPATH') or die();

class MgCacheRequirements
{

    /**
     *
     * PROPERTIES
     *
     */

    private static $minimumPhpVersion = '5.3';

    private static $classes = array(
        'MgCacheRouting',
        'MgCacheHelper',
        'MgAssetHelper',
        'MgAssetController',
        'MgCacheAdmin',
    );

    private static $classDepenencies = array();

    private static $functionDepenencies = array(
        'add_filter'                 => 0.71,
        'update_option'              => 1.0,
        'add_action'                 => 1.2,
        'get_query_var'              => 1.5,
        'is_admin'                   => 1.5,
        'get_option'                 => 1.5,
        'register_activation_hook'   => 2.0,
        'register_deactivation_hook' => 2.0,
        'wp_register_style'          => 2.1,
        'wp_enqueue_style'           => 2.1,
        'add_rewrite_rule'           => 2.1,
        'wp_register_script'         => 2.6,
        'wp_enqueue_script'          => 2.6,
        'plugin_dir_url'             => 2.8,
        'flush_rewrite_rules'        => 3.0,
    );

    private static $actionDependencies = array(
        'init'                       => 2.1,
    );


    /**
     *
     * METHODS
     *
     */

    /**
     * Check Requirements
     *
     * @return boolean
     */
    public static function checkRequirements()
    {
        if (function_exists('add_action')) {

            // check minimum PHP requirements
            if (version_compare(phpversion(), self::$minimumPhpVersion) < 0) {
                add_action('admin_notices', array('MgCacheRequirements', 'adminErrorNoticePhp'));
                return false;
            }

            // check class name conflicts
            if (count(self::$classes)) {
                foreach (self::$classes as $class) {
                    if (class_exists($class)) {
                        add_action('admin_notices', array('MgCacheRequirements', 'adminErrorNoticeClassConflict'));
                        return false;
                    }
                }
            }

            // check class dependencies
            if (count(self::$classDepenencies)) {
                foreach (self::$classDepenencies as $class => $version) {
                    if (!class_exists($class)) {
                        add_action('admin_notices', array('MgCacheRequirements', 'adminErrorNoticeUpgradeWordpress'));
                        return false;
                    }
                }
            }

            // check function dependencies
            if (count(self::$functionDepenencies)) {
                foreach (self::$functionDepenencies as $function => $version) {
                    if (!function_exists($function)) {
                        add_action('admin_notices', array('MgCacheRequirements', 'adminErrorNoticeUpgradeWordpress'));
                        return false;
                    }
                }
            }

            // check action dependencies
            if (function_exists('has_action') && count(self::$actionDependencies)) {
                foreach (self::$actionDependencies as $action => $version) {
                    if (!has_action($action)) {
                        add_action('admin_notices', array('MgCacheRequirements', 'adminErrorNoticeUpgradeWordpress'));
                        return false;
                    }
                }
            }
        } else {
            return false;
        }

        return true;
    }

    /* ERROR MESSAGES */

    /**
     * Admin Error Notice PHP
     *
     * @return null
     */
    public static function adminErrorNoticePhp()
    {
        echo "<div class='error'><p>" . __("The '" . MgCache::$pluginName . "' plugin requires at least version "
            . self::$minimumPhpVersion . " of PHP. Your version is " . phpversion() . ". Please update PHP and try again.")
            . "</p></div>\n";
    }

    /**
     * Admin Error Notice Class Conflict
     *
     * @return null
     */
    public static function adminErrorNoticeClassConflict()
    {
        echo "<div class='error'><p>" . __("The '" . MgCache::$pluginName . "' plugin has found a naming conflict. "
            . "Try disabling other plugins to see if the conflict resolves.")
            . "</p></div>\n";
    }

    /**
     * Admin Error Notice Upgrade Wordpress
     *
     * @return null
     */
    public static function adminErrorNoticeUpgradeWordpress()
    {
        $versions = array_merge(
            self::$classes,
            self::$classDepenencies,
            self::$functionDepenencies,
            self::$actionDependencies
        );
        arsort($versions);
        echo "<div class='error'><p>" . __("The '" . MgCache::$pluginName . "' plugin requires at least version "
            . sprintf("%01.1f", reset($versions)) . " of Wordpress. Your version is " . get_bloginfo('version') . ". "
            . "Please update WordPress and try again")
            . "</p></div>\n";
    }
}
