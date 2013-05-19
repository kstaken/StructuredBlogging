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
require_once("admin.php");
#require_once(dirname(__FILE__)."/../wpsb-files/schema/sb-widgets.php");
require_once(dirname(__FILE__) . "/../wpsb-files/microcontent/microcontent.php");
    
$SB_OUTPUTTHIS_ENDPOINT = 'http://outputthis.org/xmlrpc';
    
$sb_mc_location = dirname(__FILE__) . "/../wpsb-files/microcontent/descriptions";

// set flag for type
$sb_edit_type = "thing";
$sb_edit_title = "Structured Blogging";

// set page functions
$sb_page_functions['format'] = "format_thing_content";
$sb_page_functions['parse'] = "parse_thing_content";
$sb_page_functions['edit-form'] = "sb-edit-form.php";

$sb_mc_type = $_REQUEST['sb_mc_type'];
	
/** 
 * Format a structured post using an MCD file and the microcontent
 * library.
 */
function format_thing_content()
{
	// read in structured content from HTTP POST
	global $sb_mc_type, $sb_mc_location;

        $mc = new MicroContent($sb_mc_location, $sb_mc_type, "", $_REQUEST, "", 
                               get_option("sb-file-upload-directory"), get_option("sb-file-upload-url"));
    
        // Render the content for storage in the post
        $content = $mc->getView();    

        // And render any XML to be added to RSS feeds
        global $sb_rss_content;
        $sb_rss_content = $mc->getView("rss");
    
	// pass that through for:
	// * excerpt - for feeds
	// * simplecontent - for use in pingback, trackback calls
	// later; otherwise, it tries to ping namespaces and the like
	// (which may or may not be reachable)
	global $excerpt, $simplecontent;
	$_POST['excerpt'] = $excerpt = $simplecontent = $content;

	// now drop the xml on the end and send it back to the main renderer
	global $post_sb_xml;
	// Wrap the core micro-content within the nodes.
	$post_sb_xml = sb_wrap_microcontent($mc->getInstanceXml());
               
        
	$content .= "\n$post_sb_xml\n";

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
	$data = sb_extract_microcontent($postdata->post_content);

	if ($data)
	{
		global $sb_mc_type, $sb_mc_instance, $sb_mc_location;
		$sb_mc_instance = new MicroContent($sb_mc_location, "", $data, $post = NULL, "", 
            get_option("sb-file-upload-directory"), get_option("sb-file-upload-url"));
		
		if (empty($sb_mc_type)) $sb_mc_type = $sb_mc_instance->getType();
	}
}

# Extracts a chunk of microcontent XML from an HTML page
function sb_extract_microcontent($data) {
    $data = str_replace("\n", "", $data);

    $content = "";
    if (preg_match( "/.*<xml-structured-blog-entry.+?>.*<generator.+?\/>(.*)<\/xml-structured-blog-entry>.*/", 
		$data, $match )) {
		$content = $match[1];
	}
	
	return $content;
}

# Wraps a piece of micro-content for enbedding in an HTML page
function sb_wrap_microcontent($content) {
    return '<script type="application/x-subnode; charset=utf-8">
       <!-- the following is structured blog data for machine readers. -->
       <subnode xmlns:data-view="http://www.w3.org/2003/g/data-view#" data-view:interpreter="http://structuredblogging.org/subnode-to-rdf-interpreter.xsl" xmlns="http://www.structuredblogging.org/xmlns#subnode">
       	    <xml-structured-blog-entry xmlns="http://www.structuredblogging.org/xmlns">
       		    <generator id="wpsb-1" type="x-wpsb-post" version="1"/>' . $content . '
       	    </xml-structured-blog-entry>
       </subnode>
       </script>';
}

function sb_submit_to_outputthis($post_title, $content) {
    global $SB_OUTPUTTHIS_ENDPOINT;
    
    # Logic for handling posts to the outputthis.org webservice	
    include_once(dirname(__FILE__) . "/../wpsb-files/microcontent/IXR_Library.inc.php");

    # Service endpoint for the output this web service
    $client = new IXR_Client($SB_OUTPUTTHIS_ENDPOINT);

    $sb_outputthis_username = get_option("sb-outputthis-username");
    $sb_outputthis_password = get_option("sb-outputthis-password");

    # Check to see if the outputthis service is enabled.
    if (! empty($sb_outputthis_username) && ! empty($sb_outputthis_password)) {
        $ot_entry = array('title' => $post_title, 'description' => $content);

        $ot_request = array();
        foreach ($_POST['sb_outputthis_service'] as $id) {
            array_push($ot_request, array('ID' => $id, 'status' => 'publish'));            
        }        
        
        if (! $client->query('outputthis.publishPost', $sb_outputthis_username, $sb_outputthis_password, $ot_request, $ot_entry)) {
            # TODO: figure out what to do with this error.
            # When this is called the response has already been sent to the user.
        }
    }    
}

include( "sb-post-common.php" );

?>