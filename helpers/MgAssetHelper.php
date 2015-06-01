<?php
/**
 * MgAssetHelper
 *
 * Asset Helper Class
 *
 * @package MG Cache
 * @author kchevalier@mindgruve.com
 * @version 1.0
 */

defined('ABSPATH') or die();

class MgAssetHelper
{

    /**
     *
     * PROPERTIES
     *
     */

    // options
    public static $cacheStylesheets    = true;
    public static $cacheScripts        = true;
    public static $concatenate         = true;
    public static $minify              = true;
    public static $fingerprint         = true;



    // groups
    public static $stylesheetGroups    = array();
    public static $scriptGroups        = array();

    // temporary data holders
    protected static $assetDirectory   = null;
    protected static $completedStyles  = array();
    protected static $completedScripts = array();
    protected static $roundStyles      = 0;
    protected static $roundScripts     = 0;


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
        
    }

    /**
     * Register Settings
     *
     * @return null
     */
    public static function registerSettings()
    {

        // retrieve admin options
        $adminOptions = MgCacheHelper::getAdminOptions();

        // set internal properties
        self::$cacheStylesheets = $adminOptions['cache_stylesheets'] ? true : false;
        self::$cacheScripts     = $adminOptions['cache_scripts'] ? true : false;
        self::$concatenate      = $adminOptions['concatenate_files'] ? true : false;
        self::$minify           = $adminOptions['minify_output'] ? true : false;
    }

    /**
     * Register Filters
     *
     * @return null
     */
    public static function registerFilters()
    {
        if (self::$fingerprint || self::$concatenate || self::$minify) {
            if (!is_admin() && !self::isLoginPage()) {

                // print styles filter
                if (self::$cacheStylesheets) {
                    add_filter('print_styles_array', array('MgAssetHelper', 'printStylesArray'));
                }

                // print scripts filter
                if (self::$cacheScripts) {
                    add_filter('print_scripts_array', array('MgAssetHelper', 'printScriptsArray'));
                }
            }
        }
    }

    /**
     * Print Styles Array
     *
     * @global WP_Styles $wp_styles
     * @param array $stylesheets
     * @return array
     */
    public static function printStylesArray($stylesheets)
    {
        global $wp_styles;

        $paths = array();

        // loop over stylesheets
        if (is_array($stylesheets) && count($stylesheets)) {

            $groups     = array();
            $groupIndex = 0;
            $groupName  = count(self::$stylesheetGroups) ? reset(self::$stylesheetGroups) : null;

            foreach ($stylesheets as $handle) {

                if (isset($wp_styles->registered[$handle]) && !in_array($handle, self::$completedStyles)) {

                    // add handle to completed script array so we don't add to head and footer
                    self::$completedStyles[] = $handle;

                    // get relative paths for local files
                    $relativePath = preg_replace('/^(?:https?:)?\/\/' . $_SERVER['SERVER_NAME'] . '/',
                        '',
                        $wp_styles->registered[$handle]->src
                    );

                    // remove URL params from path
                    $cleanRelativePath = preg_replace('/\?.*$/', '', $relativePath);

                    // concatenate files together
                    if (self::$concatenate) {

                        // support manual grouping
                        if ($groupName) {
                            foreach (self::$stylesheetGroups as $key => $value) {
                                if (is_array($value) && count($value)) {
                                    foreach ($value as $key2 => $value2) {
                                        if ($value2 == $handle && $key != $groupName) {
                                            $groupIndex++;
                                            $groupName = $key;
                                        }
                                    }
                                }
                            }
                        }

                        // initialize group
                        if (!isset($groups[$groupIndex])) {
                            $groups[$groupIndex] = array(
                                'content'   => '',
                                'files'     => array(),
                                'handle'    => '',
                            );
                        }

                        // add file info to groups var
                        if ($stylesheetPath = realpath(MgCacheHelper::$webRoot . $cleanRelativePath)) {

                            //$groups[$groupIndex]['content'] .= file_get_contents($stylesheetPath); // slow - can we speed up at all?
                            $groups[$groupIndex]['content'] .= filemtime($stylesheetPath) . $stylesheetPath;
                            $groups[$groupIndex]['files'][] = $cleanRelativePath;

                        } else { // stylesheet not a local asset
                            if (!isset($groups[++$groupIndex])) {
                                $groups[$groupIndex] = array(
                                    'content'   => '',
                                    'files'     => array($wp_styles->registered[$handle]->src),
                                    'handle'    => $handle,
                                );
                                $groupIndex++;
                            }
                        }

                    } elseif (self::$fingerprint) { // fingerprint files - no concatenation

                        // add file info to groups var
                        if ($stylesheetPath = realpath(MgCacheHelper::$webRoot . $cleanRelativePath)) {

                            // initialize group
                            if (!isset($groups[++$groupIndex])) {
                                $groups[$groupIndex] = array(
                                    //'content'   => file_get_contents($stylesheetPath), // slow - can we speed up at all?
                                    'content'   => filemtime($stylesheetPath) . $stylesheetPath,
                                    'files'     => array($cleanRelativePath),
                                    'handle'    => $handle,
                                );
                            }

                        } else { // stylesheet not a local asset
                            if (!isset($groups[++$groupIndex])) {
                                $groups[$groupIndex] = array(
                                    'content'   => '',
                                    'files'     => array($wp_styles->registered[$handle]->src),
                                    'handle'    => $handle,
                                );
                                $groupIndex++;
                            }
                        }
                    } else { // no changes to stylesheets
                        $paths = $stylesheets;
                    }
                }
            }

            if (count($groups)) {
                foreach ($groups as $group) {
                    if (!empty($group['content'])) {

                        $hash = md5($group['content']);

                        wp_register_style(
                            $hash,
                            MgCacheHelper::$cachePath . '/' . $hash . '.css?files=' . urlencode(implode(',', $group['files'])),
                            array(),
                            null,
                            'all'
                        );

                        wp_enqueue_style($hash);
                        $paths[] = $hash;

                    } elseif (!empty($group['handle'])) {
                        $paths[] = $group['handle'];
                    }
                }
            }
        }

        self::$roundStyles++;

        return $paths;
    }

    /**
     * Print Scripts Array
     *
     * @global WP_Styles $wp_scripts
     * @param array $scripts
     * @return array
     */
    public static function printScriptsArray($scripts)
    {
        global $wp_scripts;

        $paths = array();

        // loop over scripts
        if (is_array($scripts) && count($scripts)) {

            $groups     = array();
            $groupIndex = 0;
            $groupName  = count(self::$scriptGroups) ? reset(self::$scriptGroups) : null;

            foreach ($scripts as $handle) {

                if (isset($wp_scripts->registered[$handle]) && !in_array($handle, self::$completedScripts)) {

                    // skip scripts bound for footer on initial pass
                    if (self::$roundScripts == 0
                        && isset($wp_scripts->registered[$handle]->extra['group'])
                        && $wp_scripts->registered[$handle]->extra['group'] == 1
                    ) {
                        continue;
                    }

                    // add handle to completed script array so we don't add to head and footer
                    self::$completedScripts[] = $handle;

                    // get relative paths for local files
                    $relativePath = preg_replace(
                        '/^(?:https?:)?\/\/' . $_SERVER['SERVER_NAME'] . '/',
                        '',
                        $wp_scripts->registered[$handle]->src
                    );

                    // remove URL params from path
                    $cleanRelativePath = preg_replace('/\?.*$/', '', $relativePath);

                    // concatenate files together
                    if (self::$concatenate) {

                        // support manual grouping
                        if ($groupName) {
                            foreach (self::$scriptGroups as $key => $value) {
                                if (is_array($value) && count($value)) {
                                    foreach ($value as $key2 => $value2) {
                                        if ($value2 == $handle && $key != $groupName) {
                                            $groupIndex++;
                                            $groupName = $key;
                                        }
                                    }
                                }
                            }
                        }

                        // initialize group
                        if (!isset($groups[$groupIndex])) {
                            $groups[$groupIndex] = array(
                                'content'   => '',
                                'files'     => array(),
                                'handle'    => '',
                                'data'      => '',
                            );
                        }

                        // add file info to groups var
                        if ($scriptPath = realpath(MgCacheHelper::$webRoot . $cleanRelativePath)) {

                            $groups[$groupIndex]['content'] .= filemtime($scriptPath) . $scriptPath;
                            $groups[$groupIndex]['files'][] = $cleanRelativePath;
                            if (isset($wp_scripts->registered[$handle]->extra['data'])) {
                                $groups[$groupIndex]['data'] .= $wp_scripts->registered[$handle]->extra['data'] . "\n";
                            }

                        } else { // script not a local asset
                            if (!isset($groups[++$groupIndex])) {
                                $groups[$groupIndex] = array(
                                    'content'   => '',
                                    'files'     => array($wp_scripts->registered[$handle]->src),
                                    'handle'    => $handle,
                                    'data'      => '',
                                );
                                if (isset($wp_scripts->registered[$handle]->extra['data'])) {
                                    $groups[$groupIndex]['data'] .= $wp_scripts->registered[$handle]->extra['data'] . "\n";
                                }
                                $groupIndex++;
                            }
                        }

                    } elseif (self::$fingerprint) { // fingerprint files - no concatenation

                        // add file info to groups var
                        if ($scriptPath = realpath(MgCacheHelper::$webRoot . $cleanRelativePath)) {

                            // initialize group
                            if (!isset($groups[++$groupIndex])) {
                                $groups[$groupIndex] = array(
                                    'content'   => filemtime($scriptPath) . $scriptPath,
                                    'files'     => array($cleanRelativePath),
                                    'handle'    => $handle,
                                    'data'      => '',
                                );
                                if (isset($wp_scripts->registered[$handle]->extra['data'])) {
                                    $groups[$groupIndex]['data'] .= $wp_scripts->registered[$handle]->extra['data'] . "\n";
                                }
                            }

                        } else { // script not a local asset
                            if (!isset($groups[++$groupIndex])) {
                                $groups[$groupIndex] = array(
                                    'content'   => '',
                                    'files'     => array($wp_scripts->registered[$handle]->src),
                                    'handle'    => $handle,
                                    'data'      => '',
                                );
                                if (isset($wp_scripts->registered[$handle]->extra['data'])) {
                                    $groups[$groupIndex]['data'] .= $wp_scripts->registered[$handle]->extra['data'] . "\n";
                                }
                                $groupIndex++;
                            }
                        }
                    } else { // no changes to stylesheets
                        $paths = $scripts;
                    }
                }
            }

            if (count($groups)) {
                foreach ($groups as $group) {
                    if (!empty($group['content'])) {

                        $hash = md5($group['content']);

                        wp_register_script(
                            $hash,
                            MgCacheHelper::$cachePath . '/' . $hash . '.js?files=' . urlencode(implode(',', $group['files'])),
                            array(),
                            null,
                            //'all',
                            (bool) (self::$roundScripts > 0)
                        );

                        // why isn't this handled internally by WP?
                        $wp_scripts->set_group($hash, false, (bool) (self::$roundScripts > 0));

                        // handle localization
                        if (!empty($group['data'])) {
                            echo "<script type='text/javascript'>\n/* <![CDATA[ */\n" . $group['data'] . "/* ]]> */\n</script>\n";
                        }

                        wp_enqueue_script($hash);

                        $paths[] = $hash;

                    } elseif (!empty($group['handle'])) {
                        $paths[] = $group['handle'];
                    }
                }
            }
        }

        self::$roundScripts++;

        return $paths;
    }

    /**
     * Asset Contents
     *
     * @param string $file
     * @return string
     */
    public static function assetContents($file)
    {
        $return = '';

        if ($stylesheetPath = realpath(MgCacheHelper::$webRoot . $file)) {

            // get file info
            $pathinfo = pathinfo($file);

            // set local var for temp use in callback
            self::$assetDirectory = $pathinfo['dirname'];

            // string replacement callback
            $return = preg_replace_callback(
                '/(url\(\s*[\'\"]?)(.*?)([\'\"]?\s*\))/',
                array('MgAssetHelper', 'assetContentHandler'),
                file_get_contents($stylesheetPath)
            );
        }

        // reset local temp var
        self::$assetDirectory = null;

        return $return;
    }

    /**
     * Asset Content Handler
     *
     * @param array $matches
     * @return string
     */
    public static function assetContentHandler($matches)
    {
        $return = null;
        if (is_array($matches) && count($matches) > 2) {
            $return = $matches[1] . $matches[2] . $matches[3];
            $stylesheetPath = substr(MgCacheHelper::$webRoot, 0, -1)
                . self::$assetDirectory . '/'
                . preg_replace('/[\?#].*$/', '', $matches[2]);
            if (file_exists($stylesheetPath)) {
                $return = $matches[1] . str_replace(
                        MgCacheHelper::$webRoot,
                    '/',
                    self::resolveRelativePaths(
                        substr(MgCacheHelper::$webRoot, 0, -1) . self::$assetDirectory. '/' . $matches[2]
                    )
                ) . $matches[3];
            }
        }
        return $return;
    }

    /**
     * Resolve Relative Paths
     *  resolve relative paths - realpath() also resolves symlinks, so can't always use
     *
     * @param string $path
     * @return string
     */
    public static function resolveRelativePaths($path)
    {
        $directories = explode('/', $path);
        $parents = array();
        foreach($directories as $dir) {
            switch($dir) {
                case '.':
                // Don't need to do anything here
                break;
                case '..':
                    array_pop($parents);
                break;
                default:
                    $parents[] = $dir;
                break;
            }
        }
        return implode('/', $parents);
    }

    /**
     * Is Login Page
     *
     * @return boolean
     */
    protected static function isLoginPage()
    {
        return isset($GLOBALS['pagenow']) && in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
    }

    /**
     * Admin Error Notice Class Conflict
     *
     * @return null
     */
    public static function adminErrorNoticeCacheDirectoryWritable()
    {
        echo "<div class='error'><p>" . __("The MG Cache plugin requires that the cache directory is writable. " ) . "</p></div>\n";
    }
}
