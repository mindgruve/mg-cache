<?php

/**
 * MgAssetHelper class
 *
 * Asset controller that handles asset URLs and logic to combine and minify files.
 *
 * @package     WordPress
 * @subpackage  MgCache
 * @version     1.0
 * @since       MgCache 1.0
 * @author      kchevalier@mindgruve.com
 */

defined('ABSPATH') or die();

class MgAssetHelper
{

    /* Options */

    /**
     * Flag to cache stylesheets
     * @var bool
     */
    public static $cacheStylesheets = true;

    /**
     * Flag to cache scripts
     * @var bool
     */
    public static $cacheScripts = true;

    /**
     * Flag to concatenate files
     * @var bool
     */
    public static $concatenate = true;

    /**
     * Flag to minify files
     * @var bool
     */
    public static $minify = true;

    /**
     * Flag to fingerprint files
     * @var bool
     */
    public static $fingerprint = true;


    /* Groups */

    /**
     * List of lists: [group name => [stylesheet handles]]
     * @var array
     */
    public static $stylesheetGroups = array();

    /**
     * List of lists: [group name => [script handles]]
     * @var array
     */
    public static $scriptGroups = array();


    /* Temporary data holders */

    /**
     * @var string
     */
    protected static $assetDirectory = null;

    /**
     * @var array
     */
    protected static $completedStyles = array();

    /**
     * @var array
     */
    protected static $completedScripts = array();

    /**
     * @var int
     */
    protected static $roundStyles = 0;

    /**
     * @var int
     */
    protected static $roundScripts = 0;


    /**
     * Initialize MgAssetHelper class.
     *
     * @since MgCache 1.0
     *
     * @return null
     */
    public static function init()
    {
        self::registerSettings();
        self::registerFilters();
    }

    /**
     * Register Settings.
     *
     * @since MgCache 1.0
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
     * Register filters.
     *
     * @since MgCache 1.0
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
     * Print styles array.
     *
     * @since MgCache 1.0
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
            $groupName  = count(self::$stylesheetGroups) ? uniqid() : null;

            foreach ($stylesheets as $handle) {

                if (isset($wp_styles->registered[$handle]) && !in_array($handle, self::$completedStyles)) {

                    if (!$wp_styles->registered[$handle]->src) {
                        continue;
                    }

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
                            if (isset(self::$stylesheetGroups[$groupName]) && !in_array($handle, self::$stylesheetGroups[$groupName])) {
                                $groupIndex++;
                                $groupName = uniqid();
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

                        // try to get filesystem path for asset
                        if (!$stylesheetPath = realpath(MgCacheHelper::$webRoot . $cleanRelativePath)) {
                            $stylesheetPath = realpath(ABSPATH . $cleanRelativePath);
                        }

                        // add file info to groups var
                        if ($stylesheetPath) {

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

                        // try to get filesystem path for asset
                        if (!$stylesheetPath = realpath(MgCacheHelper::$webRoot . $cleanRelativePath)) {
                            $stylesheetPath = realpath(ABSPATH . $cleanRelativePath);
                        }

                        // add file info to groups var
                        if ($stylesheetPath) {

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
     * Print sacripts array.
     *
     * @since MgCache 1.0
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
            $groupName  = count(self::$scriptGroups) ? uniqid() : null;

            foreach ($scripts as $handle) {

                if (isset($wp_scripts->registered[$handle]) && !in_array($handle, self::$completedScripts)) {

                    if (!$wp_scripts->registered[$handle]->src) {
                        continue;
                    }

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
                            if (isset(self::$scriptGroups[$groupName]) && !in_array($handle, self::$scriptGroups[$groupName])) {
                                $groupIndex++;
                                $groupName = uniqid();
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

                        // try to get filesystem path for asset
                        if (!$scriptPath = realpath(MgCacheHelper::$webRoot . $cleanRelativePath)) {
                            $scriptPath = realpath(ABSPATH . $cleanRelativePath);
                        }

                        // add file info to groups var
                        if ($scriptPath) {

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

                        // try to get filesystem path for asset
                        if (!$scriptPath = realpath(MgCacheHelper::$webRoot . $cleanRelativePath)) {
                            $scriptPath = realpath(ABSPATH . $cleanRelativePath);
                        }

                        // add file info to groups var
                        if ($scriptPath) {

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
     * Get the contents for an asset.
     *
     * @since MgCache 1.0
     *
     * @param string $file
     * @return string
     */
    public static function assetContents($file)
    {
        $return = '';

        if (file_exists($file)) {

            // get file info
            $pathinfo = pathinfo($file);

            // set local var for temp use in callback
            self::$assetDirectory = $pathinfo['dirname'];

            // string replacement callback
            $return = preg_replace_callback(
                '/(url\(\s*[\'\"]?)(.*?)([\'\"]?\s*\))/',
                array('MgAssetHelper', 'assetContentHandler'),
                file_get_contents($file)
            );
        }

        // reset local temp var
        self::$assetDirectory = null;

        return $return;
    }

    /**
     * Asset content handler.
     *
     * @since MgCache 1.0
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
     * Resolve relative paths.
     *  realpath() also resolves symlinks, so can't always use.
     *
     * @since MgCache 1.0
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
     * Is login page.
     *
     * @since MgCache 1.0
     *
     * @return boolean
     */
    protected static function isLoginPage()
    {
        return isset($GLOBALS['pagenow']) && in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
    }
}
