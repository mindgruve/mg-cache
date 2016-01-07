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
  Version: 0.9.2
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

                // actions
                add_action('init', array('MgCache', 'load'));
                add_action('admin_menu', array('MgCache', 'registerAdmin'));

                // filters
                add_filter('category_rewrite_rules', array('MgCacheRouting', 'rewriteRulesFilter'));
            }
        }
        

        /**
         * Load
         *
         * @return null
         */
        public static function load()
        {
            self::registerFactories();
            self::registerHelpers();
            self::registerControllers();
            self::registerRoutes();
        }

        public static function registerFactories()
        {
            include_once('factories/CacheProviderFactory.php');
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
