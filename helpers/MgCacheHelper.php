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

    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    public static $cacheDriver = null;

    // directories
    public static $cacheDir = null;
    public static $cachePath = null;
    public static $webRoot = null;
    public static $fileExtension = null;

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

        $wpContentUrl = preg_replace('/(http|https):\/\/' . $_SERVER['HTTP_HOST'] . '\/(.*)\/wp-content/', '/wp-content', WP_CONTENT_URL);
        if (defined('MG_CACHE_PATH')) {
            self::$cachePath = $wpContentUrl . '/' . MG_CACHE_PATH;
        } else {
            self::$cachePath = $wpContentUrl . '/cache';
        }

        if (defined('MG_CACHE_EXTENSION')) {
            self::$fileExtension = MG_CACHE_EXTENSION;
        } else {
            self::$fileExtension = 'cache';
        }

        if (defined('MG_CACHE_DRIVER')) {
            self::$cacheDriver = CacheProviderFactory::build(MG_CACHE_DRIVER);
        }

        // ABSPATH won't work reliably if WP is installed in a subdirectory
        self::$webRoot = realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . '/';
        if (empty(self::$webRoot)) {
            self::$webRoot = ABSPATH;
        }

        if (defined('MG_CACHE_DRIVER')) {
            self::$cacheDriver = CacheProviderFactory::build(MG_CACHE_DRIVER);
        } else {
            self::$cacheDriver = CacheProviderFactory::build('file');
        }


        // test that cache directory is writable (for windows and vagrant... which fail is_writable)
        try {
            $testFile = self::$cacheDir . '/__cache.txt';
            $fp = fopen($testFile, 'w');
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
            'cache_scripts' => true,
            'concatenate_files' => true,
            'minify_output' => true,
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
        self::$cacheDriver->setNamespace($group);
        if (self::$cacheDriver->contains($key)) {
            return false;
        }

        self::$cacheDriver->save($key, $data, self::$objectCacheTimeout);
    }

    public static function set($key, $data, $group = '')
    {
        self::$cacheDriver->setNamespace($group);
        self::$cacheDriver->save($key, $data, self::$objectCacheTimeout);
    }

    public static function get($key, $group = '')
    {
        self::$cacheDriver->setNamespace($group);
        if (self::$cacheDriver->contains($key)) {
            return self::$cacheDriver->fetch($key);
        }

        return false;
    }

    public static function exists($key, $group = '')
    {
        self::$cacheDriver->setNamespace($group);

        return self::$cacheDriver->contains($key);
    }

    public static function delete($key, $group = '')
    {
        self::$cacheDriver->setNamespace($group);
        self::$cacheDriver->delete($key);
    }

    public static function flush()
    {
        self::$cacheDriver->flushAll();
        self::flushJavascript();
        self::flushCss();
        unlink(self::$cacheDir.'/__cache.txt');
    }

    public static function flushJavascript()
    {
        foreach (glob(self::$cacheDir . "/*.js") as $jsFile) {
            unlink($jsFile);
        }
    }

    public static function flushCss()
    {
        foreach (glob(self::$cacheDir . "/*.css") as $cssFile) {
            unlink($cssFile);
        }
    }

    private static function hash($key, $group)
    {
        return md5($key . '-' . $group);
    }

    public static function getCachedPage()
    {
        $options = self::getAdminOptions();
        $key = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $group = 'pageCache';


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
            header('mgcache: ' . $group . '-' . $key);
            echo self::get($key, $group);
            exit;
        }
    }

    public static function cachePage($data)
    {
        $options = self::getAdminOptions();
        $key = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $group = 'pageCache';

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
