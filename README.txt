=== Environmentalist ===
Contributors: magicroundabout
Tags: environment, options, development
Requires at least: 4.0
Tested up to: 4.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Per-environment options for WordPress.

== Description ==

environmentalist (n):
1) a person who is concerned with the conservation of the environment

The plugin is concerned with the conservation of your environment. In particular it lets you have per-environment options. You can switch environments by adding a constant to wp-config.php

*NOTE: This plugin is very clever (I like to think) but is a massive hack and potentially dangerous to your WordPress settings and database. I shall not guarantee its operation, and in particular, its use with other complex plugins may be disastrous. Use carefully and at your own risk!*

*ALSO NOTE: There is an additional installation step required after uploading this plugin - see the installation section*

This plugin's function is best demonstrated with an example. WordPress has a lovely "Search Engine Visibility" setting in Settings->Reading. I generally want the "discourage search engines" option turned off on my live sites, but turned on on my development sites.

But then I'll often use a database sync to copy content and settings between development and live sites. And when I do that, I don't want settings like the Search Engine Visibility setting to carry over.  What I want is for that option to be specific to my environment, and yet still editable from the WordPress dashboard.

This is what Environmentalist does. If you set the WP_ENVIRONMENTALIST constant in wp-config.php to the name of your environment (e.g. 'dev' or 'live'), then any options that get saved are saved in such as way that they are specific to the defined environment.

= Note to developers and the technically minded =

WordPress made this REALLY hard. The thing that you need to know is that I - reluctantly - store all of the overridden options inside a single option. So there IS potential for the option value length in the database to be hit with this plugin. I will also make no guarantees about performance and memory usage.

== Installation ==

1. Upload the `environmentalist` folder to the `/wp-content/plugins/` directory
1. *IMPORTANT:* Move the `environmentalist-loader.php.move-me` file to `/wp-content/plugins/environmentalist-loader.php` - this loads Environmentalist before other plugins, which is needed for the whole thing to work.
1. Activate the plugin through the \'Plugins\' menu in WordPress

Alternatively you can upload `environmentalist.zip` from Plugins -> Add New -> Upload Plugin, but make sure you also follow the IMPORTANT step above as well.

== Frequently Asked Questions ==

1. It broke

Don't say I didn't warn you.

== Changelog ==
= 0.1 =
* Initial version