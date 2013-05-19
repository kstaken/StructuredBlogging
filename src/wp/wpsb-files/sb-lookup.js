function doSelect(itemIndex) {	
    if (lookupItemList != null) {
        var item = lookupItemList[itemIndex];
        // Only one of these should be set.
    	setValue("lookup-book", getElement(item, "title"));
    	setValue("lookup-cd", getElement(item, "title"));
    	setValue("lookup-movie", getElement(item, "title"));
        
    	setValue("lookup-link", getElement(item, "detail"));
        setValue("lookup-date", getElement(item, "date"));
        
        setValue("lookup-year", getElement(item, "date").split("-")[0]);
        
        setValue("lookup-author", getElement(item, "author"));
        setValue("lookup-publisher", getElement(item, "publisher"));
        setValue("lookup-isbn", getElement(item, "isbn"));
    	
    	setValue("lookup-artist", getElement(item, "artist"));
        setValue("lookup-label", getElement(item, "label"));
        setValue("lookup-upc", getElement(item, "upc"));

    	setValue("lookup-rating", getElement(item, "rating"));
        setValue("lookup-studio", getElement(item, "studio"));
        setValue("lookup-length", getElement(item, "length"));

    	setValue("lookup-format", getElement(item, "format"));
        setValue("lookup-esrb", getElement(item, "esrb"));
        setValue("lookup-manufacturer", getElement(item, "manufacturer"));

        setValue("lookup-source", "Amazon.com");
        setValue("lookup-source-id", getElement(item, "asin"));                
        
        // See if there's an image upload field on the form. 
        var image = $("image-upload");
        if (image != null) {
            var imageURL = getElement(item, "medium-image");
            // so then we want to insert an image tag to display the image as a preview
            new Effect.BlindUp(image, {duration: 0.5, afterFinish: function(effect) {
                var tag = "<div id=\"image-upload\"><img src=\"" +  imageURL + "\"><br></div>";
                image.innerHTML = tag;
                setValue("image-upload-data", imageURL);
                new Effect.BlindDown(image, {duration: 0.5});                
            }});
        }
        else {
            setValue("lookup-image", getElement(item, "medium-image"));            
        }
        
        lookupItemList = null;
    }
    
    effect = new Effect.BlindUp($("sb-lookup-window"), {duration: 0.5});
}

function lookupSetContents(value) {
    $("sb-lookup-contents").innerHTML = '<div id="sb-lookup-contents">' + value + '</div>';            
}

var lookupItemList = null;

function lookupAmazon(request) {
    //lookupSetContents(request.responseText);
    //return;
    var doc = request.responseXML;  

    var items = doc.getElementsByTagName("item");
    lookupItemList = items;
    
    var header = "Found 1 match";
    if (items.length > 1) {
        header = "Found " + items.length + " matches";
    }
    else if (items.length == 0) {
        header = "No matching results were found."
    }

    var result = header + "<br /><table>";
    for (i = 0; i < items.length; i++) {
        var item = items[i];
        result += "<tr>";
        result += "<td rowspan='2'><img src='" + getElement(item, "small-image") + "' /></td>" 
        result += "<td>" + getElement(item, "title") + "</td>";
        result += "</tr>";
        result += "<tr>";
        result += "<td colspan='2'>";
        result += "<a href='javascript:doSelect(" + i + ")'>Select</a>"
        result += " - <a href='" + getElement(item, "detail") + "' target='_blank'>Open Amazon Page</a>"
        result += "</td>";
        result += "</tr>";
    }
    result += "</table>";
    
    lookupSetContents(result);
}

function escapeQuotes(string) {
	string = string.replace( /\"/g, "\\\"");
	string = string.replace( /'/g, "\\\'");
}

function getElement(element, child) {
    var children = element.getElementsByTagName(child);
    if (children.length > 0) {
        if (children[0].firstChild != null) {
            return children[0].firstChild.nodeValue;            
        }
    }
    
    return "";
}

function lookupFailed(request) {
    lookupSetContents("Your search request could not be completed at this time.")
}

function lookupWindowClose() {
    effect = new Effect.BlindUp($("sb-lookup-window"), {duration: 1.0});    
}

function lookupRequest(title, type) {
	lookupSetContents("Searching");
	
	var url = lookupProxyURL + "?url=";

	lookupCount = 0;
	
    var searchIndex = "Blended";
	if ( type == "lookup-cd" ) {
        searchIndex = "Music";
	}
    else if ( type == "lookup-book" ) {
        searchIndex = "Books";
    }
    else if ( type == "lookup-movie" ) {
        searchIndex = "DVD";
    }
    else if ( type == "lookup-software" ) {
        searchIndex = "Software";
    }
    
    var xslURL = "http://www.xmldatabases.org/sb-lookup.xsl";

    var accessKey = "1GJZ3WSF1JX2981GW3R2";

    url += urlEscape(lookupAmazonURL + "/onca/xml?Service=AWSECommerceService&AWSAccessKeyId=" +
         accessKey + "&ResponseGroup=Small,Images,ItemAttributes&Operation=ItemSearch&SearchIndex=" + searchIndex + 
         "&Version=2005-10-05&Keywords=" + title + "&Style=" + xslURL + "&AssociateTag=" + lookupAmazonAffiliate);   

    var request = new Ajax.Request(url, {method: 'get', onSuccess: lookupAmazon, onFailure: lookupFailed, onLoading: lookupUpdateStatus});	        
}

function lookupData(name_elem)
{
	var searchEl = null;
	var searchStr = null;
	var dateNow = new Date();
	var url = "";

	// pull out the product name
    searchStr = $F(name_elem);

	if( null == searchStr || searchStr == "" )
	{
	    // TODO: make this nicer then an alert.
		alert( "Please enter a product name to look up." );
		return;
	}
        
	var bodyStub = '<div class="sb_lookup_cancel"><a href="javascript:lookupWindowClose();">Cancel</a></div><br/><center><div id="sb-lookup-contents" /><br /><br /></center><br/><div class="sb_lookup_cancel">'
	$("sb-lookup-window").innerHTML = bodyStub;

	effect = new Effect.BlindDown($("sb-lookup-window"), {duration: 1.0});  
    
    var result = "";
    lookupRequest(searchStr, name_elem); 			 
}

var lookupProxyURL = "sb-get.php";
var lookupAmazonURL = "http://xml-us.amznxslt.com";
var lookupAmazonAffiliate = "";

function lookupAddLinks(proxyURL, amazonURL, amazonAffiliate) {
    // Set the configuration options
    if (proxyURL) lookupProxyURL = proxyURL;
    if (amazonURL) lookupAmazonURL = amazonURL;
    if (amazonAffiliate) lookupAmazonAffiliate = amazonAffiliate;
    
    // Dynamically add the lookup links to the document
    type = null;
    
    // This is the list of valid lookup types
    var lookupKeys = new Array("lookup-movie", "lookup-cd", "lookup-book", "lookup-dvd", "lookup-software");
    
    for (var i = 0; i < lookupKeys.length; i++) {
        var element = document.getElementById(lookupKeys[i]);
        if (element != null) {
            type = lookupKeys[i];
        
            if (type) {
                var txt = document.createTextNode("Lookup");
                var newElem = document.createElement("a");
                newElem.setAttribute("href", "javascript:lookupData('" + type + "')");
                newElem.appendChild(txt);
                element.parentNode.appendChild(newElem);    

                newElem = document.createElement("br");                
                element.parentNode.appendChild(newElem);
                
                // Create the div that will contain the lookup window
                newElem = document.createElement("div");
                newElem.setAttribute("id", "sb-lookup-window");
                newElem.style.display = "none";
                newElem.className = "sb_lookup_window";                
                element.parentNode.appendChild(newElem);
            }
        }        
    }
}

var lookupCount = 0;

// Attempt to provide some feedback to the user that something is happening
function lookupUpdateStatus(request) {
    var string = "Searching ";

    for (i = 0; i < lookupCount; i++) {
        string += ".";
    }
    lookupCount++;
    
    //lookupSetContents(string);
}

function urlEscape(string) {
    string = string.replace( / /g, "%20");
	string = string.replace( /\"/g, "%27");
	string = string.replace( /'/g, "%27");
	string = string.replace( /&/g, "%26");
	return string;
}

function setValue(id, value) {
	var element = $( id );
	if ( null != element ) {
	    element.value = value;		
	}
}


/*function lookupMovie(request) {  
    var responseResult = request.responseText;
        
    var matchURL = new RegExp('<a href="http://us.rd.yahoo.com/movies/search/movie/title/(.+?)">(.+?)</a>', "");    
    
    var lines = responseResult.split("\n");    
    var i = 0;

    var found = false;
    var html = "<center><table>";
    for (i = 0; i < lines.length; i++) {
        if (result = matchURL.exec(lines[i])) {
            found = true;
            //link = result[1].replace(/^\*x2A/, "");
            title = result[2];
            
            html += "<tr><td colspan='2'>" + title + "</td></tr>";
			html += "<tr><td><a href='javascript:doMovieSelect(\"" + urlEscape(title) + "\", \"" + 
			    urlEscape(link) + "\");'>Select</a></td><td><a target='_blank' href='" + link + "'>Open Yahoo Movies page</a></td></tr>";
        }
    }
    html += "</table></center><br/>";

    if (! found) {
        html = "No matching titles found";    
    }
    
    lookupSetContents(html);
}

function doMovieSelect(title, link)
{
	var wp = window.opener;
	var doc = null;
	var o = null;
	var query = window.location.href;
	
	lookupSetContents("Retrieving image for " + title);
	var url = "sb-get.php?url=" + urlEscape(link);
	
	var request = new Ajax.Request(url, { 
	    method: 'get', 
	    onSuccess: lookupImageComplete,
	    onFailure: lookupFailed
	});
	effect = new Effect.Fade($("sb-lookup-window"), {duration: 0.5});
	
	$("lookup-movie").value = title;    
	$("lookup-link").value = link;
}

function lookupImageComplete(request) {
    var responseResult = request.responseText;    
    
    // TODO: get this working
    lookupSetContents(responseResult);
    var matchURL = new RegExp('<img src=(http:.+?) {5}width=\"101\"', "i");    
    if (result = matchURL.exec(responseResult)) {
        imglink = result[1];
        alert(imgLink);
    }

    var lines = responseResult.split("\n");    
    var i = 0;

    for (i = 0; i < lines.length; i++) {
        alert(lines[1]);
        return;
        if (result = matchURL.exec(lines[i])) {
            imglink = result[1];
            alert(imgLink);
        }
    }
}*/
