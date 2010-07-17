=== Sociable for WordPress 3.0 ===
Contributors: tompokress
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=H3YD2QYUJH8TY
Tags: sociable,sexy bookmarks,sexy,social,bookmark,social bookmarks,social bookmark,bookmarks,bookmarking,social bookmarking,sharing,sociable,share,sharethis,Add to favorites,BarraPunto,Bitacoras.com,BlinkList,blogmarks,Blogosphere,blogtercimlap,Faves,connotea,Current,del.icio.us,Design Float,Digg,Diigo,DotNetKicks,DZone,eKudos,email,Facebook,Fark,Fleck,FriendFeed,FSDaily,Global Grind,Google,Google Buzz,Gwar,HackerNews,Haohao,HealthRanker,HelloTxt,Hemidemi,Hyves,Identi.ca,IndianPad,Internetmedia,Kirtsy,laaik.it,LaTafanera,LinkArena,LinkaGoGo,LinkedIn,Linkter,Live,Meneame,MisterWong,MisterWong.DE,Mixx,MOB,muti,MyShare,MySpace,MSNReporter,N4G,Netvibes,NewsVine,Netvouz,NuJIJ,Orkut,Ping.fm,Posterous,PDF,Plurk,Print,Propeller,Ratimarks,Rec6,Reddit,RSS,Scoopeo,Segnalo,SheToldMe,Simpy,Slashdot,Socialogs,SphereIt,Sphinn,StumbleUpon,Techmeme,Technorati,ThisNext,Tipd,Tumblr,Twitter,Upnews,viadeo FR,Webnews.de,Webride,Wikio,Wikio FR,Wikio IT,Wykop,Xerpi,YahooBuzz,Yahoo! Bookmarks,Yigg
Requires at least: 2.7
Tested up to: 3.0
Stable tag: 5.01

== Description ==

The famous Sociable plugin now updated and compatible with WordPress 3.0.  Add social bookmarks to posts, pages and RSS feeds. Choose from more than 100 different social bookmarking sites like Digg, Facebook, and del.icio.us, or add your own sites!

= NEW =
* [Sociable Pro](http://wpplugins.com/plugin/155/sociable-pro) is now available on the WPPlugins.com store!  It includes 10 new icon sets in different sizes, custom CSS tooltips and the option to use your own custom icons
* Plurk added
* Orkut and Google Buzz added
* A [sociable] shortcode is now available - see the "Other Notes" section
* Options completely rewritten for WordPress 3.0 including WPMU/multisite installations

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

A [sociable] shortcode is available so you can place the Sociable bookmarks anywhere you like in a post or page.  Just place the shortcode anywhere in your post or page (if the shortcode is present the default icon output will be disabled).

If you include the 'tagline' attribute you can set the tag line as well.  For example:

`This is my post. [sociable tagline='Share it:']`

Just be sure to put the [sociable] tag on its own line - it's not allowed inside a paragraph.

Another option is to turn off the automatic bookmarks display in the Sociable options screen and add calls directly to your theme.  With this approach you can also specify which sites to display:
`
// Show all active sites
<?php global $sociable; echo $sociable->get_links(); ?>

// Show only these two sites if they are active
// Careful: the site names are case-sensitive!  They must
// exactly match the name in the services.php file.
<?php global $sociable; echo $sociable->get_links(Array('Facebook', 'Twitter')); ?>
`

== Frequently Asked Questions ==

= How can I use my own icons? =
Make sure you're using [Sociable Pro](http://wpplugins.com/plugin/155/sociable-pro).  Custom icons aren't supported in the free version.

Pro includes 10 icon sets.  You can find more at the links below or at many other sites:
[http://www.online-blogger.net/2010/02/02/100-social-media-icon-sets/](http://www.online-blogger.net/2010/02/02/100-social-media-icon-sets/)
[http://coderplus.com/blog/2009/11/social-bookmarking-icon-packs/](http://coderplus.com/blog/2009/11/social-bookmarking-icon-packs/)
[http://www.komodomedia.com/download/](http://www.komodomedia.com/download/)

Just put your icons in the directory `/images/custom/size` (where 'size' is the icon size).  For example put your 16x16 icons in `images/custom/size/16`

Your icons will then be available right from the settings screen.

If you have multiple sets of icons and you want to switch between them you can also edit the file 'pro.php' to specify the icon set names and directories.

= How can I add a new site? =
The easiest way is to send it to me for inclusion in the next version of the plugin.  But you can also do it yourself by editing the sites.php file and adding the icon to the images directory.

= How can I change the CSS? =
You can add a 'sociable_custom.css' to the plugin directory and it will be included (along with the original) when the plugin is loaded.  Override the settings you need changed.

= How has Sociable been updated? =
The plugin has been rewritten to fix bugs in past versions and make it WordPress 3.0 and multisite compatible.  It now includes multiple icon sets, shortcodes, theme tags, CSS tooltips and more.

== Changelog ==

= 5.01 =
* Fixed bug in target=blank, should now work correctly
* Fixed directory for 'tydlinka' icons in Pro
* Added links to other icon sets in Pro

= 5.00 =
* Update to Buzz link, bug fixes in shortcode and template tags
* Corrected an XHTML validation error
* Custom images are now only available in the [Sociable Pro](http://wpplugins.com/plugin/155/sociable-pro) version.  See the Frequently Asked Questions section for instructions.

= 4.0.9 =
* Added Orkut, Google Buzz services
* New code for icon paths and urls
* Admin screen bug fix, site order field removed

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
3. Sociable in action
4. [Sociable Pro](http://wpplugins.com/plugin/155/sociable-pro) twitter icons
5. [Sociable Pro](http://wpplugins.com/plugin/155/sociable-pro) tooltips
6. A few of the [Sociable Pro](http://wpplugins.com/plugin/155/sociable-pro) icon sets