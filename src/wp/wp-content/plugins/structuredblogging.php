<?php
/*
Plugin Name: Structured Blogging
Plugin URI: http://www.structuredblogging.org
Description: Structured Blogging plugin for WP &gt;= 1.5.  Use this plugin to publish many different types of microcontent.  For more information on structured blogging, visit <a href='http://www.structuredblogging.org'>http://www.structuredblogging.org</a>.
Author: Broadband Mechanics (Phillip Pearson, Kimbro Staken, Marc Senasac and Marc Canter)
Version: ##VERSION##
Author URI: http://www.broadbandmechanics.com/
*/ 

/*
 * WP-SB version ##VERSION##, built ##DATE##
 *
 * Copyright (c) 2005 PubSub Concepts, Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the 
 * GNU General Public License as published by the Free Software Foundation; either version 2 of the 
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; 
 * if not, write to the Free Software Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.  
 */

// the bulk of processing is done in creating the entries, which
// is handled by individual script pages for each type.  the methods
// in this plugin file are used to render text and add menu items.

// menu items are added to the menu on "post.php".  we also map the 
// identifier used to recognize types of posts, so we can open the
// proper editing page.  

$sb_menu_items = Array( 
    // events
   Array("title"   => "Review",
	 "script"  => "sb-post.php?sb_cat=Review",
	 "id"      => "x-wpsb-post",
	 "version" => 1),
   Array("title"   => "Event",
	 "script"  => "sb-post.php?sb_cat=Event",
	 "id"      => "x-wpsb-post",
	 "version" => 1),
      Array("title"   => "List",
	 "script"  => "sb-post.php?sb_cat=List",
	 "id"      => "x-wpsb-post",
	 "version" => 1),
   Array("title"   => "Audio",
	 "script"  => "sb-post.php?sb_mc_type=media/audio",
	 "id"      => "x-wpsb-post",
	 "version" => 1),
      Array("title"   => "Video",
	 "script"  => "sb-post.php?sb_mc_type=media/video",
	 "id"      => "x-wpsb-post",
	 "version" => 1),
   Array("title"   => "People Showcase",
	 "script"  => "sb-post.php?sb_mc_type=showcase/person",
	 "id"      => "x-wpsb-post",
	 "version" => 1),
   Array("title"   => "Group Showcase",
	 "script"  => "sb-post.php?sb_mc_type=showcase/group",
	 "id"      => "x-wpsb-post",
	 "version" => 1),
   Array("title"   => "Other microcontent",
	 "script"  => "sb-post.php",
	 "id"      => "x-wpsb-post",
	 "version" => 1),
   );

function sb_prepare_unfix_content( $content )
{
    global $sb_unfix_is_sb, $sb_post_xml;

    $pattern = "~^(.+)(<script type=\"application/x-subnode.+?>.+?xml-structured-blog-entry.+?</script>)~s";
    if(!preg_match( $pattern, $content, $m )) {
	$sb_unfix_is_sb = 0;
    } else {
	$sb_unfix_is_sb = 1; // it's SB
	$content = $m[1];
	$sb_post_xml = $m[2];
    }

    return $content;
}

/**
 * process a structured blogging entry.  the intent of this function is 
 * to reformat (or un-format) the xml after other wp filters have added 
 * <p/> para and <br/> breakingspace tags - rather than remove the other 
 * filters, we can just clean up the text here.  
 */
function sb_unfix_content( $content )
{
	global $sb_unfix_is_sb, $sb_post_xml;

	// this is a counter to identify elements; it's global so 
	// we can increment it and have unique IDs for each element
	// on the page (whatever that page might be).
	global $entry_count;
	global $wpdb;

	// check the preference for cdata encoding.  if set, escape xml in blocks.
	$use_cdata = false;
	$check = $wpdb->get_results( 	"SELECT $wpdb->options.option_value " . 
		"FROM $wpdb->options WHERE option_name='sb-escape-xml-blocks'");   
	if( null != $check )
	{
		foreach ( $check as $option) 
			if( intval($option->option_value) == 1 ) $use_cdata = true;
	}


	if ($sb_unfix_is_sb)
	{
		$xml = $sb_post_xml;
		
		if( $use_cdata )
		{
			// escape manually
			$xml = str_replace( "&", "&amp;", $xml );
			$xml = str_replace( "<", "&lt;", $xml );
			$xml = str_replace( ">", "&gt;", $xml );
		}
		$content .= $xml;
		
		// set IDs for the replace-ID scheme
		$id = sprintf( "\"sbentry_%d\"", ++$entry_count );
		$content = str_replace( "\"sbentry_\"", $id, $content );
		$content = str_replace( "<subnode", 
								"<subnode alternate-for-id=$id", 
								$content );
	}
	
	return $content;
}

/**
 * add our stylesheet.  this is called in the _head action, but can 
 * be disabled by unchecking the box on the options page.
 */
function sb_add_stylesheet()
{
	global $wpdb;
	$show_stylesheet = true;

	// check options.  we might not want to include it.  null counts
	// as true, because it means the user has never looked at the
	// options page.
	$check = $wpdb->get_results( 	"SELECT $wpdb->options.option_value " . 
									"FROM $wpdb->options WHERE option_name='sb-use-stylesheet'");   
	if( null != $check )
	{
		foreach ( $check as $option) 
		{
			if( intval($option->option_value) != 1 ) $show_stylesheet = false;
		}
	}
	if( $show_stylesheet )
	{
		$stylesheetpath = get_settings('siteurl') . "/wpsb-files/wpsb-style.css";
		echo "<link rel=\"stylesheet\" href=\"$stylesheetpath\"/>\n" ;
	}
}

/**
 * The hReview microformat requires that we mark up the description
 * field as <something class="description">description</something>.
 * Unfortunately the WP default theme's stylesheet defines rules for
 * '.description' that make it invisible in content.  To get around
 * this, at the end of the page, we go back and remove 'description'
 * classes (except where they are used by the theme to mark up the
 * blog's tagline).
 *
 * WP feature request: change this class name to something like
 * 'wp_tagline'.
 */
function sb_add_footer_hackery()
{
    ?>
<script language="javascript">
    // Remove class="description" (required by hReview microformat)
    // from reviews so they display properly under the WP default theme.
    divs = document.getElementsByTagName('div');
    if (document.all) className = 'className'; else className = 'class';
    for (i = 0; i < divs.length; i += 1) {
	element = divs[i];
	if (element.getAttribute(className) == 'description'
	    && !(element.parentNode.nodeName == 'DIV'
		 && element.parentNode.getAttribute('id') == 'headerimg'))
	{
            element.setAttribute(className, '');
	}
    }
</script>
    <?
}

/**
 * When a user clicks "edit" on a post, wp opens up the post.php 
 * script.  Since we're using different edit pages for the various sb 
 * types, we need to redirect to the appropriate page.  This is a pretty 
 * simple check for the content, and then a js redirect.  We'd like to 
 * use header redirects, but that may not be feasible.
 */
function sb_redirect_edit()
{
	global $wpdb;
	global $sb_menu_items;

	// check the page and action: do we need to redirect?
	$page = $_SERVER['SCRIPT_NAME'];
	$postID = (int)trim($_GET['post']);
	$action = trim( $_GET['action'] );
	$target_page = "";
	$needToCheckRedirect = false;
	
	if( $action == "" ) $action = trim( $_POST['action'] );

	if( ( $action == 'edit' && preg_match( "~/post.php$~", $page ))
		|| ( !isset( $_GET['indirect_ref'] ) && $action == 'edit' && preg_match( "~/sb-post-~", $page )))
	{
		// check the content for an sb entry: we need to see the post...
	
		$postdata = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID = '$postID'");
		if( preg_match( "~<xml-structured-blog-entry.+?<generator\s+([^>]*)>~s", $postdata->post_content, $m ))
		{		
			$generatorfields = $m[1];
			if( preg_match( "~type=(?:'|\")(.+?)(?:'|\")~", $generatorfields, $m ))
			{
				$type = $m[1];
				
				// see if the type matches up with anything we're aware of...
				reset( $sb_menu_items );
				while( $sbarr = current( $sb_menu_items ))
				{
					if( $sbarr['id'] == $type )
					{
						// set the target.  the indirect referrer is used because
						// of the js redirect scheme, below.
						$target_page = $sbarr['script'] . "?" . 
									   $_SERVER['QUERY_STRING'] . 
									   "&indirect_ref=" . $_SERVER['HTTP_REFERER'];
					}
					next($sb_menu_items);
				}
			
				if( $target_page != "" )
				{
					// this is kind of hacky, but there's (apparently) no action called 
					// before any http output, so we can't use a header redirect here.
				
					echo "<script>document.location.href=\"$target_page\";</script>\n";
					exit;
				}
				
			}
		}
	}
}

/**
 * This function adds our menu items to the post page set.  It should
 * be run as an admin head filter.
 */
function sb_setup() 
{
	global $submenu;
	global $sb_menu_items;

	// add menu items; start at 15, and be sure not to overwrite anyone else.
	$index = 15;
	reset( $sb_menu_items );
	while( $sbarr = current( $sb_menu_items ))
	{
		while( $submenu['post.php'][$index] ) $index++;
		$submenu['post.php'][$index++] = array(__($sbarr['title']), 1, $sbarr['script']);
		next($sb_menu_items);
	}

	// create a menu item under options, too.  once again, don't overwrite.
	$index = 15;
	while( $submenu['options-general.php'][$index] ) $index++;
	$submenu['options-general.php'][$index] = array(__("Structured Blogging"), 5, "sb-options.php" );

}

// add stylesheet and javascript links to html <head> section in sb-post.php
function sb_add_admin_stylesheets()
{
    if (strpos($_SERVER['PHP_SELF'], "sb-post.php"))
    {
        ?>
<link rel="stylesheet" type="text/css" href="<?=get_settings('siteurl')?>/wpsb-files/wpsb-admin.css" />
<link rel="stylesheet" type="text/css" href="<?=get_settings('siteurl')?>/wpsb-files/wpsb-style.css" />
<script type="text/javascript" src="<?=get_settings('siteurl')?>/wpsb-files/prototype.js"></script>
<script type="text/javascript" src="<?=get_settings('siteurl')?>/wpsb-files/scriptaculous.js"></script>
<script type="text/javascript" src="<?=get_settings('siteurl')?>/wpsb-files/sb-lookup.js"></script>
        <?
    }
}

// If we want to write any extra content into an RSS item, write it out here.
function sb_write_rss_item_elements($content)
{
    global $post;
    $rss_content = get_post_meta($post->ID, "sb_rss_content", true);
    return $rss_content . $content;
}

// add the redirect method - must be as early as possible, so 
// we can send the header 
add_action('admin_head', 'sb_redirect_edit');

// add the menu setup to admin head
add_action('admin_head', 'sb_setup');

// add the stylesheet hook to admin head
add_action('admin_head', 'sb_add_admin_stylesheets');

// add the entry cleanup to the content method
add_action('the_content', 'sb_prepare_unfix_content', -10000); // before other filters run
add_action('the_content', 'sb_unfix_content', 10000); // and after they finish

// hook a function that gets called in the middle of writing an rss <item>.
// this lets us add our own elements in - for media RSS, etc.
// (ideally we would be able to hook an action for this, but there isn't
// one, so we misuse the the_category_rss filter instead).
add_filter("the_category_rss", 'sb_write_rss_item_elements');

// finally, add our stylesheet to the head, unless the stylesheet
// has been explicitly disabled (on the options page).
add_action('wp_head', 'sb_add_stylesheet');

// add javascript into the footer that forcibly removes 'description'
// classes from divs.  without this, the description fields of reviews
// marked up with the hReview microformat show up in white text on a
// white background with the default Wordpress template (Kubrick).
// see: http://www.myelin.co.nz/post/2005/12/10/#200512101
add_action('wp_footer', 'sb_add_footer_hackery');

?>
