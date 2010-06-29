<?php
/*
Plugin Name: Sociable for WordPress 3.0
Plugin URI: http://wordpress.org/extend/plugins/sociable3
Description: WordPress 3.0 social bookmarking: add links on your posts,  pages and RSS feeds
Version: 4.0.3
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

    function Sociable() {
	    load_plugin_textdomain('sociable3', false, 'i18n');
        register_activation_hook(__FILE__, array(&$this, 'activation_hook'));

        /**
         * Hook the_content to output html if we should display on any page
         */
        $sociable_contitionals = get_option('sociable_conditionals');
        if (is_array($sociable_contitionals) and in_array(true, $sociable_contitionals)) {
            add_filter('the_content', array(&$this, 'content_hook'));
            add_filter('the_excerpt', array(&$this, 'content_hook'));
        }

        add_action('wp_print_scripts', array(&$this, 'wp_print_scripts_hook'));
        add_action('wp_print_styles', array(&$this, 'wp_print_styles_hook'));
        add_action('wp_insert_post', array(&$this, 'wp_insert_post_hook'));
        add_action('admin_menu', array(&$this, 'admin_menu_hook'));

        include dirname( __FILE__ ) . '/icons.php';
    }

    /**
     * Set the default settings on activation on the plugin.
     */
    function activation_hook() {
    }


    function wp_print_scripts_hook() {
        $active_sites = get_option('sociable_active_sites');
        if ($active_sites) {
            if (in_array('Add to favorites', $active_sites)) {
                wp_enqueue_script('sociable3-addtofavorites', plugins_url('addtofavorites.js', __FILE__));
            }
        }
    }


    /**
     * If the user has the (default) setting of using the Sociable CSS, load it.
     */
    function wp_print_styles_hook() {
        if (get_option('sociable_usecss') == true) {
            wp_enqueue_style('sociable3-front-css',plugins_url('sociable.css', __FILE__));
        }
    }

    /**
     * Add the Sociable menu to the Settings menu
     */
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


    /**
     * Make sure the required javascript files are loaded in the Sociable backend, and that they are only
     * loaded in the Sociable settings page, and nowhere else.
     */
    function admin_print_scripts() {
        wp_enqueue_script('sociable3-js', plugins_url('sociable-admin.js', __FILE__), array('jquery','jquery-ui-core','jquery-ui-sortable'));
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
        if (get_post_meta($post->ID,'_sociableoff', true)) {
            $sociableoff = true;
        }
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

    /**
     * Loop through the settings and check whether Sociable should be outputted.
     */
    function content_hook($content='') {
        $conditionals = get_option('sociable_conditionals');
        if ((is_home()     and $conditionals['is_home']) or
            (is_single()   and $conditionals['is_single']) or
            (is_page()     and $conditionals['is_page']) or
            (is_category() and $conditionals['is_category']) or
            (is_tag()        and $conditionals['is_tag']) or
            (is_date()     and $conditionals['is_date']) or
            (is_author()   and $conditionals['is_author']) or
            (is_search()   and $conditionals['is_search'])) {
            $content .= $this->get_links();
        } elseif ((is_feed() and $conditionals['is_feed'])) {
            $sociable_html = $this->get_links();
            $sociable_html = strip_tags($sociable_html, "<a><img>");
            $content .= $sociable_html . "<br/><br/>";
        }
        return $content;
    }

    /**
     * Returns the Sociable links list.
     *
     * @param array $display optional list of links to return in HTML
     * @global $this->sites array the list of sites that Sociable uses
     * @global $wp_query object the WordPress query object
     * @return string $html HTML for links list.
     */
    function get_links($display=array()) {
	    global $wp_query, $post;

	    if (get_post_meta($post->ID, '_sociableoff', true))
		    return "";

	    /**
	     * Make it possible for other plugins or themes to add buttons to Sociable
	     */
	    $this->sites = apply_filters('sociable_known_sites',  $this->sites);

	    $active_sites = get_option('sociable_active_sites');

	    // If a path is specified where Sociable should find its images, use that, otherwise,
	    // set the image path to the images subdirectory of the Sociable plugin.
	    // Image files need to be png's.
	    $imagepath = get_option('sociable_imagedir');
	    if ($imagepath == "")
		    $imagepath = plugins_url('/images/', __FILE__);

	    // if no sites are specified, display all active
	    // have to check $active_sites has content because WP
	    // won't save an empty array as an option
	    if (empty($display) and $active_sites)
		    $display = $active_sites;
	    // if no sites are active, display nothing
	    if (empty($display))
		    return "";

	    // Load the post's and blog's data
	    $blogname 	= urlencode(get_bloginfo('name')." ".get_bloginfo('description'));
	    $blogrss	= get_bloginfo('rss2_url');
	    $post 		= $wp_query->post;

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
	    $html = "\n<div class=\"sociable\">\n";

	    // If a tagline is set, display it above the links list
	    $tagline = get_option("sociable_tagline");
	    if ($tagline != "") {
		    $html .= "<div class=\"sociable_tagline\">\n";
		    $html .= stripslashes($tagline);
		    $html .= "\n</div>";
	    }

	    /**
	     * Start the list of links
	     */
	    $html .= "\n<ul>\n";

	    $i = 0;
	    $totalsites = count($display);
	    foreach( (array) $display as $sitename) {

		    /**
		     * If they specify an unknown or inactive site, ignore it.
		     */
		    if (!in_array($sitename, $active_sites))
			    continue;

		    $site = $this->sites[$sitename];

		    $url = $site['url'];
		    $url = str_replace('TITLE', $title, $url);
		    $url = str_replace('RSS', $rss, $url);
		    $url = str_replace('BLOGNAME', $blogname, $url);
		    $url = str_replace('EXCERPT', $excerpt, $url);
		    $url = str_replace('FEEDLINK', $blogrss, $url);

		    if (isset($site['description']) && $site['description'] != "") {
			    $description = $site['description'];
		    } else {
			    $description = $sitename;
		    }

		    if (get_option('sociable_awesmenable') == true && !empty($site['awesm_channel']) ) {
			    /**
			     * if awe.sm is enabled and it is an awe.sm supported site, use awe.sm
			     */
			    $permalink = str_replace('&', '%2526', $permalink);
			    $destination = str_replace('PERMALINK', 'TARGET', $url);
			    $destination = str_replace('&amp;', '%26', $destination);
			    $channel = urlencode($site['awesm_channel']);

			    $parentargument = '';
			    if ($_GET['awesm']) {
				    /**
				     * if the page was arrived at through an awe.sm URL, make that the parent
				     */
				    $parent = $_GET['awesm'];
				    $parentargument = '&p=' . $parent;
			    }

			    if (strpos($channel, 'direct') != false) {
				    $url = plugins_url('awesmate.php', __FILE__) . '?c='.$channel.'&t='.$permalink.'&d='.$destination.'&dir=true'.$parentargument;
			    } else {
				    $url = plugins_url('awesmate.php', __FILE__) . '?c='.$channel.'&t='.$permalink.'&d='.$destination.$parentargument;
			    }
		    } else {
			    /**
			     * if awe.sm is not used, simply replace PERMALINK with $permalink
			     */
			    $url = str_replace('PERMALINK', $permalink, $url);
		    }

		    /**
		     * Start building each list item. They're build up separately to allow filtering by other
		     * plugins.
		     * Give the first and last list item in the list an extra class to allow for cool CSS tricks
		     */
		    if ($i == 0) {
			    $link = '<li class="sociablefirst">';
		    } else if ($totalsites == ($i+1)) {
			    $link = '<li class="sociablelast">';
		    } else {
			    $link = '<li>';
		    }

		    /**
		     * Start building the link, nofollow it to make sure Search engines don't follow it,
		     * and optionally add target=_blank to open in a new window if that option is set in the
		     * backend.
		     */
		    $link .= '<a ';
		    $link .= 'rel="nofollow" ';


			if(!($sitename=="Add to favorites")) {
				if (get_option('sociable_usetargetblank')) {
					$link .= " target=\"_blank\"";
				}
				$link .= " href=\"".$url."\" title=\"$description\">";
			} else {
				$link .= " href=\"$url\" title=\"$description\">";
			}

		    /**
		     * If the option to use text links is enabled in the backend, display a text link, otherwise,
		     * display an image.
		     */
		    if (get_option('sociable_usetextlinks')) {
			    $link .= $description;
		    } else {
			    /**
			     * If site doesn't have sprite information
			     */
			    if (!isset($site['spriteCoordinates']) || get_option('sociable_disablesprite') || is_feed()) {
				    if (strpos($site['favicon'], 'http') === 0) {
					    $imgsrc = $site['favicon'];
				    } else {
					    $imgsrc = $imagepath.$site['favicon'];
				    }
				    $link .= "<img src=\"".$imgsrc."\" title=\"$description\" alt=\"$description\"";
				    $link .= (!get_option('sociable_disablealpha',false)) ? " class=\"sociable-hovers" : "";
			    /**
			     * If site has sprite information use it
			     */
			    } else {
				    $imgsrc = $imagepath."services-sprite.gif";
				    $services_sprite_url = $imagepath . "services-sprite.png";
				    $spriteCoords = $site['spriteCoordinates'];
				    $link .= "<img src=\"".$imgsrc."\" title=\"$description\" alt=\"$description\" style=\"width: 16px; height: 16px; background: transparent url($services_sprite_url) no-repeat; background-position:-$spriteCoords[0]px -$spriteCoords[1]px\"";
				    $link .= (!get_option('sociable_disablealpha',false)) ? " class=\"sociable-hovers" : "";
			    }
			    if (isset($site['class']) && $site['class']) {
				    $link .= (!get_option('sociable_disablealpha',false)) ? " sociable_{$site['class']}\"" : " class=\"sociable_{$site['class']}\"";
			    } else {
				    $link .= (!get_option('sociable_disablealpha',false)) ? "\"" : "";
			    }
			    $link .= " />";
		    }
		    $link .= "</a></li>";

		    /**
		     * Add the list item to the output HTML, but allow other plugins to filter the content first.
		     * This is used for instance in the Google Analytics for WordPress plugin to track clicks
		     * on Sociable links.
		     */
		    $html .= "\t".apply_filters('sociable3_link', $link)."\n";
		    $i++;
	    }

	    $html .= "</ul>\n</div>\n";

	    return $html;
    }

    /**
     * Add the Sociable menu to the Settings menu
     * @param boolean $force if set to true, force updates the settings.
     */
    function restore_defaults() {

		update_option('sociable_active_sites', array(
			'Print',
			'Digg',
			'StumbleUpon',
			'del.icio.us',
			'Facebook',
			'YahooBuzz',
            'Twitter',
			'Google'));

		update_option('sociable_tagline', "<strong>" . __("Share and Enjoy:", 'sociable') . "</strong>");

		update_option('sociable_conditionals', array(
			'is_home' => False,
			'is_single' => True,
			'is_page' => True,
			'is_category' => False,
			'is_tag' => False,
			'is_date' => False,
			'is_search' => False,
			'is_author' => False,
			'is_feed' => False,
		));

		update_option('sociable_usecss', true);
		update_option('sociable_disablealpha', true);
		update_option('sociable_disablesprite', false);
        update_option('sociable_usetextlinks', false);
        update_option('sociable_usetargetblank', false);
        update_option('awesmenable', false);
    }

    /**
     * Displays the Sociable admin menu, first section (re)stores the settings.
     */
    function options() {
	    $this->sites = apply_filters('sociable3_known_sites', $this->sites);

	    if (isset($_REQUEST['restore'])) {
		    check_admin_referer('sociable3-config');
		    $this->restore_defaults();
		    $message = __("Restored all settings to defaults.", 'sociable3');
            echo "<div id=\"message\" class=\"updated fade\"><p>$message</p></div>\n";
	    } else if (isset($_REQUEST['save']) && $_REQUEST['save']) {
		    check_admin_referer('sociable3-config');
		    $active_sites = Array();
		    if (!isset($_REQUEST['active_sites']))
			    $_REQUEST['active_sites'] = Array();
		    foreach( (array) $_REQUEST['active_sites'] as $sitename=>$dummy)
			    $active_sites[] = $sitename;
		    update_option('sociable_active_sites', $active_sites);
		    /**
		     * Have to delete and re-add because update doesn't hit the db for identical arrays
		     * (sorting does not influence associated array equality in PHP)
		     */
		    delete_option('sociable_active_sites', $active_sites);
		    add_option('sociable_active_sites', $active_sites);

		    foreach ( array('usetargetblank', 'disablealpha', 'disablesprite', 'awesmenable', 'usecss', 'usetextlinks') as $val ) {
			    if ( isset($_POST[$val]) && $_POST[$val] )
				    update_option('sociable_'.$val,true);
			    else
				    update_option('sociable_'.$val,false);
		    }

		    foreach ( array('awesmapikey', 'tagline', 'imagedir') as $val ) {
			    if ( !$_POST[$val] )
				    update_option( 'sociable_'.$val, '');
			    else
				    update_option( 'sociable_'.$val, $_POST[$val] );
		    }

		    if (isset($_POST["imagedir"]) && !trim($_POST["imagedir"]) == "") {
			    update_option('sociable_disablesprite',true);
		    }

		    /**
		     * Update conditional displays
		     */
		    $conditionals = Array();
		    if (!$_POST['conditionals'])
			    $_POST['conditionals'] = Array();

		    $curconditionals = get_option('sociable_conditionals');
            if (isset($curconditionals) && !array_key_exists('is_feed',$curconditionals)) {
			    $curconditionals['is_feed'] = false;
		    }
		    foreach( (array) $curconditionals as $condition=>$toggled)
			    $conditionals[$condition] = array_key_exists($condition, $_POST['conditionals']);

		    update_option('sociable_conditionals', $conditionals);

		    $message = __("Saved changes.", 'sociable3');
            echo "<div id=\"message\" class=\"updated fade\"><p>$message</p></div>\n";
	    }

	    /**
	     * Show active sites first and in the right order.
	     */
	    $active_sites = get_option('sociable_active_sites');
	    $active = Array();
	    $disabled = $this->sites;
	    foreach( (array) $active_sites as $sitename ) {
		    $active[$sitename] = $disabled[$sitename];
		    unset($disabled[$sitename]);
	    }
	    uksort($disabled, "strnatcasecmp");

    ?>
    <div class="wrap">
	    <?php screen_icon(); ?>
	    <h2><?php _e("Sociable for WordPress 3.0", 'sociable3'); ?></h2>
	    <table class="form-table">
            <tr>
                <th>
                    <?php _e("Support", "sociable3"); ?>
                </th>
                <td>
                    <h3><?php _e("Please - support a starving programmer (me) with a $0.99 donation!", "sociable3"); ?></h3>
                    <br/>
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                    <input type="hidden" name="cmd" value="_s-xclick">
                    <input type="hidden" name="hosted_button_id" value="H3YD2QYUJH8TY">
                    <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                    <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
                    </form>

                </td>
            </tr>
        </table>

        <form action="" method="post">
            <table class="form-table">
                <?php wp_nonce_field('sociable3-config'); ?>
                <tr>
                    <th>
                        <?php _e("Bugs?  Problems?", "sociable3"); ?>
                    </th>
                    <td>
                        <?php
                            $email = "<a href='mailto:tompokress@gmail.com'>" . __("Send me an email", "sociable3") . "</a>";
                            echo "<b>" . __("I'm here to help!", "sociable3") . " " . $email . "</b>";
                         ?>
                    </td>
                </tr>
	            <tr>
		            <th>
			            <?php _e("Sites", "sociable3"); ?>:<br/>
			            <small><?php _e("Check the sites you want to appear on your site. Drag and drop sites to reorder them.", 'sociable3'); ?></small>
		            </th>
		            <td>
			            <div style="width: 100%; height: 100%">
			            <ul id="sociable_site_list">
				            <?php foreach (array_merge($active, $disabled) as $sitename=>$site) { ?>
					            <li id="<?php echo $sitename; ?>"
						            class="sociable_site <?php echo (in_array($sitename, $active_sites)) ? "active" : "inactive"; ?>">
						            <input
							            type="checkbox"
							            id="cb_<?php echo $sitename; ?>"
							            name="active_sites[<?php echo $sitename; ?>]"
							            <?php echo (in_array($sitename, $active_sites)) ? ' checked="checked"' : ''; ?>
						            />
						            <?php
						            $imagepath = get_option('sociable_imagedir');

						            if ($imagepath == "") {
							            $imagepath = plugins_url('/images/', __FILE__);
						            } else {
							            $imagepath .= (substr($imagepath,strlen($imagepath)-1,1)=="/") ? "" : "/";
						            }

						            if (!isset($site['spriteCoordinates']) || get_option('sociable_disablesprite')) {
							            if (strpos($site['favicon'], 'http') === 0) {
								            $imgsrc = $site['favicon'];
							            } else {
								            $imgsrc = $imagepath.$site['favicon'];
							            }
							            echo "<img src=\"$imgsrc\" width=\"16\" height=\"16\" />";
						            } else {
							            $imgsrc = $imagepath."services-sprite.gif";
							            $services_sprite_url = $imagepath . "services-sprite.png";
							            $spriteCoords = $site['spriteCoordinates'];
							            echo "<img src=\"$imgsrc\" width=\"16\" height=\"16\" style=\"background: transparent url($services_sprite_url) no-repeat; background-position:-$spriteCoords[0]px -$spriteCoords[1]px\" />";
						            }

						            echo $sitename; ?>
					            </li>
				            <?php } ?>
			            </ul>
			            </div>
			            <input type="hidden" id="site_order" name="site_order" value="<?php echo join('|', array_keys($this->sites)) ?>" />
		            </td>
	            </tr>
	            <tr>
		            <th scope="row" valign="top">
			            <?php _e("Disable sprite usage for images?", "sociable3"); ?>
		            </th>
		            <td>
			            <input type="checkbox" name="disablesprite" <?php checked( get_option('sociable_disablesprite'), true ) ; ?> />
		            </td>
	            </tr>
	            <tr>
		            <th scope="row" valign="top">
			            <?php _e("Disable alpha mask on share toolbar?", "sociable3"); ?>
		            </th>
		            <td>
			            <input type="checkbox" name="disablealpha" <?php checked( get_option('sociable_disablealpha'), true ) ; ?> />
		            </td>
	            </tr>
	            <tr>
		            <th scope="row" valign="top">
			            <?php _e("Tagline", "sociable3"); ?>
		            </th>
		            <td>
			            <?php _e("Change the text displayed in front of the icons below. For complete customization, copy the contents of <em>sociable.css</em> in the Sociable plugin directory to your theme's <em>style.css</em> and disable the use of the sociable stylesheet below.", 'sociable3'); ?><br/>
			            <input size="80" type="text" name="tagline" value="<?php echo esc_attr(stripslashes(get_option('sociable_tagline'))); ?>" />
		            </td>
	            </tr>
	            <tr>
		            <th scope="row" valign="top">
			            <?php _e("Position:", "sociable3"); ?>
		            </th>
		            <td>
			            <?php _e("The icons appear at the end of each blog post, and posts may show on many different types of pages. Depending on your theme and audience, it may be tacky to display icons on all types of pages.", 'sociable3'); ?><br/>
			            <br/>
			            <?php
			            /**
			             * Load conditions under which Sociable displays
			             */
			            $conditionals 	= get_option('sociable_conditionals');
			            ?>
			            <input type="checkbox" name="conditionals[is_home]"<?php checked($conditionals['is_home']); ?> /> <?php _e("Front page of the blog", 'sociable3'); ?><br/>
			            <input type="checkbox" name="conditionals[is_single]"<?php checked($conditionals['is_single']); ?> /> <?php _e("Individual blog posts", 'sociable3'); ?><br/>
			            <input type="checkbox" name="conditionals[is_page]"<?php checked($conditionals['is_page']); ?> /> <?php _e('Individual WordPress "Pages"', 'sociable3'); ?><br/>
			            <input type="checkbox" name="conditionals[is_category]"<?php checked($conditionals['is_category']); ?> /> <?php _e("Category archives", 'sociable3'); ?><br/>
			            <input type="checkbox" name="conditionals[is_tag]"<?php checked($conditionals['is_tag']); ?> /> <?php _e("Tag listings", 'sociable3'); ?><br/>
			            <input type="checkbox" name="conditionals[is_date]"<?php checked($conditionals['is_date']); ?> /> <?php _e("Date-based archives", 'sociable3'); ?><br/>
			            <input type="checkbox" name="conditionals[is_author]"<?php checked($conditionals['is_author']); ?> /> <?php _e("Author archives", 'sociable3'); ?><br/>
			            <input type="checkbox" name="conditionals[is_search]"<?php checked($conditionals['is_search']); ?> /> <?php _e("Search results", 'sociable3'); ?><br/>
			            <input type="checkbox" name="conditionals[is_feed]"<?php checked($conditionals['is_feed']); ?> /> <?php _e("RSS feed items", 'sociable3'); ?><br/>
		            </td>
	            </tr>
	            <tr>
		            <th scope="row" valign="top">
			            <?php _e("Use CSS:", "sociable3"); ?>
		            </th>
		            <td>
			            <input type="checkbox" name="usecss" <?php checked( get_option('sociable_usecss'), true ); ?> /> <?php _e("Use the sociable stylesheet?", "sociable3"); ?>
		            </td>
	            </tr>
	            <tr>
		            <th scope="row" valign="top">
			            <?php _e("Use Text Links:", "sociable3"); ?>
		            </th>
		            <td>
			            <input type="checkbox" name="usetextlinks" <?php checked( get_option('sociable_usetextlinks'), true ); ?> /> <?php _e("Use text links without images?", "sociable3"); ?>
		            </td>
	            </tr>
	            <tr>
		            <th scope="row" valign="top">
			            <?php _e("Image directory", "sociable3"); ?>
		            </th>
		            <td>
			            <?php _e("Sociable comes with a nice set of images, if you want to replace those with your own, enter the URL where you've put them in here, and make sure they have the same name as the ones that come with Sociable.", 'sociable3'); ?><br/>
			            <input size="80" type="text" name="imagedir" value="<?php echo esc_attr(stripslashes(get_option('sociable_imagedir'))); ?>" /><br />
			            (automatically disables sprite usage)
		            </td>
	            </tr>
	            <tr>
		            <th scope="row" valign="top">
			            <?php _e("Open in new window:", "sociable3"); ?>
		            </th>
		            <td>
			            <input type="checkbox" name="usetargetblank" <?php checked( get_option('sociable_usetargetblank'), true ); ?> /> <?php _e("Use <code>target=_blank</code> on links? (Forces links to open a new window)", "sociable3"); ?>
		            </td>
	            </tr>
	            <tr>
		            <th scope="row" valign="top">
			            <?php _e("awe.sm:", "sociable3"); ?>
		            </th>
		            <td>
			            <?php _e("You can choose to automatically have the links posted to certain sites shortened via awe.sm and encoded with the channel info and your API Key.", 'sociable3'); ?><br/>
			            <input type="checkbox" name="awesmenable" <?php checked( get_option('sociable_awesmenable'), true ); ?> /> <?php _e("Enable awe.sm URLs?", "sociable3"); ?><br/>
			            <?php _e("awe.sm API Key:", 'sociable3'); ?> <input size="65" type="text" name="awesmapikey" value="<?php echo get_option('sociable_awesmapikey'); ?>" />
		            </td>
	            </tr>
	            <tr>
		            <td>&nbsp;</td>
		            <td>
			            <span class="submit"><input name="save" value="<?php _e("Save Changes", 'sociable3'); ?>" type="submit" /></span>
			            <span class="submit"><input name="restore" value="<?php _e("Restore Built-in Defaults", 'sociable3'); ?>" type="submit"/></span>
		            </td>
	            </tr>
            </table>
        </form>
    </div>
    <?php
    }
} // End class Sociable

$sociable = new Sociable();
?>