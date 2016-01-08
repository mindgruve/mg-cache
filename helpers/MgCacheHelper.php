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

    protected static $cacheSubdir = 'prod';

    /**
     *
     * METHODS
     *
     */

    public static function init()
    {
        if (defined('MG_CACHE_DIR')) {
            self::$cacheDir = WP_CONTENT_DIR.'/'.MG_CACHE_DIR.'/'.self::$cacheSubdir;
        } else {
            self::$cacheDir = WP_CONTENT_DIR.'/cache/'.self::$cacheSubdir;
        }

        $wpContentUrl = preg_replace(
            '/(http|https):\/\/'.$_SERVER['HTTP_HOST'].'\/(.*)\/wp-content/',
            '/wp-content',
            WP_CONTENT_URL
        );
        if (defined('MG_CACHE_PATH')) {
            self::$cachePath = $wpContentUrl.'/'.MG_CACHE_PATH.'/'.self::$cacheSubdir;
        } else {
            self::$cachePath = $wpContentUrl.'/cache/'.self::$cacheSubdir;
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
        self::$webRoot = realpath(dirname($_SERVER['SCRIPT_FILENAME'])).'/';
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
            $testFile = self::$cacheDir.'/__cache.txt';
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
     * @param bool $install
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
        $cacheDirBk = self::$cacheDir.'_bk'.uniqid();
        rename(self::$cacheDir, $cacheDirBk);
        self::removeDirectory($cacheDirBk);
    }

    protected static function removeDirectory($path) {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? self::removeDirectory($file) : unlink($file);
        }
        rmdir($path);
        return;
    }

    private static function hash($key, $group)
    {
        return md5($key.'-'.$group);
    }

}
