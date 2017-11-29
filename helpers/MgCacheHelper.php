<?php

/**
 * MgCacheHelper
 *
 * Cache Helper Class
 *
 * @package     WordPress
 * @subpackage  MgCache
 * @version     1.0
 * @since       MgCache 1.0
 * @author      kchevalier@mindgruve.com
 */

defined('ABSPATH') or die();

class MgCacheHelper
{

    /**
     * Label for options saved in WordPress
     * @var string
     */
    public static $adminOptionsName = 'mgCacheValues';

    /**
     * Cache directory
     * @var string
     */
    public static $cacheDir = null;

    /**
     * Cache path
     * @var string
     */
    public static $cachePath = null;

    /**
     * Web root directory
     * @var string
     */
    public static $webRoot = null;

    /**
     * Initialize MgCacheHelper class.
     *
     * @since MgCache 1.0
     *
     * @return null
     */
    public static function init()
    {
        self::initLocations();
        self::checkLocations();
    }

    /**
     * Initialize file and path locations
     *
     * @since MgCache 1.0
     *
     * @return null
     */
    public static function initLocations()
    {

        // init cache directory location
        if (defined('MG_CACHE_DIR')) {
            self::$cacheDir = MG_CACHE_DIR;
        } else {
            self::$cacheDir = realpath(dirname(__FILE__) . '/../cache');
        }

        // init cache URL location
        if (defined('MG_CACHE_PATH')) {
            self::$cachePath = MG_CACHE_PATH;
        } else {
            self::$cachePath = plugins_url() . '/mg-cache/cache';
        }

        // init web root location
        self::$webRoot = dirname($_SERVER['SCRIPT_FILENAME']);
    }

    /**
     * Check if file locations are usable
     *
     * @since MgCache 1.0
     *
     * @return null
     */
    public static function checkLocations()
    {
        if (!is_writable(self::$cacheDir)) {
            add_action('admin_notices', array('MgCacheHelper', 'adminErrorNoticeCacheDirectoryWritable'));
        }
    }

    /**
     * Initialize options
     *
     * @since MgCache 1.0
     *
     * @return array
     */
    public static function initOptions()
    {

        // default options
        $options = array(
            'cache_stylesheets' => true,
            'cache_scripts'     => true,
            'concatenate_files' => true,
            'minify_output'     => true,
        );

        return $options;
    }

    /**
     * Get Admin Options
     *
     * @since MgCache 1.0
     *
     * @param boolean $install
     * @return array
     */
    public static function getAdminOptions($install = false)
    {

        // get options
        $adminOptions = self::initOptions();

        // get options from database
        $databaseOptions = get_option(self::$adminOptionsName);

        // loop through database results and replace default options with actual data
        if (!empty($databaseOptions)) {
            foreach ($databaseOptions as $key => $option) {
                $adminOptions[$key] = $option;
            }
        }

        // store options in database and return them
        if ($install) {
            update_option(self::$adminOptionsName, $adminOptions);
        }

        return $adminOptions;
    }

    /**
     * Flush cache contents
     *
     * @since MgCache 1.0
     *
     * return null
     */
    public static function flush()
    {
        array_map('unlink', glob(self::$cacheDir . "/*"));
    }

    /**
     * Admin error notice cache directory not writable
     *
     * @since MgCache 1.0
     *
     * @return null
     */
    public static function adminErrorNoticeCacheDirectoryWritable()
    {
        echo "<div class='error'><p>" . __("The MG Cache plugin requires that the cache directory is writable. " ) . "</p></div>\n";
    }
}
