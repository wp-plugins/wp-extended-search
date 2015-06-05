=== WP Extended Search ===
Contributors: 5um17
Tags: search, postmeta, taxonomy, advance search, category search, page search, tag search
Requires at least: 3.7
Tested up to: 4.2.2
Stable tag: 1.0.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Extend default search to search in selected post meta or taxonomies.

== Description ==
Control WordPress default search to search in Post Meta, Categories, Tags or Custom Taxonomies via admin settings. Admin can select meta keys to search in, also can control the default behavior of search in post title or post content. 
 
You can include or exclude post types to appear in search results.

= Features =

* Search in selected meta keys
* Search in selected in-built or custom taxonomies
* Include or exclude any public post type
* Control whether to search in title or content or both
* Exclude old content from search results (Older than admin specified date)
* Translation ready

Get detailed documentation [here](http://www.secretsofgeeks.com/2014/09/wordpress-search-tags-and-categories.html)

== Installation ==

* Install WP Extended Search from the 'Plugins' section in your dashboard (Plugins > Add New > Search for 'WP Extended Search').
  Or
  Download WP Extended Search and upload it to your webserver via your FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).
* Activate the plugin and navigate to (Settings > Extended Search) to choose your desired search settings.

== Frequently Asked Questions ==

= Do you have any question? =

Please use plugin [support forum](http://wordpress.org/support/plugin/wp-extended-search) 

== Screenshots ==
1. WP Extented Search settings page

== Changelog ==

= 1.0.2 - 11/01/2015 =
* Added support for post_type parameter in URL
* Exclude old content from search results

= 1.0.1 - 03/10/2014 =
* Fixed taxonomy table join issue
* Added new filters wpes_meta_keys_query, wpes_tax_args, wpes_post_types_args, wpes_enabled, wpes_posts_search
* Internationalized plugin.

= 1.0 - 14/09/2014 =
* First Release