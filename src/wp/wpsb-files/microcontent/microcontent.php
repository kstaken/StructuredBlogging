<?php
require_once("XPath.class.php");
require_once("mc_renderers.php");

$debug = 0;

class MicroContent {
    var $view;
    var $instance;
    var $base;
    var $editor = "";
    var $editWrapper;
    var $map;
    var $type;
    
    function MicroContent($mcdLocation, $type, $data, &$form, $schema="", $fileUploadDirectory=false, $fileUploadURL="/") {
        global $debug;
	
	if (!$fileUploadDirectory)
	    $fileUploadDirectory = realpath("../images");
	if (!$fileUploadDirectory)
	    if (@mkdir("../images"))
		$fileUploadDirectory = realpath("../images");
        $this->fileUploadDirectory = $fileUploadDirectory;
        $this->fileUploadURL = $fileUploadURL;

	
                       
        $this->instance = new XPath();
        $this->instance->setVerbose($debug);
        
        if (!$mcdLocation) {
            die("You must provide the path to a directory where micro-content descriptions can be located.");
        }
        
        $this->type = $type;
                    
        $this->_buildDescriptorMap($mcdLocation);
        
        // See if the data to create the instance is being provided or if we need 
        // to read it from the form variables.
        if ($data) {
            $this->instance->importFromString($data);
            if (! $type) {         
                $this->type = $this->instance->getData("/node()[1]/@type"); 
            }
        } else if ($form) {         
            $this->processForm($form);
        }   

        if ((! $schema) && $this->type) {            
            // Try to use the type to locate the descriptor to use.
            $desc = $this->lookupDescriptor($this->type);
            if ($desc) {
                $handle = fopen($desc["path"], "rb");
                $schema = fread($handle, filesize($desc["path"]));
                fclose($handle);                
            }
	        else {
	            die("Can't read schema file " . $desc["path"]);
            }
        }
        else {
            // If no schema and no type then the descriptor has to be located from the instance data
            
        }

        // If a specific descriptor is provided we use that
        $schema = str_replace("\n", "", $schema);

        $this->view = new XPath();
        $this->view->setVerbose($debug);
        $this->view->importFromString($schema);            
        
        // Default, can be overridden
        $this->editWrapper = "mc_wrap_field";             
    }

    function getType() {
        return $this->type;
    }

    function getLabel() {
	return $this->view->getData('/micro-content/@label');
    }
    
    function lookupDescriptor($type) {
        $desc = NULL;
        if ($this->map) {
            $desc = $this->map[$type];            
        }
        
        return $desc;
    }
    
    function getDescriptorMap() {
        return $this->map;
    }
    
    function getTopLevelCategories()
    {
	$descriptors = $this->getDescriptorMap();
	
        // Group the descriptors by category
	foreach ($descriptors as $key => $descriptor) {
	  $categories[ucfirst($descriptor["category"])] = 1;
	}

	$cats = array_keys($categories);
	sort($cats);
	return $cats;
    }

    ///// Callback functions
    function registerEditWrapper($wrapper) {
        $this->editWrapper = $wrapper;
    }
    
    ///// Editor rendering logic

    function getEditor() {
        $mode = $contentPath = $this->view->getData('/micro-content/editor/@mode');
        if ($mode == "custom") {
            return $this->_renderStart("/micro-content/editor", "custom");            
        }
        else {
            return $this->_renderStart("/micro-content/editor", "simpleeditor");
        }

    }
    
    function _renderStart($field, $mode, $base="", $instanceID = NULL, $orientation = "") {
        if ($base  && $instanceID) {
            $base = $base . "[$instanceID]";        
        }
        
        $result = "";

        $style = "";
        // If the elements of the group should all be on one row we have to float them.
        if ($orientation == "horizontal") {
            $style = "float: left;";        
        }
        
        // Walk the list of nodes at this level in the tree
        $nodes = $this->view->match($field . "/node()");
        foreach ($nodes as $node) {
            if (! strstr($node, "text()")) {
                $nodeData = $this->view->getNode($node);

                switch($nodeData["name"]) {
                case "group":
                    $result .= $this->_renderGroup($node, $mode, $base, $style);                                            
                    break;
                case "field":
                    $result .= $this->_renderField($node, $mode, $base, $style);
                    break;
                case "if":
                    $result .= $this->_renderIf($node, $mode, $base);
                    break;
                case "attribute":
                    $result .= $this->_renderAttribute($node, $mode, $base, $field);
                    break;
                case "field-map":
                    $result .= $this->_renderFieldMap($node, $mode, $base, $field);
                    break;                
                // For all other types we recursively walk down the tree
                default:
                    $result .= $this->_serializeNode($node, $mode, $base, $nodeData);
                }                
            }  
            else {
                $result .= $this->_getTextNode($node);
            }              
        }    

        if ($orientation == "horizontal") {
            $result .= "<br />";
        }
        
        // Force the end of the row.
        if ($mode == "simpleeditor") {
            $result .= "<div style='clear:left;'></div>";            
        }
                
        return $result;
    }

    function _renderFieldMap($field, $mode, $base = "") {    
        $contentPath = $this->_getContentPath($field, $base);
	    
        $result = "";
        $nodes = $this->instance->match($contentPath);   
        if (count($nodes) > 0) {
            // Get the value of the field
            $value = $this->instance->getData($nodes[0]);
         
            $result = $value;
            // check to see if it matches any of the values in the map entries
            $maps = $this->view->match($field . "/map");
            foreach ($maps as $map) {
                $input = $this->view->getAttributes($map, "input");
                // If so return the value from the map instead of the original
                if ($value == $input) {
                    $result = $this->view->getAttributes($map, "output");
                }                
            }        
        }            
        
        return $result;
    }
    
    function _renderAttribute($field, $mode, $base, $parent) {   
        // If we're in view mode we set the attribute
        if ($mode == "view") {
            // Get the name of the attribute we're setting 
            $name = $this->view->getData($field . '/@name');
            
            // Get the value for the atttribute
            $value = $this->_renderStart($field, $mode, $base);   
            
            // walk back up the tree to avoid <if> and <group> blocks.
            while (1) {
            	$last_part_pos = strrpos($parent, "/");
            	$last_part = substr($parent, $last_part_pos);
            	if (strpos($last_part, "/if") !== 0 && strpos($last_part, "/group") !== 0) break;
                $parent = substr($parent, 0, $last_part_pos);
            }
            
            // Set the attribute to the parent element
            $this->view->setAttribute($parent, $name,  $value);
        } 
                
        // Otherwise we don't do anything
        return "";
    }
        
    function _renderIf($field, $mode, $base = "") {    
        $contentPath = $this->_getContentPath($field, $base);

        $op = $this->view->getData($field . '/@op');
        	    
        $result = "";
        // See how many instances of the field set exist in the instance document.
        $nodes = $this->instance->match($contentPath);   
        $ct = count($nodes);
        if ($op == 'not')
        {
            if (!$ct || $this->instance->getData($nodes[0]) == "") {
                $result .= $this->_renderStart($field, $mode, $base);
            }
        } else {
            if ($ct > 1) {
                $result .= $this->_renderStart($field, $mode, $base);
            }
            else if ($ct == 1){
                $value = $this->instance->getData($nodes[0]);
                if ($value) {
                    $result .= $this->_renderStart($field, $mode, $base);                
                }
            }
        }
        
        return $result;
    }
        
    function _renderGroup($field, $mode, $base = "") {    
        $orientation = $this->view->getData($field . '/@orientation');
        $repeat = $this->view->getData($field . '/@repeat');
        $addlabel = $this->view->getData($field . '/@addlabel');
        $label = $this->view->getData($field . '/@label');

        $result = "";
        if ($mode == "simpleeditor") {
            $result .= "<div style='clear: left;'>";
            
            if (!empty($label)) $result .= "<fieldset><legend>$label</legend>";
            
            $result .= "<div style='clear: all;'>";
        }
        
        $contentPath = $this->_getContentPath($field, $base);
        
        if ($repeat) {
            // See how many instances of the field set exist in the instance document.
            $count = $this->instance->match("count(" . $contentPath . ")");   
            if (is_int($count)) {
                // That's how many times we need to render the group.
                for ($i = 1; $i <= $count; $i++) {            
                    $result .= $this->_renderStart($field, $mode, $contentPath, $i, $orientation);
                }            
            }
            else {
                $result .= $this->_renderStart($field, $mode, $contentPath, NULL, $orientation);            
            }

            // for multi-valued entries we add a button so you can create a new row. 
            // This will come back into the object as part of the form and we'll add a 
            // row to the instance.
            if ($mode == "simpleeditor") {
                $result .= "<input type='submit' name='sb_action_add_row" . $contentPath . "' value='" . (empty($addlabel) ? "Add row" : $addlabel) . "'/>";
            }
        }    
        else {
            $result .= $this->_renderStart($field, $mode, $contentPath, NULL, $orientation);
        }

        if ($mode == "simpleeditor") {
            $result .= "</div>";
	        
	        if (!empty($label)) $result .= "</fieldset>";
	        
	        $result .= "</div>";            
        }
        
        return $result;
    }
    
    function _renderField($field, $mode, $base="", $style = "") {  
        $type = $this->view->getData($field . '/@type');
        
        $renderer = $this->_getRenderer($type);
        
        return $renderer->render($mode, $field, $base, $style);  
    }
     
    function _getRenderer($type) {
        // Dynamically create the renderer based on the type specified. The default is text.
        $default = "MCText";
        $name = $default;
        if ($type) {
            $name = "MC" . ucfirst($type); 
        }
        
        if (! class_exists($name)) {
            $name = $default;
        }

        $render = new $name($this->view, $this->instance, $this->editWrapper, "");
        $render->parent =& $this;

        return $render;
    }   
    
    ///// Form processing logic

    function processForm(&$form) {
        $delete = "";
        $compositeFields = array();
        $ignoreList = array();
        
        foreach ($form as $key => $value) {  
            if (array_key_exists($key, $ignoreList)) continue;

            $value = stripslashes($value);

            if ($key[0] == "/" && ! strpos($key, "#")) {
                $this->_saveField($key, $value, TRUE);            
            }
            // this was a button click to add something to the form.
            else if (preg_match('/sb_action_add_row(.+)/', $key, $result)) {
                $field = $result[1];
                $paths = explode("/", $field);

                $nodeName = array_pop($paths);
                $path = implode("/", $paths);
                $this->instance->appendChild($path, "<$nodeName/>");                                
            }
            // this was a button click to remove something from the form.
            else if (preg_match('/sb_action_delete_row(.+)/', $key, $result)) {
                // queue the delete action so that it can execute after the document
                // is complete.
                $delete= $this->_decodeFieldName($result[1]);
            }
            // the user is uploading a file
            else if (preg_match('/sb_action_upload_file(.+)/', $key, $result)) {
                $uploadkey = 'sb_action_upload_file_name' . $result[1];
                
                $uploadfile = $this->fileUploadDirectory . "/" . basename($_FILES[$uploadkey]['name']);

                if (move_uploaded_file($_FILES[$uploadkey]['tmp_name'], $uploadfile)) {
                
                    // Save the URL to the uploaded resource
                    $value = $this->fileUploadURL . "/" . $_FILES[$uploadkey]['name'];
                    $this->_saveField($result[1], $value, TRUE);
                    $ignoreList[$result[1]] = 1; // we don't want the URL to be overwritten by the hidden field containing the last URL
                } 
                else {
                    //FIXME: failed to upload the file - report an error?
                }
            }
            // Handle the cases where a single field in the XML is split across multiple fields 
            // in the form. We need to collect all the pieces together so that we can build the
            // XML value after we're done processing all the other fields.
            else if ($key[0] == "/" && strpos($key, "#") > 0) {
                $pieces = explode("#", $key);
                $field = $pieces[0];
                $attribute = $pieces[1];
                // This creates a hash structure keyed off the field name with a sub hash containing
                // the attributes
                $compositeFields[$field][$attribute] = $value;
            }
        }
        
        // Save the composite fields by looking up the type and then building the value
        if (count($compositeFields) > 0) {
            // there can be multiple fields with components in the form
            foreach ($compositeFields as $field => $components) {
                // Find out the type of the field
                $type = $components['type'];
                if ($type) {
                    $renderer = $this->_getRenderer($type);
                    
                    $value = $renderer->getValueFromComponents($components);
                    // Save the field to the XML structure.
                    $this->_saveField($field, $value, TRUE);                                    
                }                        
            }
        }
        
        // Add one last field to the data to encode the type.
        $this->_saveField("/node()/@type", $this->type, TRUE);
        
        // Delete the row queued earlier. We can only safely delete one row per 
        // transaction as the indexes will shift around after the first delete.
        // TODO: an altenate way to do this would be to look at the fields while 
        // building the document and then drop any that match paths under delete keys.
        if ($delete) {
            $this->instance->removeChild($delete);                                
        }
    }

    function _saveField($field, $value, $setValue = FALSE) {
    	$value = trim($value);
    	if (!$value) return;
    	
        $field = $this->_decodeFieldName($field);

        if ($this->instance->match($field)) {
            if ($setValue) {
                $this->instance->replaceData($field, htmlspecialchars($value));
            }
        }
        else {
            $paths = explode("/", $field);

            $nodeName = array_pop($paths);
            $nodeName = preg_replace("/\[.+?\]/", "", $nodeName);

            // Create the root node.
            if (count($paths) == 1) {
                $this->instance->appendChild(NULL, "<$nodeName/>");
            }
            else {
                $path = implode("/", $paths);

                $this->_saveField($path, $value);            

                if ($this->instance->match($path)) {
                    // Create the new element if it's not an attibute
                    if (! strstr($nodeName, "@")) {
                        $this->instance->appendChild($path, "<$nodeName/>");                    
                        if ($setValue) {
                            $this->instance->replaceData($field, htmlspecialchars($value));
                        }
                    }
                    // if it is an attribute save the data.
                    else {
                        $this->instance->setAttribute($path, ltrim($nodeName, "@"), htmlspecialchars($value));
                    }
                }
            }
        }
    }
    
    function getInstanceXml() {
        return $this->instance->exportAsXml('', '');
    }

    ///// View rendering logic

    function getView($media = 'html') {
        return $this->_renderView($media);
    }
    
    function _renderView($media) {
        $display = "/micro-content/display[@media = '$media']";
        if (empty($display)) {
            die("No display element could be found for media type $media");
        }
        
        $result = $this->_renderStart($display, "view");

        // Get the CSS stylesheet for the view
        $css = $this->view->getData("/micro-content[1]/display[1]/@stylesheet");
	if ($css) {
	    $result = '<link href="' . $css . '" rel="stylesheet" type="text/css"/>' . $result;
	}

        return $result;
    }
    
    function _serializeNode($node, $mode, $base, $nodeData) {
        // create the body of the tag. This may also set some attributes on the tag.
        $body = $this->_renderStart($node, $mode, $base);

        $result = $this->_serializeStartTag($node, $nodeData);
        $result .= $body;
        $result .= $this->_serializeEndTag($nodeData);                        
        return $result;
    }
    
    function _serializeStartTag($path, $node) {
        $result = "<" . $node["name"];
        $attributes = $this->view->getAttributes($path);
        foreach ($attributes as $attribute => $value) {
            $result .= " " . $attribute . "='" . $value . "'";        
        }
        $result .= ">";

        return $result;
    }

    function _serializeEndTag($node) {
        return "</" . $node["name"]  . ">";
    }
    
    function _getTextNode($node) {
        // find out which piece of text this is supposed to display, for some reason simply
        // using the pat to retrieve the text directly doesn't work.
        preg_match("/\/text\(\)\[(.+)\]/", $node, $matches);
        $index = $matches[1];
        // Strip the text reference from the path.
        $node = preg_replace("/\/text\(\)\[(.+)\]/", "", $node);

        // output the text, the index used is one off since php is base 0 and XPath is base 1
        $text = $this->view->getDataParts($node);
        return $text[$index - 1];        
    }
    
    function _getContentPath($field, $base) {
        $contentPath = $this->view->getData($field . '/@content');
        
        // if there's no contentPath then the attribute was empty or missing so we just use the base
        if (! $contentPath) {
            $contentPath = $base;
        }
        // Other wise if it's not an absolute path we append it to the base.
	    else if ($contentPath[0] != '/') {
	        $contentPath = "$base/$contentPath";            
	    }
	    
	    return $contentPath;        
    }
    
    function _buildDescriptorMap($dir) {
       if ( ! is_dir($dir)) {
           die ("Not a directory: $dir!");
       }

       if ($root = @opendir($dir)) {
           while ($file = readdir($root)) {               
               if ($file == "." || $file == ".." ){ 
                   continue; 
               }
               $pathinfo = pathinfo($file);
               if (! is_dir($dir . "/" . $file) && $pathinfo['extension'] == 'xml') {
                   $this->_readMapEntry($dir . "/" . $file);                   
               }
           }
       }
    }
    
    function _readMapEntry($file) {
        $handle = fopen($file, "r");
        $type = "";
        $label = "";
        $category = "";
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                if (preg_match("/<micro-content.*?display=[\"|\']false[\"|\'].*>/", $buffer, $match)) {
                    return; // skip this micro-content description - it's only for development use
                }

                if (preg_match("/<micro-content.*?type=[\"|\'](.*?)[\"|\'].*>/", $buffer, $match)) {
                    $type = $match[1];
                    $category = explode("/", $type);
                    $category = $category[0];
                } 
                
                if (preg_match("/<micro-content.*?label=[\"|\'](.*?)[\"|\'].*>/", $buffer, $match)) {
                    $label = $match[1];
                }
                
//                if (preg_match("/<micro-content.*?category=[\"|\'](.*?)[\"|\'].*>/", $buffer, $match)) {
//                    $category = $match[1];
//                }
                
                if ($type && $label) {
                    break;
                }              
            }

            fclose($handle);
            
            if (! ($type && $label)) {
                die("Invalid micro-content description in file $file");
            }
            
            $entry['label'] = $label;
            $entry['category'] = $category;
            $entry['path'] = $file;
            $entry['type'] = $type;

            $this->map[$type] = $entry;
        }
    }
    
    function _decodeFieldName($name) {
        return preg_replace("/\|(.+?)\|/", "[$1]", $name);
    }
}

// Default field wrapper if a specific one isn't provided by the consumer of the class
function mc_wrap_field($title, $field, $style) {
    $wrapper = "<div style='%STYLE%'><fieldset id='sb_titlediv'><legend><a>%TITLE%</a></legend><div>%FIELD%</div></fieldset></div>";
    
    $wrapped = str_replace("%TITLE%", $title, $wrapper);
    $wrapped = str_replace("%FIELD%", $field, $wrapped);
    $wrapped = str_replace("%STYLE%", $style, $wrapped);
    
    return $wrapped;
}

?>