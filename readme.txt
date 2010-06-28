=== Sociable for WordPress 3.0 ===
Contributors: tompokress
Tags: sociable, sociable, social, bookmark, social bookmarks, social bookmark, bookmarks, bookmarking, social bookmarking, facebook, sharing, sociable, share, sharethis, Digg, Facebook, Twitter, and del.icio.us
Requires at least: 2.7
Tested up to: 3.0
Stable tag: 4.0.0

Sociable people need this social bookmarking plugin!  This is the famous Sociable plugin updated for WordPress 3.0.  Add great-looking social bookmarks to your posts, pages and RSS feeds.

== Description ==

Sociable plugin gets an update for WordPress 3.0.  Add social bookmarks to posts, pages and RSS feeds. Choose from 99 different social bookmarking sites like Digg, Facebook, and del.icio.us, or add your own sites!

== Installation ==

1. Deactivate old Sociable versions
2. Unzip the sociable plugin zip file
3. Upload the sociable plugin files to your sociable folder /wp-contents/plugins/sociable3
4. Activate the sociable plugin from the WordPress 'plugins' screen
5. Use the sociable settings page to activate your bookmarks (only a few are active by default)

== Upgrade ==

1. Deactivate old Sociable versions
2. Unzip the sociable plugin zip file
3. Upload the sociable plugin files to your sociable folder /wp-contents/plugins/sociable3
4. Activate the sociable plugin from the WordPress 'plugins' screen
5. Use the sociable settings page to activate your bookmarks (only a few are active by default)65. Deactivate any old versions of Sociable!

== Upgrading Older Versions ==

Basically the same process as a new Sociable install.  Older Sociable versions can be upgraded to Sociable 3.0 just as if you were installing the Sociable plugin new.

1. Deactivate old Sociable versions
2. Unzip the sociable plugin zip file
3. Upload the sociable plugin files to your sociable folder /wp-contents/plugins/sociable3
4. Activate the sociable plugin from the WordPress 'plugins' screen
5. Use the sociable settings page to activate your bookmarks (only a few are active by default)65. Deactivate any old versions of Sociable!

== Advanced Users: ==

The plugin hooks `the_content()` and `the_excerpt()` to display the Sociable social bookmarks automatically without requiring any explicit calls from your theme.  To precisely customize where the Sociable social bookmarks display, use the admin panel to turn off all display options and then add calls directly to your theme:

// This is optional extra customization for advanced users
`<?php global $sociable; $sociable->sociable_html(); ?>` // all active sites
`<?php global $sociable; $sociable->sociable_html(Array("Test", "Test2")); ?>` // only these sites if they are active

== Frequently Asked Questions ==

= How has Sociable been improved in this version? =
The Sociable plugin has been re-written and cleaned up with object-oriented code - and updated for WordPress 3.0 compatibility.  Now look for new Sociable features coming soon!

== Credits ==

[Peter Harkins Sociable](http://push.cx/sociable) plugin.

== Changelog ==
= 4.0.0 =
* Jumped to higher version, folks found 3.x confusing

= 3.0.1 - 3.0.4 =
* Fixed lots of remaining bugs in old Sociable code

= 3.0.0 =
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

