<?php

/*
  Plugin Name: MG Cache
  Plugin URI: http://mindgruve.com/
  Description: Manage assets and caching.
  Author: kchevalier@mindgruve.com
  Author URI: http://mindgruve.com/
  Version: 1.0
 */

/**
 * MgCache
 *
 * MG Cache Plugin Front Controller
 *
 * @package     WordPress
 * @subpackage  MgCache
 * @version     1.0
 * @since       MgCache 1.0
 * @author      kchevalier@mindgruve.com
 */

defined('ABSPATH') or die();

if (!class_exists('MgCache')) {

    class MgCache
    {

        /**
         * Kill switch.
         * @var bool
         */
        public static $active = true;

        /**
         * Initialize MgCache class.
         *
         * @since MgCache 1.0
         *
         * @return null
         */
        public static function init()
        {
            require('MgCacheRequirements.php');
            if (MgCacheRequirements::checkRequirements() && self::$active) {

                // actions
                add_action('init', array('MgCache', 'load'));
                add_action('admin_menu', array('MgCache', 'registerAdmin'));

                // filters
                add_filter('category_rewrite_rules', array('MgCacheRouting', 'rewriteRulesFilter'));
            }
        }

        /**
         * Bootstrap MgCache Plugin.
         *
         * @since MgCache 1.0
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
         * Register Helpers.
         *
         * @since MgCache 1.0
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
         * Register Controllers.
         *
         * @since MgCache 1.0
         *
         * @return null
         */
        public static function registerControllers()
        {
            include_once('controllers/MgAssetController.php');
        }

        /**
         * Register Routes.
         *
         * @since MgCache 1.0
         *
         * @return null
         */
        public static function registerRoutes()
        {
            include_once('MgCacheRouting.php');
            MgCacheRouting::init();
        }

        /**
         * Register Admin.
         *
         * @since MgCache 1.0
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
