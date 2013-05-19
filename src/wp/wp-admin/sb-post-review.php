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
 * for the specific type (review).
 */

// set flag for type
$sb_edit_type = "review";
$sb_edit_title = "Review";

// set page functions
$sb_page_functions['format'] = "format_review_content";
$sb_page_functions['parse'] = "parse_review_content";
$sb_page_functions['edit-form'] = "sb-edit-form.php";

$SB_REVIEW_TYPES = Array(
	"Book",
	"Movie",
	"CD",
	"DVD",
	"Concert",
	"Performance",
	"Other",
);

/** 
 * format a review.  if we knew that we had php5, we could just generate
 * the xml part and use xslt to format the html content.  for the time 
 * being, though, we need to render html directly.  
 * 
 * we'll use stylesheet classes for formatting html to offer more flexibility.
 */
function format_review_content()
{
	global $_POST; // unnecessary?
	global $excerpt, $simplecontent, $post_title;

	// defaults for rating data, empty links
	
	$rating = -1; 
	$ratingbase = 0;
	$ratingvalue = 0.0;
	$ratingstring = "";

	// calculate rating; we store this as a float
	
	if( isset( $_POST['post_rating']  )) $rating = intval( $_POST['post_rating'] );
	if( isset( $_POST['post_rating_base'] )) $ratingbase = intval( $_POST['post_rating_base'] );
	if( $rating >= 0 )
	{
		if( $ratingbase <= 0 ) $ratingbase = 10; // assign default
		$ratingvalue = $rating/$ratingbase;
		$ratingstring = "$rating out of $ratingbase";
	}

	$description = $_POST['content'];
	
	// set the class for the containing div; for reviews, this can
	// be type-specific.  that lets us use different formatting for
	// different types, if desired. 
	
	$reviewdivstyle = "x-wpsb-review-default";
	if( $_POST['post_review_type'] != "" ) 
		$reviewdivstyle = "x-wpsb-review-" . strtolower( trim( $_POST['post_review_type'] ));

	// get product information and post title.  if there's no title,
	// generate something suitable.

	$product = trim( $_POST['post_product_name'] );
	$title = trim($_POST['post_title']);
	if( $title == "" )
	{
		if( $product != "" ) $title = "Review: $product";
		else $title = "Review";
		$post_title = $title;
		$_POST['post_title'] = $title;
	}

	// get imagelink, link

	$imagelink = trim( $_POST['post_product_image_link'] );
	$productlink = trim( $_POST['post_product_link'] );

	// the div containing the html content has a generated identifier
	// used to mark the content as replaceable (by the xml).  however,
	// we can't know the makeup of any individual page, so these IDs 
	// should be generated after-the-fact.  the implementation will
	// just use a placeholder.
	
	$content = "<div class=\"$reviewdivstyle\" id=\"sbentry_\">\n";
	

	if( $imagelink != "" )
	{
		$content .= "<div class=\"x-wpsb-review-image\">";
		if( $productlink != "" )
		{
			$content .= "<a href=\"$productlink\">" .
						"<img alt=\"Product Image: $product\" border=\"0\" src=\"$imagelink\"/></a>";
		}
		else
		{
			$content .= "<img src=\"$imagelink\"/>";
		}
		$content .= "</div>\n";
	}
	
	if( $productlink != "" )
	{
		$content .= "<div class=\"x-wpsb-review-product\"><a href=\"$productlink\">$product</a></div>\n";
	}
	else
	{
		$content .= "<div class=\"x-wpsb-review-product\">$product</div>\n";
	}
	
	if( $rating >= 0 )
	{
		// all the extra divs are for displaying full and empty stars; 
		// these will be invisible if there are no appropriate styles.
		
		$content .= "<div class=\"x-wpsb-rating\">My rating: $ratingstring ";
		for( $i = 0; $i< $rating; $i++ ) $content .= "<div class=\"x-wpsb-fullstar\"> </div>";
		for( $i = $rating; $i< $ratingbase; $i++ ) $content .= "<div class=\"x-wpsb-emptystar\"> </div>";
		$content .= "<div class=\"x-wpsb-endstars\"> </div>\n";
		$content .= "</div>\n";
	}
	
	// WP will insert close-P / open-P at newlines in the description.  I think it expects to
	// see open-P / close-P at the beginning and end of the block.  We'll need to provide that.
	
	$content .= "<div class=\"x-wpsb-review-description\">\n<p>$description</p></div>\n</div>\n";
	
	$excerpt = $content;
	
	// set simplecontent for use in pingback, trackback calls later;
	// otherwise, it tries to ping namespaces and the like (which may
	// or may not be reachable)
	
	$simplecontent = $content;
	$_POST['excerpt'] = $excerpt;

	$description = str_replace( "&", "&amp", $description );
	$description = str_replace( "<", "&lt;", $description );
	$description = str_replace( ">", "&gt;", $description );

	$xml = 	"<script type=\"application/x-subnode; charset=utf-8\">\n" . 
			"<!-- the following is structured blog data for machine readers. -->\n" .
			"<subnode xmlns:data-view=\"http://www.w3.org/2003/g/data-view#\" " .
			"data-view:interpreter=\"http://structuredblogging.org/subnode-to-rdf-interpreter.xsl\" " .
			"xmlns=\"http://www.structuredblogging.org/xmlns#subnode\">\n" .
			"<xml-structured-blog-entry xmlns=\"http://www.structuredblogging.org/xmlns\">\n" .
			"	<generator id=\"wpsb-1\" type=\"x-wpsb-simple-review\" version=\"1\"/>\n" .
			"	<simple-review version=\"1\" xmlns=\"http://www.structuredblogging.org/xmlns#simple-review\">\n" .
			"		<review-title>" . $_POST['post_title'] . "</review-title>\n" ;

	if( $_POST['post_review_type'] != "" )
		$xml .= "		<review-type>" . $_POST['post_review_type'] . "</review-type>\n";

	if( $rating >= 0 )
		$xml .= "		<rating number=\"$rating\" base=\"$ratingbase\" value=\"$ratingvalue\">$ratingstring</rating>\n";
	
	if( $product != "" )
		$xml .= "		<product-name>$product</product-name>\n";
	if( trim($_POST['post_product_link']) != "" )
		$xml .= "		<product-link>" . $_POST['post_product_link'] . "</product-link>\n";
	if( trim($_POST['post_product_image_link']) != "" )
		$xml .= "		<product-image-link>" . $_POST['post_product_image_link'] . "</product-image-link>\n";
		
	// create an escaped version of the description for this block.
	$escaped_description = str_replace( "&", "&amp;", $description );
	$escaped_description = str_replace( "<", "&lt;", $escaped_description );
	$escaped_description = str_replace( ">", "&gt;", $escaped_description );

	$xml .= "		<description type=\"text/html\" escaped=\"true\">" .
			$escaped_description . "</description>\n" .
			"	</simple-review>\n" .
			"</xml-structured-blog-entry>\n" . 
			"</subnode>\n" .
			"</script>\n" ;

	$content .= "\n$xml\n";
	return $content;
}

/** 
 * parse an event, from structured content, and set page fields.
 */
function parse_review_content()
{
	// preparsed fields
	global $postdata;

	// page fields
	global $edited_post_title,
			$edited_post_product_name,
			$edited_post_product_link,
			$post_review_type, 
			$post_rating,
			$edited_post_product_image_link,
			$content, $simplecontent, $excerpt;

	// look for root; capture
	if( preg_match( "^(<xml-structured-blog-entry.+?</xml-structured-blog-entry>)\s*^s", 
		$postdata->post_content, $m ))
	{
		$xml = $m[1];
		
		// check version, potentially?
		
		// capture fields
		if( preg_match( "^<review-title.*?>(.+?)</review-title>^s", $xml, $m )) $edited_post_title = trim( $m[1] );
		
		if( preg_match( "^<rating(.*?)>(.+?)</rating>^s", $xml, $m ))
		{
			// current style
			if( preg_match( "~\s+value=\"(.+?)\"~", $m[1], $rm ))
			{
				// use our default base...
				$post_rating = 5 * $rm[1];
			}
			
			// old style
			else $post_rating = intval( trim( $m[2] ));
		}
		if( preg_match( "^<review-type.*?>(.+?)</review-type>^s", $xml, $m )) $post_review_type = trim( $m[1] );
		if( preg_match( "^<product-name.*?>(.+?)</product-name>^s", $xml, $m )) $edited_post_product_name= trim( $m[1] );
		if( preg_match( "^<product-link.*?>(.+?)</product-link>^s", $xml, $m )) $edited_post_product_link = trim( $m[1] );
		if( preg_match( "^<product-image-link.*?>(.+?)</product-image-link>^s", $xml, $m )) $edited_post_product_image_link = trim( $m[1] );
		
		$content = "";
		if( preg_match( "^<description.*?>(.+?)</description>^s", $xml, $m ))
		{
		    $content = trim( $m[1] );
		    $content = str_replace( "&lt;", "<", $content );
		    $content = str_replace( "&gt;", ">", $content );
		    $content = str_replace( "&amp;", "&", $content );
		}

	}
		
}

include( "sb-post-common.php" );

?>