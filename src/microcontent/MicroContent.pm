package MicroContent;

use strict;

use XML::XPath;
#use File::Basename;

sub new {
    my $class = shift;
    my $mcdLocation = shift;
    my $type = shift;
    my $instance = shift;
    my $cgi = shift;
    my $view = shift || "";
    my $fileUploadDirectory = shift || 0;
    my $fileUploadURL = shift || "/";
    
    my $this = {};
    bless $this, $class;
        
    $this->{"fileUploadDirectory"} = $fileUploadDirectory;
    $this->{"fileUploadURL"} = $fileUploadURL;
    
    if (! $mcdLocation) {
        die("You must provide the path to a directory where micro-content descriptions can be located.");
    }
    
    $this->{"type"} = $type;
    
    $this->_buildDescriptorMap($mcdLocation);
        
    if ($instance) {
        $this->{'instance'} = new XML::XPath(xml => $instance);        
    }
    elsif ($cgi) {
        $this->processForm($cgi);
    }
    
    if ((! $view) && $this->{"type"}) {
        # Try to use the type to locate the descriptor to use.
        my $desc = $this->lookupDescriptor($this->{'type'});

        if ($desc) {
            my $path = $desc->{"path"};
            open(DESC, "< $path") || die "Unknown content descriptor type.";
            my @lines = <DESC>;
            $view = join("", @lines);
        }   
    }
    
    $this->{'view'} = new XML::XPath(xml => $view);
    
    return $this;
}

sub lookupDescriptor {
    my $this = shift;
    my $type = shift;
    
    my $desc;
    if ($this->{'map'}) {
        $desc = $this->{'map'}->{$type};          
    }
    
    return $desc;
}

sub getDescriptorMap {
    my $this = shift;
    
    return $this->{'map'};
}

sub registerEditWrapper {
    my $this = shift;
    my $wrapper = shift;
    
    $this->{'editWrapper'} = $wrapper;
}

sub getType {
    my $this = shift;
    
    return $this->{'type'};
}

sub getEditor {
    my $this = shift;
    
    my $nodes = $this->{'view'}->find("/micro-content/editor/node()");
    my $mode = $this->{'view'}->findvalue('/micro-content/editor/@mode');
    if ($mode eq "custom") {
        return $this->_renderStart($nodes, "custom");           
    }
    else {
        return $this->_renderStart($nodes, "simpleeditor");           
    }
}

sub getView {
    my $this = shift;
    my $media = shift || "html";
    
    my $nodes = $this->{'view'}->find("/micro-content/display[\@media = '$media']/node()");
    
    return $this->_renderStart($nodes, "view");           
}

sub getInstanceXml {
    my $this = shift;
    
    return $this->{'instance'}->findnodes_as_string("/");
}

##### Form processing logic

sub processForm {
    my $this = shift;
    my $cgi = shift;

    my %form = $cgi->Vars;

    my $delete = "";
    
    # this was a button click to remove something from the form.
    # First pass through look for any actions that will need to be applied while
    # building the document
    foreach my $key (keys(%form)) { 
        my $value = $form{$key};
        if ($key =~ /sb_action_delete_row(.+)/) {
            # queue the delete action so that the fields will be ignored
            $delete = $1;
            $delete =~ s/\//\\\//g;
            $delete =~ s/\|/\\\|/g;
        }
    }

    # Container to hold the values from any elements that split across multiple form fields
    my %compositeFields;
    
    # now build the document
    foreach my $key (keys(%form)) {        
        my $value = $form{$key};

        # if the row is not being deleted then we should process it.
        if (($key !~ /$delete/) || (! $delete)) {
            if ((substr($key, 0, 1) eq "/") && ($key !~ /#/)) {             
                $this->_saveField($key, $value, 1);            
            }        
            elsif ((substr($key, 0, 1) eq "/") && ($key =~ /#/)) {
                my @pieces = split("#", $key);
                my $field = $pieces[0];
                my $attribute = $pieces[1];
                # This creates a hash structure keyed off the field name with a sub hash containing
                # the attributes
                $compositeFields{$field}{$attribute} = $value;                
            }
        }
    }

    # Handle the actions
    foreach my $key (keys(%form)) {       
        # this was a button click to add something to the form.
        if ($key =~ /sb_action_add_row(.+)/) {
            # The XPath to the field is part of the name
            my $field = $1;
            my @paths = split("/", $field);

            # Find the name of the element that is the container for the row
            my $nodeName = $paths[$#paths];
            $#paths = $#paths - 1;
            my $path = join("/", @paths);
            if ($this->{'instance'}) {
                my $nodeset = $this->{'instance'}->find($path);
                my $resultNode = $nodeset->get_node($nodeset->size());

                my $node = XML::XPath::Node::Element->new($nodeName);

                $resultNode->appendChild($node);                                                
            }
        } 
        # the user is uploading a file
        elsif ($key =~ /sb_action_upload_file([^_].+)/) {
            # This is the path to the form field containing the uploaded file.
            my $field = $1;
            my $uploadkey = 'sb_action_upload_file_name' . $field;
            
            my $filename = $this->_uploadFile($cgi, $uploadkey);
            
            # Save the URL so that we can display it in the UI
            my $value = $this->{'fileUploadURL'} . "/" . $filename;
            $this->_saveField($field, $value, 1);
        }           
    }
       
    # Save the composite fields by looking up the type and then building the value
    # there can be multiple fields with components in the form
    foreach my $field (keys(%compositeFields)) {
        my $components = $compositeFields{$field};
        # Find out the type of the field
        my $type = $components->{'type'};
        if ($type) {
            my $renderer = $this->_getRenderer($type);
            
            my $value = $renderer->getValueFromComponents($components);
            # Save the field to the XML structure.
            $this->_saveField($field, $value, 1);                                    
        }                        
    }
}

sub _uploadFile {
    my $this = shift;
    my $cgi = shift;
    my $uploadkey = shift;
    
    # We need to know the upload directory
    my $upload_location = $this->{'fileUploadDirectory'};
    
    if (! -d $upload_location) {
        die("The file upload directory could not be found.");
    }

    if (! -w $upload_location) {
        die("The file upload directory is not writable.");
    }
    
    # Get the name of the file, stripping any extra slashes
    my $filename = $cgi->param($uploadkey);
    $filename =~ s/.*[\/\\](.*)/$1/;

    my $file_location = "$upload_location/$filename";

# *** MT does this, commented
#
#    open UPLOAD, ">$file_location" || die "Couldn't open the file";
#    binmode UPLOAD;
#
# *** MT does this, commented
    
    # Get the file handle to the file that was uploaded
    my $uploadHandle = $cgi->upload($uploadkey);

# *** MT hooks

    my $vars = $cgi->Vars;
    my $blog_id = $vars->{'blog_id'};
    require MT::Blog;
    my $blog = MT::Blog->load($blog_id, {cached_ok=>1});
    my $fmgr = $blog->file_mgr;

    my $app = MT->instance;
    my $umask = oct $app->config('UploadUmask');
    my $old = umask($umask);
    defined(my $bytes = $fmgr->put($uploadHandle, $file_location, 'upload'))
        or return $app->error($app->translate(
            "Error writing upload to '[_1]': [_2]", $file_location,
            $fmgr->errstr));
    umask($old);

# *** MT hooks

# *** MT does this, commented
#    
#    # Copy the file content to the correct location.
#    while (<$uploadHandle>) {
#        print UPLOAD;
#    }
#    
#    close UPLOAD;
#
# *** MT does this, commented
    
    return $filename;    
}


sub _saveField {
    my $this = shift;
    my $field = shift;
    my $value = shift;
    my $setValue = shift || 0;
    
    $value =~ s/^\s*(.*?)\s*$/$1/;
    if ($value eq '') {
        return;
    }
    
    $field = $this->_decodeFieldName($field);
    
    if ($this->{'instance'} && $this->{'instance'}->exists($field)) {
        if ($setValue) {
            # TODO make sure XML special chars are escaped
            $this->{'instance'}->setNodeText($field, $value);
        }
    }
    else {
        my @paths = split(/\//, $field);

        my $nodeName = $paths[$#paths];
        # remove the last element from the array
        $#paths = $#paths - 1;
        my $nodeCount = 1;
        if ($nodeName =~ /\[(.+?)\]/) {
            $nodeCount = $1;
            $nodeName =~ s/\[.+?\]//;           
        }
        # If there's nothing left in the path create the root node.
        if ($#paths == 0) {
            $this->{'instance'} = new XML::XPath(xml => "<$nodeName/>");
        }
        else {
            my $path = join("/", @paths);
            $this->_saveField($path, $value);            

            if ($this->{'instance'} && $this->{'instance'}->exists($path)) {
                # Create the new element if it's not an attibute
                if (substr($nodeName, 0, 1) ne "@") {
                    my $count = $this->{'instance'}->findvalue("count(" . $path . "/$nodeName)");
                    my $nodeset = $this->{'instance'}->find($path);
                    my $resultNode = $nodeset->get_node($nodeset->size());

                    my $node;
                    if ($count->value() < $nodeCount) {
                        for (my $i = $count->value(); $i < $nodeCount; $i++) {
                            $node = XML::XPath::Node::Element->new($nodeName);

                            $resultNode->appendChild($node);
                        }                        
                    }
                    
                    if ($setValue) {
                        my $value = XML::XPath::Node::Text->new($value);
                        $node->appendChild($value);
                    }                    
                }
                # if it is an attribute save the data.
                else {
                    $nodeName = substr($nodeName, 1);
                    my $node = XML::XPath::Node::Attribute->new($nodeName, $value);
                    my $nodeset = $this->{'instance'}->find($path);
                    my $resultNode = $nodeset->get_node(1);
                    $resultNode->appendAttribute($node);
                }
            }
        }
    }
}

sub _renderStart {
    my $this = shift;
    my $nodes = shift;
    my $mode = shift;
    my $base = shift || "";
    my $instanceID = shift || "";
    my $orientation = shift || "";
    
    if ($base && $instanceID) {
        $base = $base . "[" . $instanceID . "]";        
    }
    
    my $style = "";
    # If the elements of the group should all be on one row we have to float them.
    if ($orientation eq "horizontal") {
        $style = "float: left;";        
    }
    
    my $result = "";
    my @nodes;
    if ($nodes->isa("XML::XPath::NodeSet")) {
        @nodes = $nodes->get_nodelist();        
    }
    else {
        @nodes = $nodes->getChildNodes();        
    }

    foreach my $node (@nodes) {
        my $nodeName = $node->getName() || "";
        if ($nodeName eq "group") {
            $result .= $this->_renderGroup($node, $mode, $base, $style);                                            
        }
        elsif ($nodeName eq "field") {
            $result .= $this->_renderField($node, $mode, $base, $style);            
        }
        elsif ($nodeName eq "if") {
            $result .= $this->_renderIf($node, $mode, $base);            
        }
        elsif ($nodeName eq "attribute") {
            $result .= $this->_renderAttribute($node, $mode, $base, $nodes);            
        }
        elsif ($nodeName eq "field-map") {
            $result .= $this->_renderFieldMap($node, $mode, $base);            
        }
        elsif ($nodeName) {
            # For all other types we recursively walk down the tree
            $result .= $this->_serializeNode($node, $mode, $base);                            
        }  
        elsif ($node->getNodeType() != XML::XPath::Node::COMMENT_NODE) {
            $result .= $node->getValue();
        }              
    }
    
    # Force the end of the row.
    if ($mode eq "simpleeditor") {
        $result .= "<div style='clear:left;'></div>";            
    }
    
    return $result;
}

sub _renderFieldMap {    
    my $this = shift;
    my $field = shift;
    my $mode = shift;
    my $base = shift;

    my $contentPath = $this->_getContentPath($field, $base);
    
    my $result = $this->{'instance'}->findvalue($contentPath);
     
    # check to see if it matches any of the values in the map entries
    my @maps = $this->{'view'}->find("map", $field)->get_nodelist();
    foreach my $map (@maps) {
        my $input = $map->getAttribute("input");
        # If so return the value from the map instead of the original
        if ($result eq $input) {
            $result = $map->getAttribute("output");
        }                
    }        
    
    return $result;
}

sub _renderAttribute {   
    my $this = shift;
    my $field = shift;
    my $mode = shift;
    my $base = shift;
    my $parent = shift;
    
    # If we're in view mode we set the attribute
    if ($mode eq "view") {
        # Get the name of the attribute we're setting 
        my $name = $field->getAttribute('name');
        
        # Get the value for the atttribute
        my $value = $this->_renderStart($field, $mode, $base);   
        
        # Set the attribute to the parent element
        my $node = XML::XPath::Node::Attribute->new($name, $value);
        $parent->appendAttribute($node);
    } 
            
    # Otherwise we don't do anything
    return "";
}

sub _renderIf {    
    my $this = shift;
    my $field = shift;
    my $mode = shift;
    my $base = shift || "";

    my $contentPath = $this->_getContentPath($field, $base);
    my $op = $field->getAttribute('op');
    	    
    my $result = "";
    # See how many instances of the field set exist in the instance document.
    my @nodes = $this->{'instance'}->find($contentPath)->get_nodelist();
    if ($op eq 'not') {        
        if (scalar @nodes < 1 || $nodes[0]->getNodeValue() eq "") {
            $result .= $this->_renderStart($field, $mode, $base);
        }
    }
    else {
        if (scalar @nodes > 1) {
            $result .= $this->_renderStart($field, $mode, $base);
        }
        elsif (scalar @nodes == 1){
            my $value = $this->{'instance'}->findvalue($contentPath);
            if ($value) {
                $result .= $this->_renderStart($field, $mode, $base);                
            }
        }        
    }
    	    
    return $result;
}

sub _renderGroup {
    my $this = shift;
    my $field = shift;
    my $mode = shift;
    my $base = shift || "";
        
    my $contentPath = $this->_getContentPath($field, $base);
    my $orientation = $field->getAttribute('orientation');
    my $repeat = $field->getAttribute('repeat');
    my $label = $field->getAttribute('label');
    my $addlabel = $field->getAttribute('addlabel');

    my $result = "";
    if ($mode eq "simpleeditor") {
        $result .= "<div style='clear: left;'>";
                
        if ($label) {
            $result .= "<fieldset><legend class='sb_grouplegend'>$label</legend>";            
        }
        
        $result .= "<div style='clear: all;'>";          
    }
    
    if ($repeat) {
        # See how many instances of the field set exist in the instance document.
        my $count = 1;
        if ($this->{'instance'}) {
            $count = $this->{'instance'}->find("count(" . $contentPath . ")");               
        }

        if ($count > 0) {
            # That's how many times we need to render the group.
            for (my $i = 1; $i <= $count; $i++) {            
                $result .= $this->_renderStart($field, $mode, $contentPath, $i, $orientation);
            }            
        }
        else {
            $result .= $this->_renderStart($field, $mode, $contentPath, "", $orientation);            
        }

        # for multi-valued entries we add a button so you can create a new row. 
        # This will come back into the object as part of the form and we'll add a 
        # row to the instance.
        if ($mode eq "simpleeditor") {
            if (! $addlabel) {
                $addlabel = "Add row";
            }
            
            $result .= "<input type='submit' name='sb_action_add_row" . $contentPath . "' value='" . $addlabel . "'/>";                
        }
    }    
    else {
        $result .= $this->_renderStart($field, $mode, $contentPath, "", $orientation);
    }

    if ($mode eq "simpleeditor") {
        $result .= "</div>";
        
        if ($label) {
            $result .= "</fieldset>";            
        }
        
        $result .= "</div>";           
    }
    
    return $result;
}

sub _renderField {
    my $this = shift;
    my $field = shift;
    my $mode = shift;
    my $base = shift || "";
    my $style = shift || "";

    my $type = $field->getAttribute("type");
    
    my $renderer = $this->_getRenderer($type);
    
    return $renderer->render($mode, $field, $base, $style);        
}

sub _getRenderer {
     my $this = shift;
     my $type = shift;
     
     # Dynamically create the renderer based on the type specified. The default is text.
     my $default = "MC::Text";
     my $name = $default;
     if ($type) {
         $name = "MC::" . ucfirst($type); 
     }

     # TODO:: need to handle dynamically loading modules and error conditions
     my $render;
     eval {
         $render = $name->new($this->{"view"}, $this->{"instance"}, $this->{"editWrapper"});
     };
     if ($@) {
         $render = $default->new($this->{"view"}, $this->{"instance"}, $this->{"editWrapper"});
     }
 
     return $render;
}

sub _serializeNode {
    my $this = shift;
    my $node = shift;
    my $mode = shift;
    my $base = shift || "";
    
    my $body = $this->_renderStart($node, $mode, $base);
    
    my $result = $this->_serializeStartTag($node);
    $result .= $body;
    $result .= $this->_serializeEndTag($node);                        
    return $result;
}

sub _serializeStartTag {
    my $this = shift;
    my $node = shift;

    my $result = "<" . $node->getName();
    my @attributes = $node->getAttributes();
    foreach my $attribute (@attributes) {
        $result .= " " . $attribute->getName() . "='" . $attribute->getNodeValue() . "'";        
    }
    $result .= ">";

    return $result;
}

sub _serializeEndTag {
    my $this = shift;
    my $node = shift;

    return "</" . $node->getName() . ">";
}

sub _buildDescriptorMap {
    my $this = shift;
    my $dir = shift;

    if ( ! -d $dir) {
       die ("Not a directory: $dir!");
    }

    if (opendir(DIR, $dir)) {
        my @files = readdir(DIR);
        foreach my $file (@files) {               
            if ($file ne "." || $file ne ".." ) { 
                if (! -d ($dir . "/" . $file) && $file =~ '.*\.xml$') {
                    $this->_readMapEntry($dir . "/" . $file);                   
                }
            }
        }
    }
}

sub _readMapEntry {
    my $this = shift;
    my $file = shift;
    
    open(FILE, "< $file");
    
    my $type = "";
    my $label = "";        
    my $category = "";
    
    my @lines = <FILE>;
    close(FILE);
    my $content = join("", @lines);
   
    if ($content =~ /<micro-content.*?type=[\"|\'](.*?)[\"|\'].*>/) {
        $type = $1;
        my @category = split("/", $type);
        $category = $category[0];
    } 
    
    if ($content =~ /<micro-content.*?label=[\"|\'](.*?)[\"|\'].*>/) {
        $label = $1;
    }   
        
    if (! ($type && $label)) {
        die("Invalid micro-content description in file $file");
    }
    
    my %entry;
    $entry{'label'} = $label;
    $entry{'category'} = $category;
    $entry{'path'} = $file;
    $entry{'type'} = $type;

    $this->{'map'}->{$type} = \%entry;
}

sub _decodeFieldName {
    my $this = shift;
    my $name = shift;
    
    $name =~ s/\|(.+?)\|/\[$1\]/;
    return $name;
}

sub _getContentPath {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    
    my $contentPath = $field->getAttribute('content');
    if ($contentPath && substr($contentPath, 0, 1) ne "/") {
        $contentPath = $base . "/" . $contentPath;
    }        
    
    if (! $contentPath) {
        $contentPath = $base;
    }
    
    return $contentPath;
}

package MC::Text;

sub new {
    my $class = shift;
    my $view = shift;
    my $instance = shift;
    my $editWrapper = shift;
    
    my $this = { view => $view, instance => $instance, editWrapper => $editWrapper };
    bless $this, $class;
           
    return $this;
}

sub render {
    my $this = shift;
    my $mode = shift;
    my $field = shift;
    my $base = shift;
    my $style = shift;

    if ($mode eq "custom") {
        return $this->renderEditor($field, $base, $style);
    }
    elsif ($mode eq "simpleeditor") {
        return $this->renderSimpleEditor($field, $base, $style);
    }
    else {
        return $this->renderView($field, $base, $style);
    }
}

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";
    
    my $contentPath = $this->_getContentPath($field, $base);
    my $length = $this->_getLength($field, $base);
    my $data = $this->_getData($field, $base);
    my $id = $field->getAttribute("id");

    $field = '<input type="text" name="' . $this->_encodeFieldName($contentPath) . 
        '" size="' . $length . '" value="' . $data . '" id="' . $id . '"/>';

    return $field;
}

sub renderSimpleEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";
    
    my $input = $this->renderEditor($field, $base, $style);
    my $label = $field->getAttribute("label");
    if ($this->{'editWrapper'}) {
        my $wrapper = $this->{'editWrapper'};
        return &$wrapper($label, $input, $style);                
    }
    
    return $input;
}

sub renderView {        
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";

    return $this->_getData($field, $base);        
}

# For fields types that the form contains mutliple controls this method 
# is used to pull the individual pieces back together into a single
# value. For other types it should never be called.
sub getValueFromComponents {
    my $components = shift;
    
    return "";
}

sub _getData {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    
    my $contentPath = $this->_getContentPath($field, $base);
    if ($this->{'instance'}) {
        return $this->{'instance'}->findvalue($contentPath);        
    }
    
    return "";
}

sub _getDataChild {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $childPath = shift || "";
    
    my $contentPath = $this->_getContentPath($field, $base);
    if ($this->{'instance'}) {
        return $this->{'instance'}->findvalue($contentPath . $childPath);        
    }
    
    return "";
}

sub _getContentPath {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    
    my $contentPath = $field->getAttribute('content');
    if ($contentPath && substr($contentPath, 0, 1) ne "/") {
        $contentPath = $base . "/" . $contentPath;
    }        
    
    if (! $contentPath) {
        $contentPath = $base;
    }
    
    return $contentPath;
}

sub _getLength {
    my $this = shift;
    my $field = shift;

    my $length = $field->getAttribute('length');
    if (! $length) {
        $length = 60;
    }
    return $length;
}

sub _getAttr {
    my $this = shift;
    my $field = shift;
    my $attr = shift;
    my $default = shift || "";

    my $value = $field->getAttribute($attr);
    if (! $value) {
        return $default;
    }
    
    return $value;
}

sub _encodeFieldName {
    my $this = shift;
    my $name = shift;
    
    # Escape the square brackets.
    $name =~ s/\[/\|/;
    $name =~ s/\]/\|/;
    return $name;
}


package MC::Textarea;
our @ISA = qw(MC::Text);

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";
    
    my $contentPath = $this->_getContentPath($field, $base);

    my $dataValue = $this->_getData($field, $base);
    my $cols = $field->getAttribute("cols");
    if (! $cols) {
        $cols = 80;
    }
    
    my $width = $field->getAttribute("width");
	
	if ($width) {
	    $width = ' style="width: ' . $width . ';"'; 
	}
	else {
	    $width = '';
	}
	
    return '<textarea name="' . $this->_encodeFieldName($contentPath) . '" cols="'. $cols . 
        '"' . $width . ' rows="10">' . $dataValue . '</textarea>';
}

package MC::Rating;
our @ISA = qw(MC::Text);

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";

    my $contentPath = $this->_getContentPath($field, $base);

    my $dataValue = $this->_getData($field, $base);

    my $min = $field->getAttribute("min");
    my $max = $field->getAttribute("max");
    if (! $min) {
        $min = 0;
    }        
    if (! $max) {
        $max = 5;
    }

    my $select = '<input type="hidden" name="' . $this->_encodeFieldName($contentPath . '/@max') . 
        '" value="' . $max . '"/>';
    $select .= '<input type="hidden" name="' . $this->_encodeFieldName($contentPath . '/@min') . 
        '" value="' . $min . '"/>';

    $select .= '<select name="' . $this->_encodeFieldName($contentPath) . '"><option value="">No rating</option>';    
    for (my $i = $min; $i <= $max; $i++) {
        if ($dataValue && $i == $dataValue->value()) {
            $select .= "<option selected='true' value='$i'>" . $i . " Star" . ($i == 1 ? "" : "s") . "</option>";
        }
        else {
            $select .= "<option value='$i'>" . $i . " Star" . ($i == 1 ? "" : "s") . "</option>";            
        }

    }
    $select .= '</select>';

    return $select;
}        

sub renderView {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";

    my $result = $this->_getData($field, $base);
    if ($result) { 
        my $max = $this->_getDataChild($field, $base, '/@max')->value;
        my $rating = $result->value;
        
        my $stars = "$rating out of $max";
        for (my $i = 0; $i < $max; $i++) {
            if ($i >= $rating) {
                $stars .= '<div class="sb-emptystar"> </div>';                
            }
            else {
                $stars .= '<div class="sb-fullstar"> </div>';                
            }
                        
        }
        return $stars;
    }
    else {
        return "No rating"
    }
}            


package MC::Select;
our @ISA = qw(MC::Text);

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";
    
    my $contentPath = $this->_getContentPath($field, $base);

    my $dataValue = $this->_getData($field, $base);

    my $select = '<select name="' . $this->_encodeFieldName($contentPath) . '">';
    my @options = $this->{'view'}->find("option", $field)->get_nodelist();
        
    foreach my $option (@options) {
        my $optionText = $option->string_value();

        # If there's a value attribute specified we'll use that            
        my $optionValue = $option->getAttribute("value");
        if (! $optionValue) {
            # Other wise we just use the optionText for the value
            $optionValue = $optionText;
        }

        if ($optionValue eq $dataValue) {
            $select .= "<option selected='true' value='" . $optionValue . "'>" . $optionText . "</option>";
        }
        else {
            $select .= "<option value='" . $optionValue . "'>" . $optionText . "</option>";            
        }
    }
    $select .= '</select>';
    
    return $select;
}

package MC::Link;
our @ISA = qw(MC::Text);

sub renderView {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";
    
    my $linkURL = $this->_getData($field, $base);
    return "<a href='$linkURL'>$linkURL</a>";
}

package MC::Image;
our @ISA = qw(MC::Text);

sub renderView {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";

    my $classData = $field->getAttribute("class");
    my $imageURL = $this->_getData($field, $base);
    if ($imageURL) {
        if ($classData) {
           return '<div class="' . $classData . '"><img src="' . $imageURL . '"/></div>';
        }
        else {
            return '<div><img src="' . $imageURL . '"/></div>';
       }            
    }           
}

package MC::Date;
our @ISA = qw(MC::Text);

use HTTP::Date qw(parse_date time2isoz);

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";

    my $contentPath = $this->_getContentPath($field, $base);
    my $value = $field->getAttribute("value");
    
    my $dataValue = $this->_getData($field, $base);
    my @date;
    if ($dataValue) {
        @date = parse_date($dataValue);        
    }
    else {
        @date = parse_date(time2isoz());
    }
    
    # This hidden field is required so that when the form is submitted we know which 
    # renderer to call to rebuild the data
    my $result = "<input type='hidden' name='" . $this->_encodeFieldName($contentPath) . "#type' value='date'/>";        
            
    $result .= $this->monthSelector($contentPath, @date);        
    $result .= $this->daySelector($contentPath, @date);
    $result .= $this->yearSelector($contentPath, @date);
    
    return $result;
}

sub monthSelector {
    my $this = shift;
    my $contentPath = shift;
    my @date = @_;
    
    my @months = ("January", "February", "March", "April", "May", "June", "July", 
        "August", "September", "October", "November", "December");
    
    # Need a selector for Month
    my $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#month'>";
    my $i = 1;
    foreach my $month (@months) {
        if ($i eq $date[1]) {
            $result .= "<option selected='true' value='$i'>$month</option>";                
        }
        else {
            $result .= "<option value='$i'>$month</option>";
        }

        $i++;
    }
    $result .= "</select>";
    
    return $result;
}

sub daySelector {
    my $this = shift;
    my $contentPath = shift;
    my @date = @_;
    
    my $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#day'>";
    for (my $i = 1; $i <= 31; $i++) {
        if ($i == $date[2]) {
            $result .= "<option selected='true'>$i</option>";
        }
        else {
            $result .= "<option>$i</option>";                
        }
    }
    $result .= "</select>";
    
    return $result;        
}

sub yearSelector {
    my $this = shift;
    my $contentPath = shift;
    my @date = @_;
    
    my $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#year'>";
    for (my $i = 1970; $i < 2036; $i++) {
        if ($i == $date[0]) {
            $result .= "<option selected='true'>$i</option>";            
        }
        else {
            $result .= "<option>$i</option>";                
        }
    }
    $result .= "</select>";  
    
    return $result;  
}

# Dates are edited with multiple form fields so we have to put the value back together from
# the individual components. 
sub getValueFromComponents {
    my $this = shift;
    my $components = shift;
    
    return $components->{'year'} . "-" . $this->_padNumber($components->{'month'}) . "-" . 
        $this->_padNumber($components->{'day'});
}   

sub _padNumber {
    my $this = shift;
    my $number = shift;

    if (length($number) < 2) {
        $number = "0" . $number;
    }
    
    return $number;
}

package MC::Datetime;
our @ISA = qw(MC::Date);

use HTTP::Date qw(parse_date time2isoz str2time);
use POSIX;

sub renderView { 
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";

    my $contentPath = $this->_getContentPath($field, $base);

    my $result = $this->_getData($field, $base);
    my $date;
    if ($result) {
        $date = str2time($result);
    }
    
    return strftime("%a, %d %b %Y at %l:%M %p", localtime($date));
}

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";

    my $contentPath = $this->_getContentPath($field, $base);
    my $value = $field->getAttribute("value");
    
    my $dataValue = $this->_getData($field, $base);
    my @date;
    if ($dataValue) {
        @date = parse_date($dataValue);        
    }
    else {
        @date = parse_date(time2isoz());
    }
    
    # This hidden field is required so that when the form is submitted we know which 
    # renderer to call to rebuild the data
    my $result = "<input type='hidden' name='" . $this->_encodeFieldName($contentPath) . "#type' value='datetime'/>";        
    
    $result .= $this->monthSelector($contentPath, @date);        
    $result .= $this->daySelector($contentPath, @date);
    $result .= $this->yearSelector($contentPath, @date);
    $result .= " - ";
    # TODO: the timezone handling of this is probably too sloppy
    $result .= $this->hoursSelector($contentPath, @date);
    $result .= $this->minutesSelector($contentPath, @date);
    $result .= $this->amPmSelector($contentPath, @date);
    
    return $result;        
}

sub hoursSelector {
    my $this = shift;
    my $contentPath = shift;
    my @date = @_;
    
    my $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#hours'>";
    for (my $i = 1; $i <= 12; $i++) {
        if ($date[3] > 12 && ($i + 12) == $date[3]) {
            $result .= "<option selected='true'>$i</option>";
        }
        elsif ($date[3] <= 12 && $i == $date[3]) {
            $result .= "<option selected='true'>$i</option>";
        }
        else {
            $result .= "<option>$i</option>";                
        }
    }
    $result .= "</select>";
    
    return $result;        
}

sub minutesSelector {
    my $this = shift;
    my $contentPath = shift;
    my @date = @_;
    
    my $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#minutes'>";
    for (my $i = 0; $i <= 59; $i++) {
        if ($i == $date[4]) {
            $result .= "<option selected='true'>$i</option>";
        }
        else {
            $result .= "<option>$i</option>";                
        }
    }
    $result .= "</select>";
    
    return $result;        
}    

sub amPmSelector {
    my $this = shift;
    my $contentPath = shift;
    my @date = @_;
    
    my $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#ampm'>";
    if ($date[3] < 12) {
        $result .= "<option selected='true'>AM</option>";
        $result .= "<option>PM</option>";                
    }
    else {            
        $result .= "<option>AM</option>";                
        $result .= "<option selected='true'>PM</option>";
    }
    $result .= "</select>";
    
    return $result;
}

# Datetimes are edited with multiple form fields so we have to put the value back together from
# the individual components.
sub getValueFromComponents {
    my $this = shift;
    my $components = shift;
    
    # We're storing dates in 24 hour format, but the form is sending 12 hour format so we need to convert.
    my $hours = $components->{'hours'};
    if ($components->{'ampm'} eq 'PM' && $hours != 12) {
        $hours = $hours + 12;
    }
    elsif ($components->{'ampm'} eq 'AM' && $hours == 12) {
        $hours = 0;
    }
    
    return $components->{'year'} . "-" . $this->_padNumber($components->{'month'}) . "-" . $this->_padNumber($components->{'day'}) . 
        "T" . $this->_padNumber($hours) . ":" . $this->_padNumber($components->{'minutes'}) . ":00";
}    

package MC::Radio; 
our @ISA = qw(MC::Text);

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";

    my $contentPath = $this->_getContentPath($field, $base);
    my $value = $field->getAttribute("value");
    my $dataValue = $this->_getData($field, $base);
    if ($value == $dataValue) {
        return "<input type='radio' name='" . $this->_encodeFieldName($contentPath) . "' value='" . $value . "' checked='YES'/>";            
    }
    else {
        return "<input type='radio' name='" . $this->_encodeFieldName($contentPath) . "' value='" . $value . "'/>";
    }

}

package MC::Checkbox; 
our @ISA = qw(MC::Text);

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";

    my $contentPath = $this->_getContentPath($field, $base);
    my $value = $field->getAttribute("value");
    my $dataValue = $this->_getData($field, $base);

    if ($dataValue == $value) {
        return "<input type='checkbox' name='" . $this->_encodeFieldName($contentPath) . "' value='" . $value . "' checked='YES'/>";            
    }
    else {
        return "<input type='checkbox' name='" . $this->_encodeFieldName($contentPath) . "' value='" . $value . "'/>";
    }

}


##### Action renderers

package MC::Uploadfile; 
our @ISA = qw(MC::Text);

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";

#	if (!$this->parent->fileUploadDirectory)
#	    return '<p style="width: 200px">(Uploads disabled!  To enable, <a href="sb-options.php">configure an upload directory</a>.)</p>';

    my $contentPath = $this->_getContentPath($field, $base);
    my $dataValue = $this->_getData($field, $base);

    $this->{'fileType'} = $this->_getAttr($field, "filetype", "file");

    my $result = $this->displayData($dataValue);
    $result .= "<input type='file' name='sb_action_upload_file_name". $this->_encodeFieldName($contentPath) . "' size='30' /><br />"; 
    $result .= "<input type='submit' name='sb_action_upload_file" . $this->_encodeFieldName($contentPath) . 
        "' value='Upload " . ucfirst($this->{'fileType'}) . "'/>";
    $result .= "<input type='hidden' id='image-upload-data' name='" . $this->_encodeFieldName($contentPath) . "' value='" . $dataValue . "'/>";
        
    return $result;
}
    
sub displayData {
    my $this = shift;
    my $dataValue = shift;
    
    my $html = "";
    my $caption = "";
    if ($dataValue) {    
        my $filename = $this->basename($dataValue);
        $caption = "Click to open " . $this->{'fileType'} . " file: $filename";
        if ($this->{'fileType'} eq "image") {
            $html = "<img id=\"image-upload\" width=\"200\" src=\"$dataValue\"><br>";
            $caption = "Click to view full size image: $filename";
        }
    
        if ($caption) {
            $html .= "(<a target=\"_new\" href='" . $dataValue . "'>$caption</a>)<br />";
        }    
    }
    else {
        $html = "<div id='image-upload' style='display:none'></div>";            
    }

    return $html;
}

sub basename {
    my $this = shift;
    my $filename = shift;
    
    my @pieces = split(/\//, $filename);
    return $pieces[$#pieces]
}

package MC::Uploadimage; 
our @ISA = qw(MC::Text);

sub displayData {
    my $this = shift;
    my $dataValue = shift;
    if ($dataValue) {
        return "<img src='" . $dataValue . "'/><br />";        
    }
    
    return "";    
}

package MC::DeleteRow;
our @ISA = qw(MC::Text);

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";
    
    my $contentPath = $this->_getContentPath($field, $base);
    return "<input type='submit' name='sb_action_delete_row" . 
        $this->_encodeFieldName($contentPath) . "' value='Delete Row'/>";
}


package MC::AddRow;
our @ISA = qw(MC::Text);

sub renderEditor {
    my $this = shift;
    my $field = shift;
    my $base = shift || "";
    my $style = shift || "";
    
    my $contentPath = $this->_getContentPath($field, $base);
    return "<input type='submit' name='sb_action_add_row" . 
        $this->_encodeFieldName($contentPath) . "' value='Add Row'/>";
}

1;
