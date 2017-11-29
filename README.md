
# MGCache WordPress Plugin

MGCache is an optional helper plugin for MGPress, a WordPress theme library. It allows a user to manage caching for website assets, such as CSS and JavaScript files.

## Installing the Plugin

Install this plugin as you would any other:
 
1. Make sure you are using a theme based on MGPress.
2. Download the zip for this plugin (or clone it) and move it to `wp-content/plugins` in your WordPress installation. 
3. Activate the plugin in Plugins > Installed Plugins.
4. Go to Settings > Cache to configure the plugin's behavior.

## Settings

* Clear the Cache: Manually purge the cache directory. The next public request will create new cached files.

### Assets

* Cache Stylesheets: Enable caching of CSS stylesheets. This will create a new file on the server with a unique name ("fingerprinted") any time the file changes and set the file headers to maximumize browser caching. Must be enabled for any actions to have affect.

* Cache JavaScript Files: Enable caching of JavaScript files. This will create a new file on the server with a unique name ("fingerprinted") any time the file changes and set the file headers to maximumize browser caching. Must be enabled for any actions to have affect.


### Actions

* Combine Files: Attempt to combine multiple files into a single file to to minimize server requests. Optionally, files may be grouped into separate requests by the theme.
 
* Minify Files: Attempt to minimize the contents of the files by removing comments and extra whitespace.
