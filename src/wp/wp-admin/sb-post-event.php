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
 * for the specific type (event).
 */
 
// set flag for type
$sb_edit_type = "event";
$sb_edit_title = "Event";

// set page functions
$sb_page_functions['format'] = "format_event_content";
$sb_page_functions['parse'] = "parse_event_content";
$sb_page_functions['edit-form'] = "sb-edit-form.php";

// roles
$SB_EVENT_ROLES = Array(
	" - ", 
	"Attendee",
	"Organizer",
	"Sponsor",
	"Reporter",
	"Bystander",
	"Host",
	"Guest",
);

/** 
 * format an event.  this includes the event itself (adding headers)
 * and the xml structured content.
 */
function format_event_content()
{
	global $_POST; // unnecessary?
	global $excerpt, $simplecontent;
		
	$description = $_POST['content'];

	// get page parameters
	
	$url = trim($_POST['post_url']);
	$phone = trim($_POST['post_phone']);
	$location = trim($_POST['post_location']);
	$role = trim( $_POST['post_role'] );
	if( $role == "-" ) $role = ""; 
	
	// figure out date

	$hr = trim( $_POST['post_time'] );
	$dateval = mktime( 1,1,1, $_POST['post_month'], $_POST['post_mday'], $_POST['post_year'] );
	
	// build html

	$content = "<div class=\"x-wpsb-simple-event\" id=\"sbentry_\">\n";

	$dateblock = "<div class=\"x-wpsb-event-date\">When: " . date( "l, F d Y", $dateval );
	if( $hr != "" && $hr >= 0 )
	{
		if( $hr == 0 ) $dateblock .= " Midnight";
		elseif( $hr == 12 ) $dateblock .= " 12:00 Noon";
		elseif( $hr > 12 ) $dateblock .= sprintf( " %02d:00 PM", $hr - 12 );
		else $dateblock .= sprintf( " %02d:00 AM", $hr );
	}
	$dateblock .= "</div>\n";
	$content .= $dateblock;
	
	if( $location != "" ) $content .= "<div class=\"x-wpsb-event-location\">Where: $location</div>\n";
	if( $phone != "" ) $content .= "<div class=\"x-wpsb-event-information\">More Information: $phone</div>\n";
	if( $url != "" ) $content .= "<div class=\"x-wpsb-event-information\">More Information: <a href=\"$url\">$url</a></div>\n";
	if( $role != "" ) $content .= "<div class=\"x-wpsb-event-role\">My Role: $role</div>\n";
	
	//$content .= "</p>\n<p>\n";
	$content .= "<div>";
	$content .= $description;
	//$content .= "\n</p>\n</div>\n";
	$content .= "</div>\n";
	$content .= "</div>\n";
	
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
			"	<generator id=\"wpsb-1\" type=\"x-wpsb-simple-event\" version=\"1\"/>\n" .
			"	<simple-event version=\"1\" xmlns=\"http://www.structuredblogging.org/xmlns#simple-event\">\n" ;

	$datetimestr = sprintf( "%04d-%02d-%-2d", 
							$_POST['post_year'], $_POST['post_month'], $_POST['post_mday'] );
	if( $hr != "" && $hr >= 0 ) $datetimestr .= sprintf( "T%02d:00:00", $hr );
	$xml .= "		<datetime>$datetimestr</datetime>\n" .
			"		<event-title>" . $_POST['post_title'] . "</event-title>\n" .
			"		<location>$location</location>\n";

	if( $role != "" ) $xml .= "		<role>$role</role>\n";

	if( trim($_POST['post_url']) != "" ) 
		$xml .= "		<more-information url=\"$url\"/>\n";

	if( trim($_POST['post_phone']) != "" ) 
		$xml .= "		<more-information phone=\"$phone\"/>\n";


	// create an escaped version of the description for this block.
	$escaped_description = str_replace( "&", "&amp;", $description );
	$escaped_description = str_replace( "<", "&lt;", $escaped_description );
	$escaped_description = str_replace( ">", "&gt;", $escaped_description );

	$xml .= "		<description type=\"text/html\" escaped=\"true\">" .
			$escaped_description . "</description>\n" .
			"	</simple-event>\n" .
			"</xml-structured-blog-entry>\n" .
			"</subnode>\n" .
			"</script>\n" ;

	$content .= "\n$xml\n";
	return $content;
}

/** 
 * parse an event, from structured content, and set page fields.
 */
function parse_event_content()
{
	// preparsed fields
	global $postdata;

	// page fields
	global $edited_post_location,
			$edited_post_title,
			$edited_post_role,
			$edited_post_url,
			$edited_post_phone,
			$post_time, $post_year, $post_month, $post_mday,
			$content, $simplecontent, $excerpt;

	// look for root; capture
	if( preg_match( "^(<xml-structured-blog-entry.+?</xml-structured-blog-entry>)\s*^s", 
		$postdata->post_content, $m ))
	{
		$xml = $m[1];
		
		// check version, potentially?
		
		// capture fields
		if( preg_match( "^<location.*?>(.+?)</location>^s", $xml, $m )) $edited_post_location = trim( $m[1] );
		if( preg_match( "^<event-title.*?>(.+?)</event-title>^s", $xml, $m )) $edited_post_title = trim( $m[1] );
		if( preg_match( "^<role.*?>(.+?)</role>^s", $xml, $m )) $edited_post_role = trim( $m[1] );
		if( preg_match( "~<more-information[^>]+url=\"(.+?)\"~", $xml, $m )) $edited_post_url = trim( $m[1] );
		if( preg_match( "~<more-information[^>]+phone=\"(.+?)\"~", $xml, $m )) $edited_post_phone = trim( $m[1] );
		
		// new style (8601)
		if( preg_match( "~<datetime>(.+?)</datetime>~s", $xml, $m ))
		{
			$datetime = $m[1];
			$post_time = -1;
			if( preg_match( '~^(\d\d\d\d)-(\d\d)-(\d\d)~', $datetime, $m ))
			{
				$post_year = $m[1];
				$post_month = $m[2];
				$post_mday = $m[3];
			}
			if( preg_match( '~T(\d\d)\:~', $datetime, $m ))
			{
				$post_time = $m[1];
			}
		}
		
		// old style (ugly)
		elseif( preg_match( "~<datetime\s+([^>]+?)(?:>|/)~s", $xml, $m ))
		{
			$datetime = $m[1];
			if( preg_match( "~month=\"(.+?)\"~", $datetime, $m )) $post_month = $m[1];
			if( preg_match( "~mday=\"(.+?)\"~", $datetime, $m )) $post_mday = $m[1];
			if( preg_match( "~year=\"(.+?)\"~", $datetime, $m )) $post_year = $m[1];
			if( preg_match( "~hour=\"(.+?)\"~", $datetime, $m )) $post_time = $m[1];
			else $post_time = -1;
		}
	
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