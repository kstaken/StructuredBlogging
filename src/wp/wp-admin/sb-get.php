<?php
// Proxies a request to a remote service for use by an AJAX request.
function proxyRequest($url) {
	$contents = "";
	if (! preg_match( "~^http://xml-us.amznxslt.com/.+~i", $url, $m ) &&
	    ! preg_match( "~^http://xml-uk.amznxslt.com/.+~i", $url, $m ) && 
	    ! preg_match( "~^http://xml-ca.amznxslt.com/.+~i", $url, $m ) && 
        ! preg_match( "~^http://xml-de.amznxslt.com/.+~i", $url, $m ) && 
        ! preg_match( "~^http://xml-fr.amznxslt.com/.+~i", $url, $m ) && 
        ! preg_match( "~^http://xml-jp.amznxslt.com/.+~i", $url, $m )) {
	    return "ERROR: Can't connect to that URL ";
	}
	
	$url = str_replace(" ", "+", $url);
	$handle = fopen( $url, "r" );
	if( $handle )
	{
		while (! feof($handle)) {
		    $contents .= fread( $handle, 2048 );    
		}
		
		fclose( $handle );
	}
	else {
	    return 'ERROR: Connection failed.';
	}
 
    return $contents;
}

header("Content-type: text/xml");  
print proxyRequest($_REQUEST["url"]);

?>
