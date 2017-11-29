<?php

/**
 * MgAssetController class
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

class MgAssetController
{

    /**
     * Handle stylesheet assets.
     *
     * @since MgCache 1.0
     *
     * @return null
     */
    public static function assetStylesheetAction()
    {

        $pagename    = get_query_var('pagename');
        $files       = get_query_var('files');
        $fingerprint = get_query_var('fingerprint');

        // check if on mg_asset_css page and have 'files' parameter
        if ($pagename == 'mg_asset_css' && $files) {

            $content      = '';
            $lastModified = new \DateTime('-1 year');

            // initialize cached resource file location
            $cachedResourceFile = MgCacheHelper::$cacheDir .'/' . $fingerprint . '.css';

            // create new cached resource, save to cache directory
            if (!file_exists($cachedResourceFile)) {

                // turn files parameter into array
                $fileArray = explode(',', $files);
                if (is_array($fileArray) && count($fileArray)) {

                    // loop over array of files
                    foreach ($fileArray as $file) {

                        if (!$stylesheetPath = realpath(MgCacheHelper::$webRoot . $file)) {
                            $stylesheetPath = realpath(ABSPATH . $file);
                        }

                        if ($stylesheetPath) {

                            // get timestamp for most recently modified file
                            $localLastModified = new \DateTime(date("F d Y H:i:s", filemtime($stylesheetPath)));
                            if ($localLastModified > $lastModified) {
                                $lastModified = $localLastModified;
                            }

                            // get contents of each file while fixing asset paths
                            $content .= MgAssetHelper::assetContents($stylesheetPath);
                        }
                    }

                    // strip whitespace and comments from $content
                    if (MgAssetHelper::$minify) {
                        $content = preg_replace(array('/\/\*.*?\*\//s', '/\s+/'), array('', ' '), $content);
                    }
                }

                // save contents
                if (!empty($content)) {
                    if (!is_writable(dirname($cachedResourceFile))) {
                        mkdir(dirname($cachedResourceFile), 0775, true);
                    }
                    if (is_writable(dirname($cachedResourceFile))) {
                        file_put_contents($cachedResourceFile, $content);
                    }
                }
            }

            // read cached resource to buffer
            if (file_exists($cachedResourceFile)) {
                ob_start();
                readfile($cachedResourceFile);
                $content = ob_get_contents();
                ob_end_clean();

                $lastModified = new \DateTime(date("F d Y H:i:s", filemtime($cachedResourceFile)));
            }

            // output
            if (empty($content)) {
                header('HTTP/1.1 404 Not Found');
            } else {
                header('HTTP/1.1 200 OK');
                header('Content-Type: text/css');
                header('Content-Length: ' . strlen($content));
                header('Expires: ' . date('D, d M Y H:i:s e', strtotime('+1 year')));
                header('Cache-Control: max-age=31556926');
                header('Last-Modified: ' . $lastModified->format('D, d M Y H:i:s e'));
                header('Date: ' . $lastModified->format('D, d M Y H:i:s e'));
                header('Pragma: cache');
                header('Vary: Accept-Encoding');
                header('X-Content-Type-Options: nosniff');
                header('Accept-Ranges: bytes');

                echo $content;
            }

            exit;
        }
    }

    /**
     * Handle script assets.
     *
     * @since MgCache 1.0
     *
     * @return null
     */
    public static function assetScriptAction()
    {

        $pagename    = get_query_var('pagename');
        $files       = get_query_var('files');
        $fingerprint = get_query_var('fingerprint');

        // check if on mg_asset_js page and have 'files' parameter
        if ($pagename == 'mg_asset_js' && $files) {

            $content      = '';
            $lastModified = new \DateTime('-1 year');

            // initialize cached resource file location
            $cachedResourceFile = MgCacheHelper::$cacheDir .'/' . $fingerprint . '.js';

            // create new cached resource, save to cache directory
            if (!file_exists($cachedResourceFile)) {

                // turn files parameter into array
                $fileArray = explode(',', $files);
                if (is_array($fileArray) && count($fileArray)) {

                    // loop over array of files
                    foreach ($fileArray as $file) {

                        if (!$scriptPath = realpath(MgCacheHelper::$webRoot . $file)) {
                            $scriptPath = realpath(ABSPATH . $file);
                        }

                        if ($scriptPath) {

                            // get timestamp for most recently modified file
                            $localLastModified = new \DateTime(date("F d Y H:i:s", filemtime($scriptPath)));
                            if ($localLastModified > $lastModified) {
                                $lastModified = $localLastModified;
                            }

                            // get contents of each file
                            $content .= file_get_contents($scriptPath);
                        }
                    }

                    // strip whitespace and comments from $content
                    if (MgAssetHelper::$minify) {
                        include_once(realpath(dirname(__FILE__) . '/../vendor/JShrink/Minifier.php'));
                        $content = JShrink\Minifier::minify($content);
                    }
                }

                // save contents
                if (!empty($content)) {
                    if (!is_writable(dirname($cachedResourceFile))) {
                        mkdir(dirname($cachedResourceFile), 0775, true);
                    }
                    if (is_writable(dirname($cachedResourceFile))) {
                        file_put_contents($cachedResourceFile, $content);
                    }
                }
            }

            // read cached resource to buffer
            if (file_exists($cachedResourceFile)) {
                ob_start();
                readfile($cachedResourceFile);
                $content = ob_get_contents();
                ob_end_clean();

                $lastModified = new \DateTime(date("F d Y H:i:s", filemtime($cachedResourceFile)));
            }

            // output
            if (empty($content)) {
                header('HTTP/1.1 404 Not Found');
            } else {
                header('HTTP/1.1 200 OK');
                header('Content-Type: text/javascript');
                header('Content-Length: ' . strlen($content));
                header('Expires: ' . date('D, d M Y H:i:s e', strtotime('+1 year')));
                header('Cache-Control: max-age=31556926');
                header('Last-Modified: ' . $lastModified->format('D, d M Y H:i:s e'));
                header('Date: ' . $lastModified->format('D, d M Y H:i:s e'));
                header('Pragma: cache');
                header('Vary: Accept-Encoding');
                header('X-Content-Type-Options: nosniff');
                header('Accept-Ranges: bytes');

                echo $content;
            }

            exit;
        }
    }
}
