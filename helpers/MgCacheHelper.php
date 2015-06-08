<?php
/**
 * MgCacheHelper
 *
 * Cache Helper Class
 *
 * @package MG Cache
 * @author kchevalier@mindgruve.com
 * @version 1.0
 */

defined('ABSPATH') or die();

class MgCacheHelper
{

    /**
     *
     * PROPERTIES
     *
     */

    // data labels
    public static $adminOptionsName = 'mgCacheValues';
    public static $objectCacheTimeout = 3600; // 15 minutes

    // directories
    public static $cacheDir = null;
    public static $cachePath = null;
    public static $webRoot = null;

    /**
     *
     * METHODS
     *
     */

    public static function init()
    {
        if (defined('MG_CACHE_DIR')) {
            self::$cacheDir = WP_CONTENT_DIR . '/' . MG_CACHE_DIR;
        } else {
            self::$cacheDir = WP_CONTENT_DIR . '/cache';
        }

        if (defined('MG_CACHE_PATH')) {
            self::$cachePath = '/wp-content/' . MG_CACHE_PATH;
        } else {
            self::$cachePath = '/wp-content/cache';
        }

        // ABSPATH won't work reliably if WP is installed in a subdirectory
        self::$webRoot = realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . '/';
        if (empty(self::$webRoot)) {
            self::$webRoot = ABSPATH;
        }

        // test that cache directory is writable (for windows and vagrant... which fail is_writable)
        try {
            $testFile = self::$cacheDir . '/__cache.txt';
            $fp       = fopen($testFile, 'w');
            fwrite($fp, 'hello cache!');
            if (!file_exists($testFile)) {
                throw new \Exception;
            }

            MgAssetHelper::registerSettings();
            MgAssetHelper::registerFilters();
        } catch (\Exception $e) {
            add_action('admin_notices', array('MgAssetHelper', 'adminErrorNoticeCacheDirectoryWritable'));
        }
        self::getCachedPage();
    }

    /**
     * init Options
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
     * @param true $install
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

    public static function add($key, $data, $group = '')
    {
        if (self::exists($key, $group)) {
            return false;
        }

        self::set($key, $data, $group);
    }

    public static function set($key, $data, $group = '')
    {
        file_put_contents(self::cacheFile($key, $group), serialize($data));
    }

    public static function get($key, $group = '')
    {
        if (self::exists($key, $group)) {
            return unserialize(file_get_contents(self::cacheFile($key, $group)));
        }

        return false;
    }

    public static function exists($key, $group = '')
    {
        $now  = time();
        $file = self::cacheFile($key, $group);
        if (!file_exists($file)) {
            return false;
        } elseif ((filemtime($file) + self::$objectCacheTimeout) <= $now) {
            self::delete($key, $group);

            return false;
        }

        return true;
    }

    public static function delete($key, $group = '')
    {
        $file = self::cacheFile($key, $group);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public static function flush()
    {
        $files = glob(self::$cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private static function hash($key, $group)
    {
        return md5($key . '-' . $group);
    }

    private static function cacheFile($key, $group)
    {
        return self::$cacheDir . '/' . self::hash($key, $group);
    }

    public static function getCachedPage()
    {
        $options = self::getAdminOptions();
        $key     = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $group   = 'pageCache';


        /* Page Caching not enabled */
        if (!isset($options['cache_pages']) || $options['cache_pages'] != true) {
            return;
        }

        /* Only use cache on GET request  */
        if (strtolower($_SERVER['REQUEST_METHOD']) != 'get') {
            return;
        }

        /* Don't show when user logged in  */
        if (is_user_logged_in()) {
            self::delete($key, $group);

            return;
        }

        /* AJAX check  */
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            return;
        }

        /* Skip if query param SKIP_CACHED_PAGE == true **/
        if (isset($_REQUEST['__NO_CACHE'])) {
            return;
        }

        // retrieve cache
        if (self::exists($key, $group)) {
            header('mgcache: ' . self::hash($key, $group));
            echo self::get($key, $group);
            exit;
        }
    }

    public static function cachePage($data)
    {
        $options = self::getAdminOptions();
        $key     = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $group   = 'pageCache';

        /* Page Caching not enabled */
        if (!isset($options['cache_pages']) || $options['cache_pages'] != true) {
            return;
        }

        /* Only use cache on GET request  */
        if (!strtolower($_SERVER['REQUEST_METHOD']) == 'get') {
            return;
        }

        /* Don't show when user logged in  */
        if (is_user_logged_in()) {
            return;
        }

        /* AJAX check  */
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            return;
        }

        // set cache
        self::set($key, $data, $group);
    }

    public static function onSavePost($post_id, $post, $update)
    {
        $options = self::getAdminOptions();

        /* Page Caching not enabled */
        if (!isset($options['cache_pages']) || $options['cache_pages'] != true) {
            return;
        }

        $permalink = get_permalink($post_id);
        self::delete($permalink, 'pageCache');
    }

    public static function onMenuUpdate()
    {
        $options = self::getAdminOptions();

        /* Page Caching not enabled */
        if (!isset($options['cache_pages']) || $options['cache_pages'] != true) {
            return;
        }

        self::flush();
    }
}
