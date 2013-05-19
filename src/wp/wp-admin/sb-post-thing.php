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
 * this is a wrapper for SB content.  it uses the script 
 * sb-post-common.php for control flow; this page sets a 
 * type flag and includes parsing and rendering methods 
 * for the specific type (thing).
 */

require_once(dirname(__FILE__)."/../wpsb-files/schema/sb-widgets.php");

// set flag for type
$sb_edit_type = "thing";
$sb_edit_title = "Thing";

// set page functions
$sb_page_functions['format'] = "format_thing_content";
$sb_page_functions['parse'] = "parse_thing_content";
$sb_page_functions['edit-form'] = "sb-edit-form.php";

//var_dump($_REQUEST); exit;

if (empty($_REQUEST['sb_mc_type']))
	sb_set_mc_type("");
else
	sb_set_mc_type($_REQUEST['sb_mc_type']);

/** 
 * format a thing.  if we knew that we had php5, we could just generate
 * the xml part and use xslt to format the html content.  for the time 
 * being, though, we need to render html directly.  
 *  * we'll use stylesheet classes for formatting html to offer more flexibility.
 */
function format_thing_content()
{
	// read in structured content from HTTP POST
	global $sb_mc_type, $sb_info;
	$sb_info = sb_read_post_data($sb_mc_type);

//	echo "post content:<pre>"; var_dump($sb_info); echo "</pre>"; exit;

	// run the renderer script and suck its output into $content
	ob_start();
	include(dirname(__FILE__)."/../wpsb-files/schema/sb-render-review.php");
	$content = ob_get_contents();
	ob_end_clean();

	// pass that through for:
	// * excerpt - for feeds
	// * simplecontent - for use in pingback, trackback calls
	// later; otherwise, it tries to ping namespaces and the like
	// (which may or may not be reachable)
	global $excerpt, $simplecontent;
	$_POST['excerpt'] = $excerpt = $simplecontent = $content;

	// now drop the xml on the end and send it back to the main renderer
	global $post_sb_xml;
	$post_sb_xml = sb_xml_from_post($sb_info);
	$content .= "\n$post_sb_xml\n";
	//	echo "just made the xml from this info:<pre>"; var_dump($sb_info); echo "</pre>... the xml looks like this:<pre>".htmlspecialchars($post_sb_xml)."</pre>";
	//	exit;
	return $content;
}

/** 
 * parse a thing, from structured content, and set page fields.
 */
function parse_thing_content()
{
	// preparsed fields
	global $postdata;

	// page fields
//	global $edited_post_title,
//			$edited_post_product_name,
//			$edited_post_product_link,
//			$post_thing_type, 
//			$post_rating,
//			$edited_post_product_image_link,
//			$content, $simplecontent, $excerpt;

	// look for root; capture
	list($mctype, $mcinfo) = sb_extract_info_from_content($postdata->post_content);
	if ($mcinfo !== false)
	{
		global $sb_mc_type;
		if (empty($sb_mc_type)) sb_set_mc_type($mctype);
	}
}

include( "sb-post-common.php" );

?>