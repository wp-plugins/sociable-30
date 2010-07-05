=== Sociable for WordPress 3.0 ===
Contributors: tompokress
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=H3YD2QYUJH8TY
Tags: sociable,sexy bookmarks,sexy,social,bookmark,social bookmarks,social bookmark,bookmarks,bookmarking,social bookmarking,facebook,sharing,sociable,share,sharethis,Digg,Facebook,Twitter,del.icio.us
Requires at least: 2.7
Tested up to: 3.0
Stable tag: 4.0.8

== Description ==

The famous Sociable plugin now updated and compatible with WordPress 3.0.  Add social bookmarks to posts, pages and RSS feeds. Choose from 99 different social bookmarking sites like Digg, Facebook, and del.icio.us, or add your own sites!

= NEW =
* A [sociable] shortcode is now available - see the "Other Notes" section
* Options completely rewritten for WordPress 3.0 including WPMU/multisite installations
* I18N fixed - please send me your updated translations!
* RSS feeds fixed

== Installation ==

1. Deactivate any old Sociable versions
2. Unzip the sociable plugin zip file
3. Upload the sociable plugin files to your sociable folder /wp-contents/plugins/sociable-30
4. Activate the sociable plugin from the WordPress 'plugins' screen
5. Use the sociable settings page to activate your bookmarks (only a few are active by default)

== Upgrade ==

1. Deactivate any old Sociable versions
2. Unzip the sociable plugin zip file
3. Upload the sociable plugin files to folder /wp-contents/plugins/sociable-30
4. Activate the plugin from the WordPress 'plugins' screen
5. Use the settings page to activate your bookmarks (only a few are active by default).

== Advanced Users ==

A [sociable] shortcode is available so you can place the Sociable bookmarks anywhere you like in a post or page.

Another option is to turn off the automatic bookmarks display in the Sociable options screen and add calls directly to your theme.  With this approach you can also specify which sites to display:
`
// Show all activate sites
<?php global $sociable; echo $sociable->get_links(); ?>

// Show only these two sites if they are active
<?php global $sociable; echo $sociable->get_links(Array('blogmarks', 'Blogosphere')); ?>
`

== Frequently Asked Questions ==

= How can I add a new site? =
You can send it to me for inclusion in the next version of the plugin or do it yourself by editing the sites.php file and add the icon to the images directory.

= How can I change the CSS? =
You can add a 'sociable_custom.css' to the plugin directory and it will be included (along with the original) when the plugin is loaded.  Override the settings you need changed.

= How has Sociable been updated? =
The plugin has been rewritten to fix bugs in past versions and make it WordPress 3.0 and multisite compatible.  It's now supported and lots of enhancements are coming soon.

== Changelog ==

= 4.0.8 =
* Added [sociable] shortcode

= 4.0.7 =
* Fixed bug in RSS output

= 4.0.6 =
* i18n fixed
* Options are now (I think) WPMU/multisite compatible
* "Use CSS" option removed - for custom CSS create a file "sociable_custom.css" in the sociable directory
* usetextlinks and disablesprites removed
* awe.sm options removed

= 4.0.3 - 4.0.5 =
* Various array type bugs fixed

= 4.0.2 =
* Fixed bug that prevented meta box from displaying in post edit screen

= 4.0.1 =
* Fixed error in original code for wp_insert_post hook

= 4.0.0 =
* Woo-hoo Sociable for WordPress 3.0 released and working!  Sociable is now WordPress 3.0 compatible and many more enhancements to come.
* Sociable core code re-written to object-oriented PHP and WP 3.0

== Features ==

* Sociable plugin upgraded to WordPress 3.0
* Includes 99 social bookmarking sites
* Create your own sites
* Bookmarks are automatically added to posts, pages or RSS feeds
* You control which posts/pages display booksmarks
* Use our icons or add your own

== Screenshots ==
1. Sociable options screen.  Just drag and drop the bookmarks to change their display order.
2. Sociable with default styling.

