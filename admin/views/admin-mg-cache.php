<div class='wrap'>

    <h2>Cache Management</h2>

    <p><a href="<?php echo $route; ?>&clear_cache=true"><?php _e( 'Click here to clear the cache', 'Mg Cache' ); ?></a></p>

    <form method='post' action='<?php echo $route; ?>'>

        <table class='form-table mg-cache-asset-settings'>
            <tbody>

                <tr>
                    <th scope="row">
                        <label>Assets</label>
                    </th>
                    <td>

                        <input type='checkbox' class='checkbox' <?php if ($adminOptions['cache_stylesheets']) echo " checked='checked'"; ?> id="cache_stylesheets" name="cache_stylesheets" />
                        <label for="cache_stylesheets">Cache stylesheets</label>

                        <br />

                        <input type='checkbox' class='checkbox' <?php if ($adminOptions['cache_scripts']) echo " checked='checked'"; ?> id="cache_scripts" name="cache_scripts" />
                        <label for="cache_scripts">Cache javascript files</label>

                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label>Actions</label>
                    </th>
                    <td>

                        <input type='checkbox' class='checkbox' <?php if ($adminOptions['concatenate_files']) echo " checked='checked'"; ?> id="concatenate_files" name="concatenate_files" />
                        <label for="concatenate_files">Combine files</label>

                        <br />

                        <input type='checkbox' class='checkbox' <?php if ($adminOptions['minify_output']) echo " checked='checked'"; ?> id="minify_output" name="minify_output" />
                        <label for="minify_output">Minify files</label>

                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label>Actions</label>
                    </th>
                    <td>

                        <input type='checkbox' class='checkbox' <?php if ($adminOptions['cache_pages']) echo " checked='checked'"; ?> id="cache_pages" name="cache_pages" />
                        <label for="cache_pages">Cache Pages & Objects</label>
                        <p>Cached pages are stored for 1 Hour</p>
                        
                    </td>
                    
                    
                </tr>

            </tbody>
        </table>

        <p class="submit"><input type="submit" name="update_MgCacheSettings" id="update_MgCacheSettings" class="button button-primary" value="<?php _e( 'Update Settings', 'Mg Cache' ); ?>" /></p>

    </form>

</div>
