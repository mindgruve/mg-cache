<?php
/**
 * MgCache
 *
 * @package MG Cache
 * @author kchevalier@mindgruve.com
 * @version 1.0
 */
/*
  Plugin Name: MG Cache
  Plugin URI: http://mindgruve.com/
  Description: Manage assets and cacheing.
  Author: kchevalier@mindgruve.com
  Version: 1.0
  Author URI: http://mindgruve.com/
 */

defined('ABSPATH') or die();

if (!class_exists('MgCache')) {

    class MgCache
    {

        /**
         *
         * PROPERTIES
         *
         */

        public static $active = true;


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
            require('MgCacheRequirements.php');
            if (MgCacheRequirements::checkRequirements()) {

                // activate/deactivate hooks
                register_activation_hook(__FILE__, array('MgCache', 'activate'));
                register_deactivation_hook(__FILE__, array('MgCache', 'deactivate'));
                add_action('mgCacheAdd', array('MgCacheHelper', 'add'), 10, 3);
                add_action('mgCacheSet', array('MgCacheHelper', 'set'), 10, 3);
                add_filter('mgCacheGet', array('MgCacheHelper', 'get'), 10, 2);
                add_filter('mgCacheExists', array('MgCacheHelper', 'exists'), 10, 2);
                add_action('mgCacheDelete', array('MgCacheHelper', 'delete'), 10, 2);
                add_action('mgCacheFlush', array('MgCacheHelper', 'flush'), 10, 0);
                add_action('mgCachePage', array('MgCacheHelper', 'cachePage'), 10, 3);
                add_action('save_post', array('MgCacheHelper', 'onSavePost'), 10, 3);
                add_action('wp_update_nav_menu', array('MgCacheHelper', 'onMenuUpdate'), 10, 0);

                // actions
                add_action('init', array('MgCache', 'load'));
                add_action('admin_menu', array('MgCache', 'registerAdmin'));

                // filters
                add_filter('category_rewrite_rules', array('MgCacheRouting', 'rewriteRulesFilter'));
            }
        }

        /**
         * Activate
         *
         * @return null
         */
        public static function activate()
        {

            // add rewrite rules
            include_once('MgCacheRouting.php');
//            MgCacheRouting::addRewriteRules(); // @deprecated since version 1.1

            // flush rewrite rules
            flush_rewrite_rules();
        }

        /**
         * Deactivate
         *
         * @return null
         */
        public static function deactivate()
        {
            MgCache::$active = false;
            flush_rewrite_rules();
        }

        /**
         * Load
         *
         * @return null
         */
        public static function load()
        {
            self::registerHelpers();
            self::registerControllers();
            self::registerRoutes();
        }

        /**
         * Register Helpers
         *
         * @return null
         */
        public static function registerHelpers()
        {
            include_once('helpers/MgCacheHelper.php');
            include_once('helpers/MgAssetHelper.php');
            MgAssetHelper::init();
            MgCacheHelper::init();
        }

        /**
         * Register Controllers
         *
         * @return null
         */
        public static function registerControllers()
        {
            include_once('controllers/MgAssetController.php');
        }

        /**
         * Register Routes
         *
         * @return null
         */
        public static function registerRoutes()
        {
            include_once('MgCacheRouting.php');
            MgCacheRouting::init();
        }

        /**
         * Register Admin
         *
         * @return null
         */
        public static function registerAdmin()
        {
            include_once('admin/MgCacheAdmin.php');
            add_submenu_page(
                "options-general.php",
                "Mg Cache",
                "Cache",
                "update_themes",
                basename(__FILE__),
                array('MgCacheAdmin', 'printAdminPage')
            );
        }
    }

    MgCache::init();
}