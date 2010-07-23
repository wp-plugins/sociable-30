<?php
/*
Plugin Name: Sociable for WordPress 3.0
Plugin URI: http://wordpress.org/extend/plugins/sociable3
Description: Sociable people need sociable!  Sociable now for WordPress 3.0.  Add sociable bookmarks to posts,  pages and RSS feeds
Version: 5.01
Author: Tom Pokress

Copyright 2010 Tom Pokress
Copyright 2009 and earlier Peter Harkins (ph@malaprop.org), blogplay, Joost de Valk (joost@yoast.com)

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
    var $version = '5.02';
    var $bug_link;
    var $pro_link;
    var $pro_button;

    function Sociable() {
	    load_plugin_textdomain('sociable3', false, dirname(plugin_basename( __FILE__ )) . '/i18n');
        register_activation_hook(__FILE__, array(&$this, 'activation_hook'));

        $this->bug_link = "<a href='mailto:tompokress@gmail.com?subject=Bug in $this->version' >" . __('Report a Bug', 'sociable3') . "</a>";
        $this->pro_link = "<a href='http://wpplugins.com/plugin/155/sociable-pro'><img style='vertical-align:middle' src='" . plugins_url('/images/smallpro.png', __FILE__) . "'/></a>";
        $this->pro_button = "<a href='http://wpplugins.com/plugin/155/sociable-pro'><img style='padding-right:25px' src='" . plugins_url('/images/gopro.png', __FILE__) . "'/></a>";

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
        add_action('wp_ajax_sociable_active_sites', array(&$this, 'ajax_active_sites') );

        add_shortcode('sociable', array(&$this, 'shortcode_hook'));

        include_once dirname( __FILE__ ) . '/services.php';

        if (isset($_GET['s3_debug']))
            $this->debug = true;
    }

    /**
     * Set the default settings on activation only if no sites are active
     */
    function activation_hook() {
        $options = get_option('sociable');

        // Current version, set icon info if missing
        if ($options) {
            if (!isset($options['iconset_name']))
                $options['iconset_name'] = 'default';
            if (!isset($options['icon_size']))
                $options['icon_size'] = 16;
        } else {
        // If no options set then upgrade older versions or reset defaults
            $options = $this->defaults();

            // Upgrade to new options
            // Disabled: awesmapikey, iframeheight, iframewidth, usetextlinks, usecss & disableasprite are all deprecated
            if(get_option('sociable_active_sites'))
                $options['active_sites'] = get_option('sociable_active_sites');
            if (get_option('sociable_tagline'))
                $options['tagline'] = get_option('sociable_tagline');
            if (get_option('sociable_disablealpha'))
                $options['disablealpha'] = "on";
            if (get_option('sociable_usetargetblank'))
                $options['usetargetblank'] = "on";

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


    function ajax_active_sites() {
        // Split iconset name and size
        $iconset_name = isset($_POST['iconset_name']) ? $_POST['iconset_name'] : null;
        $active_sites = isset($_POST['active_sites']) ? $_POST['active_sites'] : null;
        $result = explode(":", $iconset_name);
        die ($this->get_active_sites($active_sites, $result[0], $result[1]));
    }

    function wp_print_scripts_hook() {
        $options = get_option('sociable');
        $tooltips = (isset($options['tooltips'])) ? $options['tooltips'] : false;
        $active_sites = isset($options['active_sites']) ? $options['active_sites'] : null;

        if ($active_sites && array_search('Add to favorites', (array)$active_sites) !== false) {
            wp_enqueue_script('sociable3-addtofavorites', plugins_url('addtofavorites.js', __FILE__));
        }

        if ($tooltips)
            wp_enqueue_script('sociable-js', plugins_url('/pro/sociable.js', __FILE__), array('jquery'));

    }

    function wp_print_styles_hook() {
        $options = get_option('sociable');
        wp_enqueue_style('sociable3-front-css', plugins_url('sociable.css', __FILE__));
        wp_enqueue_style('sociable3-front-css', plugins_url('sociable_custom.css', __FILE__));
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

    function admin_print_scripts() {
        wp_enqueue_script('sociable3-js', plugins_url('sociable-admin.js', __FILE__), array('jquery','jquery-ui-core','jquery-ui-sortable', 'jquery-ui-dialog'));
    }

    function admin_print_styles() {
        wp_enqueue_style('sociable3-css', plugins_url('sociable-admin.css', __FILE__));
    }

    function shortcode_hook($atts) {

        $options = get_option('sociable');
        if (isset($options['conditionals']))
            $conditionals = $options['conditionals'];

        extract(shortcode_atts(array(
            'tagline' => null
        ), $atts));

        if ((is_feed() and isset($conditionals['is_feed']))) {
            $html = $this->get_links(null, $tagline);
            $html = strip_tags($html, "<a><img>");
            return $html . "<br/><br/>";
        }

        return $this->get_links($display, $tagline);
    }

    function content_hook($content='') {
        if ($this->debug) {
            echo "<!-- Sociable version $this->version: \r\n";
            $options = get_option('sociable');
            if (isset($options['conditionals']))
                echo "content_hook: conditionals = " . print_r($conditionals, true);
            echo " -->";
        }

        // If no conditionals are set, there's nothing to do
        $options = get_option('sociable');
        if (isset($options['conditionals']))
            $conditionals = $options['conditionals'];
        else
            return $content;

        // Don't output links again if already output by shortcode
        if (stristr($content, '[sociable') !== false)
            return $content;

        if ((is_home()     and isset($conditionals['is_home'])) or
            (is_single()   and isset($conditionals['is_single'])) or
            (is_page()     and isset($conditionals['is_page'])) or
            (is_category() and isset($conditionals['is_category'])) or
            (is_tag()        and isset($conditionals['is_tag'])) or
            (is_date()     and isset($conditionals['is_date'])) or
            (is_author()   and isset($conditionals['is_author'])) or
            (is_search()   and isset($conditionals['is_search']))) {
            $content .= $this->get_links();
        } elseif ((is_feed() and isset($conditionals['is_feed']))) {
            $html = $this->get_links(null, null);
            $html = strip_tags($html, "<a><img>");
            $content .= $html . "<br/><br/>";
        }

        return $content;
    }

    /**
     * Returns the Sociable links list.
     *
     * @param array $display optional array of links to return in HTML
     * @param string tagline optional tagline to override the default
     */
    function get_links($display = null, $tagline = null) {
        global $post;

        // Apply filter for other plugins
        $this->sites = apply_filters('sociable_known_sites',  $this->sites);

        if (get_post_meta($post->ID, '_sociableoff', true))
            return "";

        $options = get_option('sociable');
        $active_sites = isset($options['active_sites']) ? $options['active_sites'] : null;
        $iconurl = $this->get_icon_url();

        if (!$display) {
            if ($active_sites)
                $display = $active_sites;
            else
                return "";
        }

        if (!$tagline)
            $tagline = isset($options['tagline']) ? $options['tagline'] : "";

        if ($tooltips === null)
            $tooltips = isset($options['tooltips']) ? $options['tooltips'] : false;

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

            if ($i == 0)
                $link = '<li class="sociablefirst">';
            else if ($totalsites == ($i+1))
                $link = '<li class="sociablelast">';
            else
                $link = '<li>';

            $link .= $this->show_site($sitename, $iconurl);
            $link .= "</li>";

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
                'is_single' => on,
                'is_page' => on
            ),

            'tagline' => "<strong>" . __("Share and Enjoy:", 'sociable') . "</strong>",
            'disablealpha' => on,
            'iconset_name' => 'default',
            'icon_size' => 16,
        );

        return $defaults;
    }


    function admin_init_hook() {
        register_setting('sociable', 'sociable', array($this, 'set_options'));
        add_settings_section('sociable_settings', __('Settings', 'sociable3'), array(&$this, 'settings'), 'sociable');
        if (method_exists($this, 'set_iconset_name'))
            add_settings_field('iconset_name', __('Icon Set', 'sociable3'), array(&$this, 'set_iconset_name'), 'sociable', 'sociable_settings');
        add_settings_field('active_sites', __('Sites', 'sociable3'), array(&$this, 'set_active_sites'), 'sociable', 'sociable_settings');
        add_settings_field('conditionals', __('Position', 'sociable3'), array(&$this, 'set_conditionals'), 'sociable', 'sociable_settings');
        add_settings_field('tagline', __('Tag Line', 'sociable3'), array(&$this, 'set_tagline'), 'sociable', 'sociable_settings');
        add_settings_field('tooltips', __('CSS Tooltips', 'sociable3'), array(&$this, 'set_tooltips'), 'sociable', 'sociable_settings');
        add_settings_field('captions', __('Captions', 'sociable3'), array(&$this, 'set_captions'), 'sociable', 'sociable_settings');
        add_settings_field('disablealpha', __('Brighter Icons', 'sociable3'), array(&$this, 'set_disablealpha'), 'sociable', 'sociable_settings');
        add_settings_field('usetargetblank', __('New Window', 'sociable3'), array(&$this, 'set_usetargetblank'), 'sociable', 'sociable_settings');
    }


    function set_options($input) {
        // Process restore requests
        if (isset($_REQUEST['restore']))
            return $this->defaults();

        // Split iconset name and size into 2 fields
        if (isset($input['iconset_name'])) {
            $iconset_name = $input['iconset_name'];
            $result = explode(":", $iconset_name);
            $input['iconset_name'] = $result[0];
            $input['icon_size'] = $result[1];
        }

        return $input;
    }

    function settings() {
        // We need an empty function for add_settings_section
    }

    function set_iconset_name() {
        $name = esc_attr($name);
        echo "<select id='sociable_iconset_name' name='sociable[iconset_name]' disabled>";
        echo "<option value='' $selected>Default 16x16</option>";
        echo "</select>";
        echo sprintf(__("Upgrade to %s to get tons of icons in multiple sizes and the ability to use custom icons!", 'sociable3'), $this->pro_link);

    }

    function set_active_sites() {
        _e("Check the sites you want to appear on your site. Drag and drop sites to reorder them.", 'sociable3');
        echo "<div id='sociable_site_list_div'>" . $this->get_active_sites() . "</div>";
    }

    function set_tagline() {
        $options = get_option('sociable');
        $tagline = (isset($options['tagline'])) ? $options['tagline'] : "";

        _e("Change the text displayed in front of the icons below.  You can also create your own css: just create a 'sociable_custom.css' file in the sociable directory.", 'sociable3');
        echo '<br/><input size="80" type="text" name="sociable[tagline]" value="' . esc_attr(stripslashes($tagline)) . '" />';
    }

    function set_tooltips() {
        echo "<input type='checkbox' name='sociable[tooltips]' disabled />";
        echo sprintf(__("Upgrade to %s to get cool CSS tooltips over your icons!", 'sociable3'), $this->pro_link);
    }

    function set_disablealpha() {
        $options = get_option('sociable');
        $checked = (isset($options['disablealpha'])) ? 'checked="checked"' : "";

        echo "<input type='checkbox' name='sociable[disablealpha]' $checked />";
        _e("If checked icons will be always 'on', otherwise they will be dimmed until the mouse moves over them.", 'sociable3');
    }

    function set_usetargetblank() {
        $options = get_option('sociable');
        $checked = (isset($options['usetargetblank'])) ? 'checked="checked"' : "";

        echo "<input type='checkbox' name='sociable[usetargetblank]' $checked />";
        _e("Use <code>target=_blank</code> on links? (Forces links to open a new window)", "sociable3");
    }

    function set_captions() {
        $options = get_option('sociable');
        $checked = (isset($options['captions'])) ? 'checked="checked"' : "";

        echo "<input type='checkbox' name='sociable[captions]' $checked />";
        _e("Show a text caption next to each icon", "sociable3");
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
            <h2>
                <?php _e("Sociable for WordPress 3.0", 'sociable3'); ?>
                <span style='float:right;font-size: 12px'><?php echo __(' Version: ', 'sociable3') . $this->version; ?>
                <?php echo " | " . $this->bug_link ?>
                </span>
            </h2>
            <?php $this->options_header(); ?>
            <form id="sociable_admin_form" action="options.php" method="post">
                <?php settings_fields('sociable'); ?>
                <?php do_settings_sections('sociable'); ?>
                <br/>
                <span class="submit"><input type="submit" name="save" value="<?php _e("Save Changes", 'sociable3'); ?>" /></span>
                <span class="submit"><input name="restore" value="<?php _e("Restore Built-in Defaults", 'sociable3'); ?>" type="submit"/></span>
            </form>
        </div>
    <?php
    }

    function options_header() {
    ?>
            <h3><?php _e('Donate', 'sociable3')?></h3>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                <input type="hidden" name="cmd" value="_s-xclick" />
                <input type="hidden" name="hosted_button_id" value="H3YD2QYUJH8TY" />
                <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
                <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
                <?php echo __("Please support a starving programmer (me) with a small donation!", 'sociable3') ?>
            </form>
            <hr/>
            <h3><?php _e('Go Pro!', 'sociable3')?></h3>
            <table>
                <tr>
                    <th>
                        <?php echo $this->pro_button ?>
                    </th>
                    <td>
                    <ul>
                        <li><img src='<?php echo plugins_url('/images/check.png', __FILE__)?>' />10 Sets of new icons</li>
                        <li><img src='<?php echo plugins_url('/images/check.png', __FILE__)?>' />Use your own icons</li>
                        <li><img src='<?php echo plugins_url('/images/check.png', __FILE__)?>' />Themeable CSS tooltips</li>
                    </ul>
                    </td>
                    <td>
                    <img src='<?php echo plugins_url('/images/banner.png', __FILE__)?>' />
                    </td>
                </tr>
            </table>
            <hr/>

    <?php
    }

    function get_active_sites($active_sites=null, $iconset_name=null, $icon_size=null) {
        $options = get_option('sociable');

        $iconurl = $this->get_icon_url($iconset_name, $icon_size);

        if (!$active_sites)
            $active_sites = (isset($options['active_sites'])) ? (array)$options['active_sites'] : null;

        if (!$icon_size)
            $icon_size = (isset($options['icon_size'])) ? $options['icon_size'] : 16;

        // Apply filter for other plugins
        $this->sites = apply_filters('sociable_known_sites',  $this->sites);
        $sites = $this->sites;
        uksort($sites, "strnatcasecmp");

        $active = array();
        $inactive = $sites;
        foreach( (array)$active_sites as $sitename) {
            if (isset($sites[$sitename]))
                $active[$sitename] = $sites[$sitename];
            unset($inactive[$sitename]);
        }

        $sites = array_merge($active, $inactive);

        $html = __("<b>Icons location</b>: $iconurl<br/>", 'sociable3');
        $html .= "<ul id='sociable_site_list'>";

        foreach ($sites as $sitename => $site) {

            if (!$this->check_icon_exists($site['favicon'], $iconset_name, $icon_size))
                continue;

            if (array_search($sitename, (array)$active_sites) !== false) {
                $class = "sociable_site active";
                $checked = "checked='checked'";
            } else {
                $class = "sociable_site inactive";
                $checked = "";
            }

            $style = "height:" . $icon_size . "px";
            $html .= "<li id='$sitename' class='$class' style='$style' >";
            $html .= "<input type='checkbox' id='cb_$sitename' name='sociable[active_sites][]' value='$sitename' $checked />";
            $html .= $this->show_site($sitename, $iconurl);
            $html .= $sitename;
            $html .= "</li>";
        }
        $html .= "</ul>";
        return $html;
    }

    function show_site($sitename, $iconurl) {
        global $post;

        $options = get_option('sociable');
        $usetargetblank = isset($options['usetargetblank']) ? $options['usetargetblank'] : false;
        $disablealpha = isset($options['disablealpha']) ? $options['disablealpha'] : false;
        $captions = isset($options['captions']) ? $options['captions'] : false;
        $tooltips = isset($options['tooltips']) ? $options['tooltips'] : false;

        // If we can't find an entry in sites array there's nothing to do
        if (!isset($this->sites[$sitename]))
            return "";

        // Special settings for feeds
        if (is_feed()) {
            $disablealpha = false;
            $tooltips = false;
        }

        $site = $this->sites[$sitename];
        $src = $iconurl . $site['favicon'];

        if (isset($site['description']) && $site['description'] != "")
            $description = $site['description'];
        else
            $description = $sitename;

        // Alpha
        if (!$disablealpha)
            $class = "sociable-img sociable-hovers ";
        else
            $class = "sociable-img";

        // Create a span to hold the image and caption together
        $html = "<span class='sociable_outerspan'>";

        // If tooltips enabled, suppress default alt tag
        if ($tooltips) {
            $img = "<img src='$src' class='$class' alt='' />";
        } else {
            $img = "<img src='$src' title='$description' alt='$description' class='$class' />";
        }

        // For admin screen, just return the image
        if (is_admin()) {
            $html .= "</span>";
            return $img;
        }

        // Get post/blog infoa
        $blogname = urlencode(get_bloginfo('name')." ". get_bloginfo('description'));
        $blogrss = get_bloginfo('rss2_url');

        // Grab the excerpt, if there is no excerpt, create one
        $excerpt = urlencode(strip_tags(strip_shortcodes($post->post_excerpt)));
        if ($excerpt == "")
            $excerpt = urlencode(substr(strip_tags(strip_shortcodes($post->post_content)), 0, 250));

        // Clean the excerpt for use with links
        $excerpt = str_replace('+', '%20', $excerpt);
        $permalink = urlencode(get_permalink($post->ID));
        $title = str_replace('+', '%20', urlencode($post->post_title));
        $rss = urlencode(get_bloginfo('ref_url'));

        $url = $site['url'];
        $url = str_replace('TITLE', $title, $url);
        $url = str_replace('RSS', $rss, $url);
        $url = str_replace('BLOGNAME', $blogname, $url);
        $url = str_replace('EXCERPT', $excerpt, $url);
        $url = str_replace('FEEDLINK', $blogrss, $url);
        $url = str_replace('PERMALINK', $permalink, $url);

        $html .= "<a rel='nofollow' ";

        if ($usetargetblank)
            $html .= " target='_blank' ";

        if(!($sitename=="Add to favorites")) {
            $html .= " href='$url' >";
        } else {
            $html .= " href='$url' title='$description''>";
        }

        $html .= "$img";

        if ($captions)
            $html .= "$description";

        $html .= "</a></span>";

        if ($tooltips)
            $html .= "<div class='fg-tooltip'>$description<div class='fg-tooltip-pointer-down'><div class='fg-tooltip-pointer-down-inner'></div></div></div>";

        return $html;
    }

    function get_icon_url($iconset_name=null, $icon_size=null) {
        $options = get_option('sociable');

        $path = $this->get_icon_path($iconset_name, $icon_size);
        return plugins_url($path, __FILE__);
    }

    function get_icon_path($iconset_name=null, $icon_size=null) {
        return '/images/default/16/';
    }

    function check_icon_exists($filename, $iconset_name=null, $icon_size=null) {
        $path = $this->get_icon_path($iconset_name, $icon_size);
        if (!$path)
            return null;

        $path = dirname(__FILE__) . $path . $filename;
        $size = @getimagesize($path);
        if (!isset($size) || $size[0] == 0)
            return null;
        else
            return $size[0];
    }
} // End class Sociable

@include_once dirname( __FILE__ ) . '/pro/pro.php';
if (class_exists('SociablePro'))
    $sociable = new SociablePro();
else
    $sociable = new Sociable();
?>