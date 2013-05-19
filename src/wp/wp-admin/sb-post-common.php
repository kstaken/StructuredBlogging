<?php

/*
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

/*
 * Structured blogging: common form script.  functions for
 * parsing and formatting sb entries should be written in 
 * enclosing pages, mapped to a function array.
 *
 * This file is based on the original WP file post.php, from
 * the distribution WP 1.5.  
 */

require_once('admin.php');

$wpvarstoreset = array('action', 'safe_mode', 'withcomments', 'posts', 'content', 'edited_post_title', 'comment_error', 'profile', 'trackback_url', 'excerpt', 'showcomments', 'commentstart', 'commentend', 'commentorder' );

for ($i=0; $i<count($wpvarstoreset); $i += 1) {
	$wpvar = $wpvarstoreset[$i];
	if (!isset($$wpvar)) {
		if (empty($_POST["$wpvar"])) {
			if (empty($_GET["$wpvar"])) {
				$$wpvar = '';
			} else {
			$$wpvar = $_GET["$wpvar"];
			}
		} else {
			$$wpvar = $_POST["$wpvar"];
		}
	}
}

if (isset($_POST['deletepost'])) {
$action = "delete";
}

$parent_file = 'post.php'; // so admin-header.php gives us the submenu from post.php rather than just the top-level menu
$current_url = $_SERVER['SCRIPT_URI'];
if (@$_SERVER['QUERY_STRING']) $current_url .= '?'.$_SERVER['QUERY_STRING'];
$submenu_file = preg_replace('|^.*/wp-admin/|i', '', $current_url);

$redisplay_edit_form = 0;

if ($action)
{
	// new-style structured blogging - check to see if this is a
	// POST to add a new field in the working copy of a post
	// (without saving it).
	foreach ($_POST as $k => $v)
	{	    
		if (strpos($k, "sb_action_") === 0)
		{
		  		#	die ("found add_ request: $k<br>" . $sb_mc_type);
			$redisplay_edit_form = 1;
			if ($action == "editpost") {
			    $action = "edit";			    
			}
			else {
			    $action = "";
			}

			$sb_mc_instance = new MicroContent($sb_mc_location, $sb_mc_type, "", $_POST, "", 
                get_option("sb-file-upload-directory"), get_option("sb-file-upload-url")); 
			
			break;
		}		
	}
}


$edited_post_title = $_REQUEST['post_title'];

switch($action) {
case 'post':

	if ( !user_can_create_draft($user_ID) )
		die('You are not allowed to create posts or drafts on this blog.');

	$post_pingback = (int) $_POST['post_pingback'];
	$content         = apply_filters('content_save_pre',  $_POST['content']);
	$excerpt         = apply_filters('excerpt_save_pre',  $_POST['excerpt']);
	$post_title      = apply_filters('title_save_pre',    $_POST['post_title']);
	$post_categories = apply_filters('category_save_pre', $_POST['post_category']);
	$post_status     = apply_filters('status_save_pre',   $_POST['post_status']);
	$post_name       = apply_filters('name_save_pre',     $_POST['post_name']);
	$post_parent = 0;
	$menu_order  = 0;
	
	// we need a function set by the enclosing page.
	if( isset( $sb_page_functions['format'] ))
	{
		$fn = $sb_page_functions['format'];
		$content = $fn();
	}
	
	if ( isset($_POST['parent_id']) )
		$post_parent = (int) $_POST['parent_id'];

	if ( isset($_POST['menu_order']) )
		$menu_order = (int) $_POST['menu_order'];

	if (! empty($_POST['post_author_override'])) {
		$post_author = (int) $_POST['post_author_override'];
	} else if (! empty($_POST['post_author'])) {
		$post_author = (int) $_POST['post_author'];
	} else {
		$post_author = (int) $_POST['user_ID'];
	}
	if ( !user_can_edit_user($user_ID, $post_author) )
		die( __('You cannot post as this user.') );

	if ( empty($post_status) )
		$post_status = 'draft';
	// Double-check
	if ( 'publish' == $post_status && (!user_can_create_post($user_ID)) && 2 != get_option('new_users_can_blog') )
		$post_status = 'draft';
	$comment_status = $_POST['comment_status'];
	if ( empty($comment_status) && !isset($_POST['advanced_view']) )
		$comment_status = get_option('default_comment_status');
	$ping_status = $_POST['ping_status'];
	if ( empty($ping_status) && !isset($_POST['advanced_view']) )
		$ping_status = get_option('default_ping_status');
	$post_password = $_POST['post_password'];
	

	
	$trackback = $_POST['trackback_url'];
	$trackback = preg_replace('|\s+|', "\n", $trackback);

	if (user_can_set_post_date($user_ID) && (!empty($_POST['edit_date']))) {
		$aa = $_POST['aa'];
		$mm = $_POST['mm'];
		$jj = $_POST['jj'];
		$hh = $_POST['hh'];
		$mn = $_POST['mn'];
		$ss = $_POST['ss'];
		$jj = ($jj > 31) ? 31 : $jj;
		$hh = ($hh > 23) ? $hh - 24 : $hh;
		$mn = ($mn > 59) ? $mn - 60 : $mn;
		$ss = ($ss > 59) ? $ss - 60 : $ss;
		$now = "$aa-$mm-$jj $hh:$mn:$ss";
		$now_gmt = get_gmt_from_date("$aa-$mm-$jj $hh:$mn:$ss");
	} else {
		$now = current_time('mysql');
		$now_gmt = current_time('mysql', 1);
	}


	// What to do based on which button they pressed
	if ('' != $_POST['saveasdraft']) { $post_status = 'draft'; }
	if ('' != $_POST['saveasprivate']) { $post_status = 'private'; }
	if ('' != $_POST['publish']) { $post_status = 'publish'; }
	if ('' != $_POST['advanced']) { $post_status = 'draft'; }
	if ('' != $_POST['savepage']) { $post_status = 'static'; }

	$id_result = $wpdb->get_row("SHOW TABLE STATUS LIKE '$wpdb->posts'");
	$post_ID = $id_result->Auto_increment;

	if ( empty($post_name) ) {
		$post_name = sanitize_title($post_title, $post_ID);
	} else {
		$post_name = sanitize_title($post_name, $post_ID);
	}

	if ('publish' == $post_status) {
		$post_name_check = $wpdb->get_var("SELECT post_name FROM $wpdb->posts WHERE post_name = '$post_name' AND post_status = 'publish' AND ID != '$post_ID' LIMIT 1");
		if ($post_name_check) {
			$suffix = 2;
			while ($post_name_check) {
				$alt_post_name = $post_name . "-$suffix";
				$post_name_check = $wpdb->get_var("SELECT post_name FROM $wpdb->posts WHERE post_name = '$alt_post_name' AND post_status = 'publish' AND ID != '$post_ID' LIMIT 1");
				$suffix++;
			}
			$post_name = $alt_post_name;
		}
	}

	$postquery ="INSERT INTO $wpdb->posts
			(ID, post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt,  post_status, comment_status, ping_status, post_password, post_name, to_ping, post_modified, post_modified_gmt, post_parent, menu_order)
			VALUES
			('$post_ID', '$post_author', '$now', '$now_gmt', '".addslashes($content)."', '$post_title', '".addslashes($excerpt)."', '$post_status', '$comment_status', '$ping_status', '$post_password', '$post_name', '$trackback', '$now', '$now_gmt', '$post_parent', '$menu_order')
			";

	$result = $wpdb->query($postquery);

	if (!empty($_POST['mode'])) {
	switch($_POST['mode']) {
		case 'bookmarklet':
			$location = 'bookmarklet.php?a=b';
			break;
		case 'sidebar':
			$location = 'sidebar.php?a=b';
			break;
		default:
			$location = 'sb-post.php';
			break;
		}
	} else {
		$location = 'sb-post.php?posted=true';
	}

	if ( 'static' == $_POST['post_status'] )
		$location = "page-new.php?saved=true";

	if ( '' != $_POST['advanced'] || isset($_POST['save']) )
		$location = "sb-post.php?action=edit&post=$post_ID";
	        
	header("Location: $location"); // Send user on their way while we keep working

    # Send the request through to other systems.
    sb_submit_to_outputthis($post_title, $content);
    
	// Insert categories
	// Check to make sure there is a category, if not just set it to some default
	if (!$post_categories) $post_categories[] = get_option('default_category');
	foreach ($post_categories as $post_category) {
		// Double check it's not there already
		$exists = $wpdb->get_row("SELECT * FROM $wpdb->post2cat WHERE post_id = $post_ID AND category_id = $post_category");

		 if (!$exists) { 
			$wpdb->query("
			INSERT INTO $wpdb->post2cat
			(post_id, category_id)
			VALUES
			($post_ID, $post_category)
			");
		}
	}

	add_meta($post_ID);

	// store a copy of the post's SB XML and RSS content in the postmeta table
	global $post_sb_xml, $sb_rss_content;
        foreach (array("structuredblogging_xml" => $post_sb_xml,
                       "sb_rss_content" => $sb_rss_content)
                 as $k => $v)
        {
            $wpdb->query("INSERT INTO $wpdb->postmeta SET post_id='$post_ID', meta_key='$k', meta_value='".addslashes($v)."'");
        }

	$wpdb->query("UPDATE $wpdb->posts SET guid = '" . get_permalink($post_ID) . "' WHERE ID = '$post_ID'");

	do_action('save_post', $post_ID);

	// this is a bit dangerous (actually, it just doesn't work) probably
	// because it's trying to ping the namespaces; let's clean it up
	// by removing xml before we do any pingback, trackback type stuff.

	// we have the method now setting "simplecontent", which is the formatted
	// content block without any xml.  use that here.

	/*

	if ('publish' == $post_status) {

		if ($post_pingback)
			pingback($simplecontent, $post_ID);
		do_enclose( $simplecontent, $post_ID );
		do_trackbacks($post_ID);
		do_action('publish_post', $post_ID);
	
	}

	*/

	if ($post_status == 'static') {
		generate_page_rewrite_rules();
		add_post_meta($post_ID, '_wp_page_template',  $_POST['page_template'], true);
	}
       
	require_once('admin-header.php');

	exit();
	break;

case 'edit':
	$title = __('Edit');

	require_once('admin-header.php');

	$post = $post_ID = $p = (int) $_GET['post'];

	if ( !user_can_edit_post($user_ID, $post_ID) )
		die ('You are not allowed to edit this post.');
		
	$postdata = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID = '$post_ID'");
	$content = $postdata->post_content;
	$content = format_to_edit($content);
	$content = apply_filters('content_edit_pre', $content);
	$excerpt = $postdata->post_excerpt;
	$excerpt = format_to_edit($excerpt);
	$excerpt = apply_filters('excerpt_edit_pre', $excerpt);
	$edited_post_title = format_to_edit($postdata->post_title);
	$edited_post_title = apply_filters('title_edit_pre', $edited_post_title);
	$post_status = $postdata->post_status;
	$comment_status = $postdata->comment_status;
	$ping_status = $postdata->ping_status;
	$post_password = $postdata->post_password;
	$to_ping = $postdata->to_ping;
	$pinged = $postdata->pinged;
	$post_name = $postdata->post_name;
	$post_parent = $postdata->post_parent;
	$post_author = $postdata->post_author;
	$menu_order = $postdata->menu_order;
	$post_sb_xml = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE post_id='$post_ID' AND meta_key='structuredblogging_xml'");

	if( 'private' == $postdata->post_status && $postdata->post_author != $user_ID )
		die ('You are not allowed to view other users\' private posts.');

	if ($redisplay_edit_form)
	{
		// if we're doing something that requires a refresh without
		// grabbing everything from the database, get the content from
		// the page...
		$sb_info = $redisplay_content;
				//echo "redisplaying content: "; 
	}
	else
	{
		// otherwise get it from the database
		if( isset( $sb_page_functions['parse'] )) $sb_page_functions['parse']();
	}

	// include the edit form.  may be dependent on page, so it's
	// abstracted.  set in calling page.
	if( isset( $sb_page_functions['edit-form'] ))
		include( $sb_page_functions['edit-form'] );

	$post = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID = '$post_ID'");
	?>
	<div id='preview' class='wrap'>
	<h2><?php _e('Post Preview (updated when post is saved)'); ?></h2>
	<h3 class="storytitle" id="post-<?php the_ID(); ?>"><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php printf(__("Permanent Link: %s"), the_title()); ?>"><?php the_title(); ?></a></h3>
	<div class="meta"><?php _e("Filed under:"); ?> <?php the_category(','); ?> &#8212; <?php the_author() ?> @ <?php the_time() ?></div>

	<div class="storycontent">
	<?php 
	if ($redisplay_edit_form) {
	    $content = apply_filters('the_content', $sb_mc_instance->getView());	    
	}
	else {
            $content = apply_filters('the_content', $post->post_content);
	}
	echo $content;
	?>
	</div>
	</div>
	<?php
	break;

case 'editpost':
	// die(var_dump('<pre>', $_POST));
	if (!isset($blog_ID)) {
		$blog_ID = 1;
	}
	$post_ID = $_POST['post_ID'];

	if (!user_can_edit_post($user_ID, $post_ID, $blog_ID))
		die('You are not allowed to edit this post.');

	$post_categories = $_POST['post_category'];
	if (!$post_categories) $post_categories[] = 1;
	$content = apply_filters('content_save_pre', $_POST['content']);
	$excerpt = apply_filters('excerpt_save_pre', $_POST['excerpt']);
	$post_title = $_POST['post_title'];
	$prev_status = $_POST['prev_status'];
	$post_status = $_POST['post_status'];
	$menu_order = (int) $_POST['menu_order'];
	if (! empty($_POST['post_author_override'])) {
		$post_author = (int) $_POST['post_author_override'];
	} else if (! empty($_POST['post_author'])) {
		$post_author = (int) $_POST['post_author'];
	} else {
		$post_author = (int) $_POST['user_ID'];
	}
	if ( !user_can_edit_user($user_ID, $post_author) )
		die( __('You cannot post as this user.') );

	// sb block 

	// for an event post, munge the content so that it's formatted
	// appropriately and includes the structured xml entry.
	if( isset( $sb_page_functions['format'] ))
	{
		$fn = $sb_page_functions['format'];
		$content = $fn();
	}

	// end block

	$comment_status = $_POST['comment_status'];
	if (empty($comment_status)) $comment_status = 'closed';
	//if (!$_POST['comment_status']) $comment_status = get_settings('default_comment_status');

	$ping_status = $_POST['ping_status'];
	if (empty($ping_status)) $ping_status = 'closed';
	//if (!$_POST['ping_status']) $ping_status = get_settings('default_ping_status');
	$post_password = $_POST['post_password'];
	$post_name = $_POST['post_name'];
	if (empty($post_name)) {
		$post_name = $post_title;
	}

	$post_parent = 0;
	if (isset($_POST['parent_id'])) {
		$post_parent = $_POST['parent_id'];
	}

	$trackback = $_POST['trackback_url'];
	// Format trackbacks
	$trackback = preg_replace('|\s+|', '\n', $trackback);
	
	if (isset($_POST['publish'])) $post_status = 'publish';
	// Double-check
	if ( 'publish' == $post_status && (!user_can_create_post($user_ID)) && 2 != get_option('new_users_can_blog') )
		$post_status = 'draft';

	if (empty($post_name)) {
		$post_name = sanitize_title($post_title, $post_ID);
	} else {
		$post_name = sanitize_title($post_name, $post_ID);
	}

	if ('publish' == $post_status) {
		$post_name_check = $wpdb->get_var("SELECT post_name FROM $wpdb->posts WHERE post_name = '$post_name' AND post_status = 'publish' AND ID != '$post_ID' LIMIT 1");
		if ($post_name_check) {
			$suffix = 2;
			while ($post_name_check) {
				$alt_post_name = $post_name . "-$suffix";
				$post_name_check = $wpdb->get_var("SELECT post_name FROM $wpdb->posts WHERE post_name = '$alt_post_name' AND post_status = 'publish' AND ID != '$post_ID' LIMIT 1");
				$suffix++;
			}
			$post_name = $alt_post_name;
		}
	}

	if (user_can_edit_post_date($user_ID, $post_ID) && (!empty($_POST['edit_date']))) {
		$aa = $_POST['aa'];
		$mm = $_POST['mm'];
		$jj = $_POST['jj'];
		$hh = $_POST['hh'];
		$mn = $_POST['mn'];
		$ss = $_POST['ss'];
		$jj = ($jj > 31) ? 31 : $jj;
		$hh = ($hh > 23) ? $hh - 24 : $hh;
		$mn = ($mn > 59) ? $mn - 60 : $mn;
		$ss = ($ss > 59) ? $ss - 60 : $ss;
		$datemodif = ", post_date = '$aa-$mm-$jj $hh:$mn:$ss'";
	$datemodif_gmt = ", post_date_gmt = '".get_gmt_from_date("$aa-$mm-$jj $hh:$mn:$ss")."'";
	} else {
		$datemodif = '';
		$datemodif_gmt = '';
	}

	$now = current_time('mysql');
	$now_gmt = current_time('mysql', 1);

	$result = $wpdb->query("
		UPDATE $wpdb->posts SET
			post_content = '".addslashes($content)."',
			post_excerpt = '".addslashes($excerpt)."',
			post_title = '$post_title'"
			.$datemodif_gmt
			.$datemodif.",			
			post_status = '$post_status',
			comment_status = '$comment_status',
			ping_status = '$ping_status',
			post_author = '$post_author',
			post_password = '$post_password',
			post_name = '$post_name',
			to_ping = '$trackback',
			post_modified = '$now',
			post_modified_gmt = '$now_gmt',
			menu_order = '$menu_order',
			post_parent = '$post_parent'
		WHERE ID = $post_ID ");

	// our redirect logic has to be slightly different, because
	// on edits we're not arriving via direct link (we're 
	// redirecting through the main post.php page).

	if( isset( $_POST['indirect_ref'] )) $http_ref = trim( $_POST['indirect_ref'] );
	elseif( isset( $_GET['indirect_ref'] )) $http_ref = trim( $_GET['indirect_ref'] );
	else $http_ref = trim( $_SERVER['HTTP_REFERER'] );

	if ($_POST['save']) 
	{
		$location = $http_ref ;
	} 
	elseif ($_POST['updatemeta']) 
	{
		$location = $http_ref . '&message=2#postcustom';
	} 
	elseif ($_POST['deletemeta']) 
	{
		$location = $http_ref . '&message=3#postcustom';
	} 
	elseif (isset($_POST['referredby']) && $_POST['referredby'] != $http_ref ) 
	{
		$location = $_POST['referredby'];
		if ( $_POST['referredby'] == 'redo' ) $location = get_permalink( $post_ID );
	} 
	else 
	{
		$location = 'sb-post.php';
	}

	// because this is passed around as a parameter, it may be url-encoded; 
	// we have to decode it before we can use it as an url.

	$location = urldecode( $location );

	header ('Location: ' . $location); // Send user on their way while we keep working

	// update structured blogging xml and rss content
	global $post_sb_xml, $sb_rss_content;
        foreach (array("structuredblogging_xml" => $post_sb_xml,
                       "sb_rss_content" => $sb_rss_content)
                 as $k => $v)
        {
            $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key='$k' AND post_id='$post_ID'");
            $wpdb->query("INSERT INTO $wpdb->postmeta SET post_id='$post_ID', meta_key='$k', meta_value='".addslashes($v)."'");
        }

	// Meta Stuff
	if ($_POST['meta']) :
		foreach ($_POST['meta'] as $key => $value) :
			update_meta($key, $value['key'], $value['value']);
		endforeach;
	endif;

	if ($_POST['deletemeta']) :
		foreach ($_POST['deletemeta'] as $key => $value) :
			delete_meta($key);
		endforeach;
	endif;

	add_meta($post_ID);

	// Now it's category time!
	// First the old categories
	$old_categories = $wpdb->get_col("SELECT category_id FROM $wpdb->post2cat WHERE post_id = $post_ID");
	
	// Delete any?
	foreach ($old_categories as $old_cat) {
		if (!in_array($old_cat, $post_categories)) // If a category was there before but isn't now
			$wpdb->query("DELETE FROM $wpdb->post2cat WHERE category_id = $old_cat AND post_id = $post_ID LIMIT 1");
	}
	
	// Add any?
	foreach ($post_categories as $new_cat) {
		if (!in_array($new_cat, $old_categories))
			$wpdb->query("INSERT INTO $wpdb->post2cat (post_id, category_id) VALUES ($post_ID, $new_cat)");
	}

	if ($prev_status != 'publish' && $post_status == 'publish')
		do_action('private_to_published', $post_ID);

	if ($post_status == 'publish') {
	
		do_action('publish_post', $post_ID);
		do_trackbacks($post_ID);
		do_enclose( $simplecontent, $post_ID );
//		if ( get_option('default_pingback_flag') )
//			pingback($simplecontent, $post_ID);
	}

	if ($post_status == 'static') {
		generate_page_rewrite_rules();

		if ( ! update_post_meta($post_ID, '_wp_page_template',  $_POST['page_template'])) {
			add_post_meta($post_ID, '_wp_page_template',  $_POST['page_template'], true);
		}
	}

	do_action('edit_post', $post_ID);
	exit();
	break;

    case 'delete':
    	check_admin_referer();

    	$post_id = (isset($_GET['post']))  ? intval($_GET['post']) : intval($_POST['post_ID']);

    	if (!user_can_delete_post($user_ID, $post_id)) {
    		die('You are not allowed to delete this post.');
    	}

    	if (! wp_delete_post($post_id))
    		die(__('Error in deleting...'));

    	$sendback = $_SERVER['HTTP_REFERER'];
    	if (strstr($sendback, 'sb-post.php')) $sendback = get_settings('siteurl') .'/wp-admin/sb-post.php';
    	$sendback = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $sendback);
    	header ('Location: ' . $sendback);
    	generate_page_rewrite_rules();
    	do_action('delete_post', $post_id);
    	break;

    case 'editcomment':
    	$title = __('Edit Comment');
    	$parent_file = 'edit.php';
    	require_once('admin-header.php');

    	get_currentuserinfo();

    	$comment = $_GET['comment'];
    	$commentdata = get_commentdata($comment, 1, true) or die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'javascript:history.go(-1)'));

    	if (!user_can_edit_post_comments($user_ID, $commentdata['comment_post_ID'])) {
    		die('You are not allowed to edit comments on this post.');
    	}

    	$content = $commentdata['comment_content'];
    	$content = format_to_edit($content);
    	$content = apply_filters('comment_edit_pre', $content);

    	$comment_status = $commentdata['comment_approved'];

    	include('edit-form-comment.php');

    	break;

    case 'confirmdeletecomment':

        require_once('admin-header.php');

    	$comment = $_GET['comment'];
    	$p = $_GET['p'];
    	$commentdata = get_commentdata($comment, 1, true) or die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'edit.php'));

    	if (!user_can_delete_post_comments($user_ID, $commentdata['comment_post_ID'])) {
    		die('You are not allowed to delete comments on this post.');
    	}

    	echo "<div class=\"wrap\">\n";
    	echo "<p>" . __('<strong>Caution:</strong> You are about to delete the following comment:') . "</p>\n";
    	echo "<table border=\"0\">\n";
    	echo "<tr><td>" . __('Author:') . "</td><td>" . $commentdata["comment_author"] . "</td></tr>\n";
    	echo "<tr><td>" . __('E-mail:') . "</td><td>" . $commentdata["comment_author_email"] . "</td></tr>\n";
    	echo "<tr><td>". __('URL:') . "</td><td>" . $commentdata["comment_author_url"] . "</td></tr>\n";
    	echo "<tr><td>". __('Comment:') . "</td><td>" . stripslashes($commentdata["comment_content"]) . "</td></tr>\n";
    	echo "</table>\n";
    	echo "<p>" . __('Are you sure you want to do that?') . "</p>\n";

    	echo "<form action='".get_settings('siteurl')."/wp-admin/sb-post.php' method='get'>\n";
    	echo "<input type=\"hidden\" name=\"action\" value=\"deletecomment\" />\n";
    	echo "<input type=\"hidden\" name=\"p\" value=\"$p\" />\n";
    	echo "<input type=\"hidden\" name=\"comment\" value=\"$comment\" />\n";
    	echo "<input type=\"hidden\" name=\"noredir\" value=\"1\" />\n";
    	echo "<input type=\"submit\" value=\"" . __('Yes') . "\" />";
    	echo "&nbsp;&nbsp;";
    	echo "<input type=\"button\" value=\"" . __('No') . "\" onclick=\"self.location='". get_settings('siteurl') ."/wp-admin/edit.php?p=$p&amp;c=1#comments';\" />\n";
    	echo "</form>\n";
    	echo "</div>\n";

    	break;

    case 'deletecomment':

    	check_admin_referer();

    	$comment = $_GET['comment'];
    	$p = $_GET['p'];
    	if (isset($_GET['noredir'])) {
    		$noredir = true;
    	} else {
    		$noredir = false;
    	}

    	$postdata = get_postdata($p) or die(sprintf(__('Oops, no post with this ID. <a href="%s">Go back</a>!'), 'edit.php'));
    	$commentdata = get_commentdata($comment, 1, true) or die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'sb-post.php'));

    	if (!user_can_delete_post_comments($user_ID, $commentdata['comment_post_ID'])) {
    		die('You are not allowed to edit comments on this post.');
    	}

    	wp_set_comment_status($comment, "delete");
    	do_action('delete_comment', $comment);

    	if (($_SERVER['HTTP_REFERER'] != "") && (false == $noredir)) {
    		header('Location: ' . $_SERVER['HTTP_REFERER']);
    	} else {
    		header('Location: '. get_settings('siteurl') .'/wp-admin/edit.php?p='.$p.'&c=1#comments');
    	}

    	break;

    case 'unapprovecomment':

        require_once('admin-header.php');

    	check_admin_referer();

    	$comment = $_GET['comment'];
    	$p = $_GET['p'];
    	if (isset($_GET['noredir'])) {
    		$noredir = true;
    	} else {
    		$noredir = false;
    	}

    	$commentdata = get_commentdata($comment) or die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'edit.php'));

    	if (!user_can_edit_post_comments($user_ID, $commentdata['comment_post_ID'])) {
    		die('You are not allowed to edit comments on this post, so you cannot disapprove this comment.');
    	}

    	wp_set_comment_status($comment, "hold");

    	if (($_SERVER['HTTP_REFERER'] != "") && (false == $noredir)) {
    		header('Location: ' . $_SERVER['HTTP_REFERER']);
    	} else {
    		header('Location: '. get_settings('siteurl') .'/wp-admin/edit.php?p='.$p.'&c=1#comments');
    	}

    	break;

    case 'mailapprovecomment':

    	$comment = (int) $_GET['comment'];

    	$commentdata = get_commentdata($comment, 1, true) or die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'edit.php'));

    	if (!user_can_edit_post_comments($user_ID, $commentdata['comment_post_ID'])) {
    		die('You are not allowed to edit comments on this post, so you cannot approve this comment.');
    	}

    	if ('1' != $commentdata['comment_approved']) {
    		wp_set_comment_status($comment, 'approve');
    		if (true == get_option('comments_notify'))
    			wp_notify_postauthor($comment);
    	}

    	header('Location: ' . get_option('siteurl') . '/wp-admin/moderation.php?approved=1');

    	break;

    case 'approvecomment':

    	$comment = $_GET['comment'];
    	$p = $_GET['p'];
    	if (isset($_GET['noredir'])) {
    		$noredir = true;
    	} else {
    		$noredir = false;
    	}
    	$commentdata = get_commentdata($comment) or die(sprintf(__('Oops, no comment with this ID. <a href="%s">Go back</a>!'), 'edit.php'));

    	if (!user_can_edit_post_comments($user_ID, $commentdata['comment_post_ID'])) {
    		die('You are not allowed to edit comments on this post, so you cannot approve this comment.');
    	}

    	wp_set_comment_status($comment, "approve");
    	if (get_settings("comments_notify") == true) {
    		wp_notify_postauthor($comment);
    	}


    	if (($_SERVER['HTTP_REFERER'] != "") && (false == $noredir)) {
    		header('Location: ' . $_SERVER['HTTP_REFERER']);
    	} else {
    		header('Location: '. get_settings('siteurl') .'/wp-admin/edit.php?p='.$p.'&c=1#comments');
    	}

    	break;

    case 'editedcomment':

    	$comment_ID = $_POST['comment_ID'];
    	$comment_post_ID = $_POST['comment_post_ID'];
    	$newcomment_author = $_POST['newcomment_author'];
    	$newcomment_author_email = $_POST['newcomment_author_email'];
    	$newcomment_author_url = $_POST['newcomment_author_url'];
    	$comment_status = $_POST['comment_status'];

    	if (!user_can_edit_post_comments($user_ID, $comment_post_ID)) {
    		die('You are not allowed to edit comments on this post, so you cannot edit this comment.');
    	}

    	if (user_can_edit_post_date($user_ID, $post_ID) && (!empty($_POST['edit_date']))) {
    		$aa = $_POST['aa'];
    		$mm = $_POST['mm'];
    		$jj = $_POST['jj'];
    		$hh = $_POST['hh'];
    		$mn = $_POST['mn'];
    		$ss = $_POST['ss'];
    		$jj = ($jj > 31) ? 31 : $jj;
    		$hh = ($hh > 23) ? $hh - 24 : $hh;
    		$mn = ($mn > 59) ? $mn - 60 : $mn;
    		$ss = ($ss > 59) ? $ss - 60 : $ss;
    		$datemodif = ", comment_date = '$aa-$mm-$jj $hh:$mn:$ss'";
    	} else {
    		$datemodif = '';
    	}
    	$content = apply_filters('comment_save_pre', $_POST['content']);

    	$result = $wpdb->query("
    		UPDATE $wpdb->comments SET
    			comment_content = '$content',
    			comment_author = '$newcomment_author',
    			comment_author_email = '$newcomment_author_email',
    			comment_approved = '$comment_status',
    			comment_author_url = '$newcomment_author_url'".$datemodif."
    		WHERE comment_ID = $comment_ID"
    		);

    	$referredby = $_POST['referredby'];
    	if (!empty($referredby)) {
    		header('Location: ' . $referredby);
    	} else {
    		header ("Location: edit.php?p=$comment_post_ID&c=1#comments");
    	}
    	do_action('edit_comment', $comment_ID);
    	break;

default:
	global $sb_edit_title;
	$title = __("Structured Blogging: Create New $sb_edit_title Post");
	require_once('admin-header.php');
?>
<?php if ( isset($_GET['posted']) ) : ?>
<div class="updated"><p>Post saved. <a href="<?php bloginfo('home'); ?>">View site &raquo;</a></p></div>
<?php endif; ?>
<?php
	if (user_can_create_draft($user_ID)) {
		$action = 'post';
		get_currentuserinfo();
		$drafts = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE post_status = 'draft' AND post_author = $user_ID");
		if ($drafts) {
			?>
			<div class="wrap">
			<p><strong><?php _e('Your Drafts:') ?></strong>
			<?php
			$i = 0;
			foreach ($drafts as $draft) {
				if (0 != $i)
					echo ', ';
				$draft->post_title = stripslashes($draft->post_title);
				if ($draft->post_title == '')
					$draft->post_title = sprintf(__('Post # %s'), $draft->ID);
				echo "<a href='sb-post.php?action=edit&amp;post=$draft->ID' title='" . __('Edit this draft') . "'>$draft->post_title</a>";
				++$i;
				}
			?>.</p>
			</div>
			<?php
		}
		//set defaults
		$post_status = 'draft';
		$comment_status = get_settings('default_comment_status');
		$ping_status = get_settings('default_ping_status');
		$post_pingback = get_settings('default_pingback_flag');
		$default_post_cat = get_settings('default_category');

		$content = wp_specialchars($content);
		$content = apply_filters('default_content', $content);
		
		$edited_post_title = apply_filters('default_title', $edited_post_title);
		$excerpt = apply_filters('default_excerpt', $excerpt);

		/*
		if (get_settings('advanced_edit')) {
			include('edit-form-advanced.php');
		} else {
			include('edit-form.php');
		}
		*/
		
		if( isset( $sb_page_functions['edit-form'] ))
			include( $sb_page_functions['edit-form'] );
?>

<div class="wrap">

<h3><?php _e('What is Structured Blogging?') ?></h3> 
<p>
Structured blogging is a way to get more information on the web in a way
that's more usable.  
You can enter information on this form and it'll get
published on your blog like a normal entry; but it will also be published
as XML, so that other services can read and understand it.
</p>
<p>
Think of structured blogging as RSS for your information.  Now any kind of 
data - events, reviews, classified ads - can be represented in your blog.
</p>

</div>

<?php
} else {
?>
<div class="wrap">
<p><?php printf(__('Since you&#8217;re a newcomer, you&#8217;ll have to wait for an admin to raise your level to 1, in order to be authorized to post.<br />
You can also <a href="mailto:%s?subject=Promotion?">e-mail the admin</a> to ask for a promotion.<br />
When you&#8217;re promoted, just reload this page and you&#8217;ll be able to blog. :)'), get_settings('admin_email')); ?>
</p>
</div>
<?php
}

	break;
} // end switch
/* </Edit> */
include('admin-footer.php');
?>
