<?php

/**
 * MgCacheAdmin class
 *
 * Handle WordPress admin page for cache plugin.
 *
 * @package     WordPress
 * @subpackage  MgCache
 * @version     1.0
 * @since       MgCache 1.0
 * @author      kchevalier@mindgruve.com
 */

defined('ABSPATH') or die();

class MgCacheAdmin
{

    /**
     * Print Admin Page.
     *
     * @since MgCache 1.0
     *
     * @return null
     */
    public static function printAdminPage()
    {

        // retrieve admin options
        $adminOptions = MgCacheHelper::getAdminOptions();

        $route = str_replace('&clear_cache=true', '', $_SERVER['REQUEST_URI']);

        // manual cache clear
        if (isset($_GET['clear_cache']) && $_GET['clear_cache'] == 'true') {

            // delete contents of cache directory
            MgCacheHelper::flush();

            // output status
            ?><div class="updated"><p><strong><?php _e("Cache Cleared.", "Mg Cache"); ?></strong></p></div><?php
        }

        // check if form was submitted
        if (isset($_POST['update_MgCacheSettings'])) {

            // update admin options array with post values
            $adminOptions['cache_stylesheets'] = isset($_POST['cache_stylesheets']) ? true : false;
            $adminOptions['cache_scripts']     = isset($_POST['cache_scripts']) ? true : false;
            $adminOptions['concatenate_files'] = isset($_POST['concatenate_files']) ? true : false;
            $adminOptions['minify_output']     = isset($_POST['minify_output']) ? true : false;

            // update database
            update_option(MgCacheHelper::$adminOptionsName, $adminOptions);

            // delete contents of cache directory
            MgCacheHelper::flush();

            // output status
            ?>
            <div class="updated"><p><strong><?php _e("Settings Updated.", "Mg Cache"); ?></strong></p></div><?php

        }

        // output admin form
        include(realpath(dirname(__FILE__) . '/views/admin-mg-cache.php'));
    }
}
