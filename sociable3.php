<?php
/*
Plugin Name: Sociable for WordPress 3.0
Plugin URI: http://wordpress.org/extend/plugins/sociable3
Description: Sociable people need sociable!  Sociable now for WordPress 3.0.  Add sociable bookmarks to posts,  pages and RSS feeds
Version: 4.0.7
Author: Tom Pokress

Copyright 2010-present Tom Pokress
Copyright Peter Harkins (ph@malaprop.org), Joost de Valk (joost@yoast.com), blogplay(info@blogply.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/



class Sociable {

    var $debug;

    function Sociable() {
	    load_plugin_textdomain('sociable3', false, dirname(plugin_basename( __FILE__ )) . '/i18n');
        register_activation_hook(__FILE__, array(&$this, 'activation_hook'));

        $options = get_option('sociable');
        if (isset($options['conditionals'])) {
            add_filter('the_content', array(&$this, 'content_hook'));
            add_filter('the_excerpt', array(&$this, 'content_hook'));
        }

        add_action('wp_print_scripts', array(&$this, 'wp_print_scripts_hook'));
        add_action('wp_print_styles', array(&$this, 'wp_print_styles_hook'));
        add_action('wp_insert_post', array(&$this, 'wp_insert_post_hook'));
        add_action('admin_menu', array(&$this, 'admin_menu_hook'));
        add_action('admin_init', array(&$this, 'admin_init_hook'));

        include dirname( __FILE__ ) . '/icons.php';

        if (isset($_GET['s3_debug']))
            $this->debug = true;
    }

    /**
     * Set the default settings on activation only if no sites are active
     */
    function activation_hook() {
        // If no options set then upgrade older versions or reset defaults
        if (get_option('sociable') == false) {
            $options = $this->defaults();

            // Upgrade to new options
            // Disabled: awesmapikey, iframeheight, iframewidth, usetextlinks, usecss & disableasprite are all deprecated
            if(get_option('sociable_active_sites'))
                $options['active_sites'] = get_option('sociable_active_sites');
            if (get_option('sociable_tagline'))
                $options['tagline'] = get_option('sociable_tagline');
            if (get_option('sociable_imagedir'))
                $options['imagedir'] = get_option('sociable_imagedir');
            if (get_option('sociable_disablealpha'))
                $options['disablealpha'] = get_option('sociable_disablealpha');
            if (get_option('sociable_usetargetblank'))
                $options['usetargetblank'] = get_option('sociable_usetargetblank');

            if (get_option('sociable_conditionals')) {
                unset($options['conditionals']);
                $conditionals = get_option('sociable_conditionals');
                foreach((array)$conditionals as $condition => $value) {
                    if ($value)
                        $options['conditionals'][$condition] = "on";
                }
            }

            update_option('sociable', $options);
        }
    }


    function wp_print_scripts_hook() {
        $options = get_option('sociable');
        $active_sites = isset($options['active_sites']) ? $options['active_sites'] : null;

        if ($active_sites && array_search('Add to favorites', (array)$active_sites) !== false) {
                wp_enqueue_script('sociable3-addtofavorites', plugins_url('addtofavorites.js', __FILE__));
        }
    }

    function wp_print_styles_hook() {
        $options = get_option('sociable');
        wp_enqueue_style('sociable3-front-css', plugins_url('sociable.css', __FILE__));
        wp_enqueue_style('sociable3-front-css', plugins_url('sociable_custom.css', __FILE__));
    }

    function admin_menu_hook() {
        $pages[] = add_options_page('Sociable WP3', 'Sociable WP3', 'manage_options', 'sociablewp3', array(&$this, 'options'));

        // Load scripts/styles for plugin pages only
        foreach ( (array) $pages as $page) {
            add_action('admin_print_scripts-' . $page, array(&$this, 'admin_print_scripts'));
            add_action('admin_print_styles-' . $page, array(&$this, 'admin_print_styles'));
        }

        add_meta_box('sociablewp3', 'Sociable WP3', array(&$this, 'meta_box_hook'), 'post', 'side');
        add_meta_box('sociablewp3', 'Sociable WP3', array(&$this, 'meta_box_hook'), 'page','side');
    }

    function admin_init_hook() {
        register_setting('sociable', 'sociable', array($this, 'set_options'));
        add_settings_section('sociable_settings', __('Basic Settings', 'sociable3'), array(&$this, 'settings_basic'), 'sociable');

        add_settings_field('active_sites', __('Sites', 'sociable3'), array(&$this, 'set_active_sites'), 'sociable', 'sociable_settings');
        add_settings_field('conditionals', __('Position', 'sociable3'), array(&$this, 'set_conditionals'), 'sociable', 'sociable_settings');
        add_settings_field('tagline', __('Tag Line', 'sociable3'), array(&$this, 'set_tagline'), 'sociable', 'sociable_settings');
        add_settings_field('imagedir', __('Image directory', 'sociable3'), array(&$this, 'set_imagedir'), 'sociable', 'sociable_settings');
        add_settings_field('disablealpha', __('Brighter Icons', 'sociable3'), array(&$this, 'set_disablealpha'), 'sociable', 'sociable_settings');
        add_settings_field('usetargetblank', __('New Window', 'sociable3'), array(&$this, 'set_usetargetblank'), 'sociable', 'sociable_settings');
    }

    function set_options($input) {
        // Process restore requests
        if (isset($_REQUEST['restore'])) {
            return $this->defaults();
        }
        return $input;
    }

    function set_imagedir($input) {
        $options = get_option('sociable');
        $imagedir = (isset($options['imagedir'])) ? $options['imagedir'] : "";

        echo _e("Sociable comes with a nice set of images, if you want to replace those with your own, enter the URL where you've put them in here, and make sure they have the same name as the ones that come with Sociable.", 'sociable3');
        echo "<input size='80' type='text' name='sociable[imagedir]' value='" . esc_attr(stripslashes($imagedir)) . "' /><br />";
    }

    // Not used
    function set_disablesprite() {
        $options = get_option('sociable');
        $checked = (isset($options['disablesprite'])) ? 'checked="checked"' : "";

        echo "<input type='checkbox' name='sociable[disablesprite] $checked' />";
        _e("Disable the icon background image?", "sociable3");
    }

    function set_active_sites() {
        $options = get_option('sociable');
        $active_sites = (isset($options['active_sites'])) ? (array)$options['active_sites'] : null;
        $imagepath = (isset($options['imagedir'])) ? $options['imagedir'] : null;

        $sites = apply_filters('sociable_known_sites', $this->sites);
        uksort($sites, "strnatcasecmp");

        $active = array();
        $inactive = $sites;
        foreach( (array)$active_sites as $sitename) {
            if (isset($sites[$sitename]))
                $active[$sitename] = $sites[$sitename];
            unset($inactive[$sitename]);
        }

        $sites = array_merge($active, $inactive);

        _e("Check the sites you want to appear on your site. Drag and drop sites to reorder them.", 'sociable3');
        echo "<div><ul id='sociable_site_list'>";
        foreach ($sites as $sitename => $site) {
            if (array_search($sitename, (array)$active_sites) !== false) {
                $class = "sociable_site active";
                $checked = "checked='checked'";
            } else {
                $class = "sociable_site inactive";
                $checked = "";
            }

            echo "<li id='$sitename' class='$class' />";
//            echo "<input type='checkbox' id='cb_$sitename name='sociable[active_sites][$sitename]' $checked />";
            echo "<input type='checkbox' id='cb_$sitename' name='sociable[active_sites][]' value='$sitename' $checked />";
            echo $this->show_site($sitename, $site, $imagepath, false, true);
            echo $sitename;
            echo "</li>";
            echo "<input type='hidden' id='site_order' name='site_order' value='" . join('|', array_keys($this->sites)) . "' />";
        }
        echo "</ul></div>";
    }

    function show_site($sitename, $site, $imagepath=null, $disablesprite=false, $disablealpha=true) {
        if ($imagepath == "")
            $imagepath = plugins_url('/images/', __FILE__);
        else
            $imagepath .= (substr($imagepath, strlen($imagepath)-1, 1) == "/") ? "" : "/";

        if (isset($site['description']) && $site['description'] != "")
            $description = $site['description'];
        else
            $description = $sitename;

        if (!$disablealpha)
            $class = "sociable-hovers ";
        else
            $class = "";

        if (isset($site['class']))
            $class .= "sociable_" . $site['class'];

        if (strpos($site['favicon'], 'http') === 0)
            $imgsrc = $site['favicon'];
        else
            $imgsrc = $imagepath . $site['favicon'];

        return "<img src='$imgsrc' title='$description' alt='$description' style='width:16px; height:16px' class='$class' />";
    }

    function set_tagline() {
        $options = get_option('sociable');
        $tagline = (isset($options['tagline'])) ? $options['tagline'] : "";

        _e("Change the text displayed in front of the icons below.  You can also create your own css: just create a 'sociable_custom.css' file in the sociable directory.", 'sociable3');
        echo '<br/><input size="80" type="text" name="sociable[tagline]" value="' . esc_attr(stripslashes($tagline)) . '" />';
    }

    function set_disablealpha() {
        $options = get_option('sociable');
        $checked = (isset($options['disablealpha'])) ? 'checked="checked"' : "";

        echo "<input type='checkbox' name='sociable[disablealpha]' $checked />";
        _e("If unchecked, icons will be dimmed until the mouse moves over them.", 'sociable3');
    }

    function set_usetextlinks() {
        $options = get_option('sociable');
        $checked = (isset($options['usetextlinks'])) ? 'checked="checked"' : "";

        echo "<input type='checkbox' name='sociable[usetextlinks]' $checked />";
        _e("Use text links without images?", "sociable3");
    }

    function set_usetargetblank() {
        $options = get_option('sociable');
        $checked = (isset($options['usetargetblank'])) ? 'checked="checked"' : "";

        echo "<input type='checkbox' name='sociable[usetargetblank]' $checked />";
        _e("Use <code>target=_blank</code> on links? (Forces links to open a new window)", "sociable3");
    }

    function set_conditionals() {
        $options = get_option('sociable');
        $conditionals = (isset($options['conditionals'])) ? $options['conditionals'] : array();

        _e('Specify which pages to display social bookmarks on:', 'sociable3');
        echo "<br/>";

        $this->option_conditional($conditionals, 'is_home', __("Front page of the blog", 'sociable3'));
        $this->option_conditional($conditionals, 'is_single', __("Individual blog posts", 'sociable3'));
        $this->option_conditional($conditionals, 'is_page', __('Individual WordPress "Pages"', 'sociable3'));
        $this->option_conditional($conditionals, 'is_category', __("Category archives", 'sociable3'));
        $this->option_conditional($conditionals, 'is_tag', __("Tag listings", 'sociable3'));
        $this->option_conditional($conditionals, 'is_date', __("Date-based archives", 'sociable3'));
        $this->option_conditional($conditionals, 'is_author', __("Author archives", 'sociable3'));
        $this->option_conditional($conditionals, 'is_search', __("Search results", 'sociable3'));
        $this->option_conditional($conditionals, 'is_feed', __("RSS feed items", 'sociable3'));
    }

    function option_conditional($conditionals, $field, $label) {
        $checked = (isset($conditionals[$field])) ? checked($conditionals[$field], "on", false) : "";
        echo "<input type='checkbox' name='sociable[conditionals][$field]' $checked /> $label <br/>";
    }

    function options() {
    ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e("Sociable for WordPress 3.0", 'sociable3'); ?></h2>

            <hr/>
            <h4><?php _e("Please - support a starving programmer (me) with a $0.99 donation!", "sociable3"); ?></h4>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="hosted_button_id" value="H3YD2QYUJH8TY">
            <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
            <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
            </form>

            <br/>
            <?php _e("Got a <b>great idea</b> for the next Sociable?  Let me know: ", "sociable3"); ?>
            <input type="button" class="button-primary" id="button_s3poll" value="<?php _e('Take the poll', 'sociable3')?>" />

            <div id="s3poll" style="background-color: white">
                <script type="text/javascript" charset="utf-8" src="http://static.polldaddy.com/p/3409762.js"></script>
                <noscript>
                    <a href="http://polldaddy.com/poll/3409762/">What would you like to see in the next version of Sociable?</a><span style="font-size:9px;"><a href="http://polldaddy.com/features-surveys/">survey software</a></span>
                </noscript>
            </div>

            <script type='text/javascript'>
            /* <![CDATA[ */
                jQuery('#s3poll').dialog({autoOpen : false, width : 500});
                jQuery('#button_s3poll').click(function() {
                    jQuery('#s3poll').dialog('open');
                });
            /* ]]> */
            </script>
            <hr/>

            <form action="options.php" method="post">
                <?php settings_fields('sociable'); ?>
                <?php do_settings_sections('sociable'); ?>
                <br/>
                <span class="submit"><input type="submit" name="save" value="<?php _e("Save Changes", 'sociable3'); ?>" /></span>
                <span class="submit"><input name="restore" value="<?php _e("Restore Built-in Defaults", 'sociable3'); ?>" type="submit"/></span>
            </form>
        </div>
    <?php
    }


    function settings_basic() {
    }

    /**
     * Make sure the required javascript files are loaded in the Sociable backend, and that they are only
     * loaded in the Sociable settings page, and nowhere else.
     */
    function admin_print_scripts() {
        wp_enqueue_script('sociable3-js', plugins_url('sociable-admin.js', __FILE__), array('jquery','jquery-ui-core','jquery-ui-sortable', 'jquery-ui-dialog'));
    }

    /**
     * Make sure the required stylesheet is loaded in the Sociable backend, and that it is only
     * loaded in the Sociable settings page, and nowhere else.
     */
    function admin_print_styles() {
        wp_enqueue_style('sociable3-css', plugins_url('sociable-admin.css', __FILE__));
    }

    /**
     * Displays a checkbox that allows users to disable Sociable on a
     * per post or page basis.
     */
    function meta_box_hook($post) {
        $sociableoff = false;
        if (get_post_meta($post->ID,'_sociableoff', true))
            $sociableoff = true;
        ?>
        <input type="checkbox" id="sociableoff" name="sociableoff" <?php checked($sociableoff); ?>/> <label for="sociableoff"><?php _e('Sociable disabled?','sociable') ?></label>
        <?php
    }

    /**
     * If the post is inserted, set the appropriate state for the sociable off setting.
     */
    function wp_insert_post_hook($post_id) {
        if (isset($_POST['sociableoff'])) {
            if (!get_post_meta($post_id, '_sociableoff', true))
                add_post_meta($post_id, '_sociableoff', true, true);
        } else {
            if (get_post_meta($post_id, '_sociableoff', true))
                delete_post_meta($post_id, '_sociableoff');
        }
    }

    function content_hook($content='') {
        $options = get_option('sociable');
        if (isset($options['conditionals']))
            $conditionals = $options['conditionals'];
        else
            return $content;

        if ($this->debug)
            echo "<!-- Sociable: content_hook: content = " . $content . ", conditionals = " . print_r($conditionals, true) . " -->";

        if ((is_home()     and isset($conditionals['is_home'])) or
            (is_single()   and isset($conditionals['is_single'])) or
            (is_page()     and isset($conditionals['is_page'])) or
            (is_category() and isset($conditionals['is_category'])) or
            (is_tag()        and isset($conditionals['is_tag'])) or
            (is_date()     and isset($conditionals['is_date'])) or
            (is_author()   and isset($conditionals['is_author'])) or
            (is_search()   and isset($conditionals['is_search']))) {
            $content .= $this->get_links();
        } elseif ((is_feed() and $conditionals['is_feed'])) {
            $html = $this->get_links();
            $$html = strip_tags($html, "<a><img>");
            $content .= $html . "<br/><br/>";
        }

        return $content;
    }

    /**
     * Returns the Sociable links list.
     *
     * @param array $display optional array of links to return in HTML
     */
    function get_links($display = null) {
	    global $post;

	    if (get_post_meta($post->ID, '_sociableoff', true))
		    return "";

        $options = get_option('sociable');
        $active_sites = isset($options['active_sites']) ? $options['active_sites'] : null;
        $imagepath = isset($options['imagedir']) ? $options['imagedir'] : plugins_url('/images/', __FILE__);
        $tagline = isset($options['tagline']) ? $options['tagline'] : "";
        $usetargetblank = isset($options['usetargetblank']) ? $options['usetargetblank'] : false;
        $usetextlinks = isset($options['usetextlinks']) ? $options['usetextlinks'] : false;
        $disablesprite = isset($options['disablesprite']) ? $options['disablesprite'] : false;
        $disablealpha = isset($options['disablealpha']) ? $options['disablealpha'] : false;

        if (!$display) {
            if ($active_sites)
                $display = $active_sites;
            else
                return "";
        }

	    /**
	     * Make it possible for other plugins or themes to add buttons to Sociable
	     */
	    $this->sites = apply_filters('sociable_known_sites',  $this->sites);

	    // Load the post's and blog's data
	    $blogname 	= urlencode(get_bloginfo('name')." ". get_bloginfo('description'));
	    $blogrss	= get_bloginfo('rss2_url');

	    // Grab the excerpt, if there is no excerpt, create one
	    $excerpt	= urlencode(strip_tags(strip_shortcodes($post->post_excerpt)));
	    if ($excerpt == "")
		    $excerpt = urlencode(substr(strip_tags(strip_shortcodes($post->post_content)), 0, 250));

	    // Clean the excerpt for use with links
	    $excerpt	= str_replace('+', '%20', $excerpt);
	    $permalink 	= urlencode(get_permalink($post->ID));
	    $title 		= str_replace('+', '%20', urlencode($post->post_title));
	    $rss 		= urlencode(get_bloginfo('ref_url'));

	    // Start preparing the output
	    $html = '<div class="sociable">';

	    // If a tagline is set, display it above the links list
	    if ($tagline) {
		    $html .= "<div class='sociable_tagline'>\n";
		    $html .= stripslashes($tagline);
		    $html .= "\n</div>";
	    }

	    /**
	     * Start the list of links
	     */
	    $html .= "\n<ul>\n";

	    $i = 0;
	    $totalsites = count($display);
	    foreach( (array)$display as $sitename ) {

		    if (!isset($this->sites[$sitename]))
                continue;

		    $site = $this->sites[$sitename];

		    $url = $site['url'];
		    $url = str_replace('TITLE', $title, $url);
		    $url = str_replace('RSS', $rss, $url);
		    $url = str_replace('BLOGNAME', $blogname, $url);
		    $url = str_replace('EXCERPT', $excerpt, $url);
		    $url = str_replace('FEEDLINK', $blogrss, $url);
			$url = str_replace('PERMALINK', $permalink, $url);

            if (isset($site['description']) && $site['description'] != "") {
                $description = $site['description'];
            } else {
                $description = $sitename;
            }

		    /**
		     * Start building each list item. They're build up separately to allow filtering by other
		     * plugins.
		     * Give the first and last list item in the list an extra class to allow for cool CSS tricks
		     */
		    if ($i == 0)
			    $link = '<li class="sociablefirst">';
		    else if ($totalsites == ($i+1))
			    $link = '<li class="sociablelast">';
		    else
			    $link = '<li>';

		    /**
		     * Start building the link, nofollow it to make sure Search engines don't follow it,
		     * and optionally add target=_blank to open in a new window if that option is set in the
		     * backend.
		     */
		    $link .= '<a ';
		    $link .= 'rel="nofollow" ';

			if(!($sitename=="Add to favorites")) {
				if ($usetargetblank)
					$link .= " target=\"_blank\"";
				$link .= " href=\"".$url."\" title=\"$description\">";
			} else {
				$link .= " href=\"$url\" title=\"$description\">";
			}

		    /**
		     * If the option to use text links is enabled in the backend, display a text link, otherwise,
		     * display an image.
		     */
		    if ($usetextlinks)
			    $link .= $description;
		    else
                $link .= $this->show_site($sitename, $site, $imagepath, $disablesprite, $disablealpha);

		    $link .= "</a></li>";

		    /**
		     * Add the list item to the output HTML, but allow other plugins to filter the content first.
		     * This is used for instance in the Google Analytics for WordPress plugin to track clicks
		     * on Sociable links.
		     */
		    $html .= "\t" . apply_filters('sociable_link', $link) . "\n";
	    }

	    $html .= "</ul>\n</div>\n";

	    return $html;
    }

    /**
     * Add the Sociable menu to the Settings menu
     * @param boolean $force if set to true, force updates the settings.
     */
    function defaults() {
        $defaults = array(
		    'active_sites' => array('Print', 'Digg', 'StumbleUpon', 'del.icio.us', 'Facebook', 'YahooBuzz', 'Twitter', 'Google'),

            'conditionals' => array(
                'is_single' => true,
                'is_page' => true
            ),

            'tagline' => "<strong>" . __("Share and Enjoy:", 'sociable') . "</strong>",
		    'disablealpha' => true,
            'usetargetblank' => false
        );

        return $defaults;
    }

    function option_checked( $helper, $current=true, $echo=true, $type='checked' ) {
        if ( (string) $helper === (string) $current )
            $result = " $type='$type'";
        else
            $result = '';

        if ( $echo )
            echo $result;

        return $result;
    }
} // End class Sociable

$sociable = new Sociable();
?>