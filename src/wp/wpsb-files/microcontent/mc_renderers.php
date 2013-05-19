<?php
class MCUploadfile extends MCText {
    function renderEditor($field, $base="", $style="") {
        if (!$this->parent->fileUploadDirectory) {
	        return '<p style="width: 200px">(Uploads disabled!  To enable, <a href="sb-options.php">configure an upload directory</a>.)</p>';
        }
        
        $contentPath = $this->_getContentPath($field, $base);
        $dataValue = $this->instance->getData($contentPath);
	    
	    $this->fileType = $this->_getAttr($field, "filetype", "file");

        $result = $this->displayData($dataValue);
        $result .= '<input type="file" name="sb_action_upload_file_name'. $this->_encodeFieldName($contentPath) . 
            '" value="Choose File" size="35"/><br />';
        $result .= '<input type="submit" name="sb_action_upload_file' . $this->_encodeFieldName($contentPath) . 
            '" value="Upload '.ucfirst($this->fileType).'"/>';
        $result .= '<input type="hidden" id="image-upload-data" name="' . $this->_encodeFieldName($contentPath) . '" value="' . $dataValue . '"/>';
        
        return $result;
    }
    
    function displayData($dataValue) {
        if (! empty($dataValue)) {
	    
            $filename = basename($dataValue);
            $html = ""; $caption = "Click to open $this->fileType file: $filename";
            switch ($this->fileType) {
                case "image":
                    $html = "<img id=\"image-upload\" width=\"200\" src=\"$dataValue\"><br>";
                    $caption = "Click to view full size image: $filename";
                    break;
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
}

class MCDeleteRow extends MCText {
    function renderEditor($field, $base="", $style="") {
        $contentPath = $this->_getContentPath($field, $base);
        return "<input type='submit' name='sb_action_delete_row" . $this->_encodeFieldName($contentPath) . "' value='Delete Row'/>";
    }
}

class MCAddRow extends MCText {
    function renderEditor($field, $base="", $style="") {
        $contentPath = $this->_getContentPath($field, $base);
        return "<input type='submit' name='sb_action_add_row" . $this->_encodeFieldName($contentPath) . "' value='Add Row'/>";
    }
}


class MCHidden extends MCText {
    function renderEditor($field, $base="", $style="") {
        $contentPath = $this->_getContentPath($field, $base);
        $value = $this->_getValue($field, $base);

        return "<input type='hidden' name='" . $this->_encodeFieldName($contentPath) . "' value='" . $value . "'/>";
    }
}

class MCDate extends MCText {
    function renderEditor($field, $base="", $style="") {
        $contentPath = $this->_getContentPath($field, $base);
        $value = $this->_getValue($field, $base);
        
        $dataValue = $this->instance->getData($contentPath);
        if (empty($dataValue)) {
            $date = getdate();
        }
        else {
            $date = getdate(strtotime($dataValue));
        }
        
        # This hidden field is required so that when the form is submitted we know which 
        # renderer to call to rebuild the data
        $result = "<input type='hidden' name='" . $this->_encodeFieldName($contentPath) . "#type' value='date'/>";        
                
        $result .= $this->monthSelector($contentPath, $date);        
        $result .= $this->daySelector($contentPath, $date);
        $result .= $this->yearSelector($contentPath, $date);
        
        return $result;
    }
    
    function monthSelector($contentPath, $date) {
        $months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
        # Need a selector for Month
        $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#month'>";
        $i = 1;
        foreach ($months as $month) {
            if ($month == $date['month']) {
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
    
    function daySelector($contentPath, $date) {
        $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#day'>";
        for ($i = 1; $i <= 31; $i++) {
            if ($i == $date['mday']) {
                $result .= "<option selected='true'>$i</option>";
            }
            else {
                $result .= "<option>$i</option>";                
            }
        }
        $result .= "</select>";
        
        return $result;        
    }
    
    function yearSelector($contentPath, $date) {
        $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#year'>";
        for ($i = 1970; $i < 2036; $i++) {
            if ($i == $date['year']) {
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
    function getValueFromComponents($components) {
        return $components['year'] . "-" . $this->_padNumber($components['month']) . "-" . 
            $this->_padNumber($components['day']);
    }
    
    function _padNumber($number) {
        if (strlen($number) < 2) {
            $number = "0" . $number;
        }
        
        return $number;
    }    
}

class MCDatetime extends MCDate {
    function renderView($field, $base="", $style) { 
        $contentPath = $this->_getContentPath($field, $base);

        $result = $this->instance->match($contentPath);
        if (count($result) > 0) {
	    $date = strtotime($this->instance->getData($result[0]));
	    return strftime("%a, %d %b %Y at %l:%M %p", $date);
	}
    }            

    function renderEditor($field, $base="", $style="") {
        $contentPath = $this->_getContentPath($field, $base);
        $value = $this->_getValue($field, $base);
        
        $dataValue = $this->instance->getData($contentPath);
        if (empty($dataValue))
            $date = getdate();
        else
            $date = getdate(strtotime($dataValue));
        
        # This hidden field is required so that when the form is submitted we know which 
        # renderer to call to rebuild the data
        $result = "<input type='hidden' name='" . $this->_encodeFieldName($contentPath) . "#type' value='datetime'/>";        
        
        $result .= $this->monthSelector($contentPath, $date);        
        $result .= $this->daySelector($contentPath, $date);
        $result .= $this->yearSelector($contentPath, $date);
        $result .= " - ";
        # TODO: the timezone handling of this is probably too sloppy
        $result .= $this->hoursSelector($contentPath, $date);
        $result .= $this->minutesSelector($contentPath, $date);
        $result .= $this->amPmSelector($contentPath, $date);
        return $result;        
    }
    
    function hoursSelector($contentPath, $date) {
        $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#hours'>";
        for ($i = 1; $i <= 12; $i++) {
            if ($date['hours'] > 12 && ($i + 12) == $date['hours']) {
                $result .= "<option selected='true'>$i</option>";
            }
            elseif ($date['hours'] <= 12 && $i == $date['hours']) {
                $result .= "<option selected='true'>$i</option>";
            }
            else {
                $result .= "<option>$i</option>";                
            }
        }
        $result .= "</select>";
        
        return $result;        
    }
    
    function minutesSelector($contentPath, $date) {
        $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#minutes'>";
        for ($i = 0; $i <= 59; $i++) {
            if ($i == $date['minutes']) {
                $result .= "<option selected='true'>$i</option>";
            }
            else {
                $result .= "<option>$i</option>";                
            }
        }
        $result .= "</select>";
        
        return $result;        
    }    
    
    function amPmSelector($contentPath, $date) {
        $result = "<select name='" . $this->_encodeFieldName($contentPath) . "#ampm'>";
        if ($date['hours'] < 12) {
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
    function getValueFromComponents($components) {
        # We're storing dates in 24 hour format, but the form is sending 12 hour format so we need to convert.
        $hours = $components['hours'];
        if ($components['ampm'] == 'PM' && $hours != 12) {
            $hours = $hours + 12;
        }
        elseif ($components['ampm'] == 'AM' && $hours == 12) {
            $hours = 0;
        }
        
        return $components['year'] . "-" . $this->_padNumber($components['month']) . "-" . $this->_padNumber($components['day']) . 
            "T" . $this->_padNumber($hours) . ":" . $this->_padNumber($components['minutes']) . ":00";
    }    
}

class MCRadio extends MCText {
    function renderEditor($field, $base="", $style="") {
        $contentPath = $this->_getContentPath($field, $base);
        $value = $this->_getValue($field, $base);
        
        $dataValue = $this->instance->getData($contentPath);
        if ($value == $dataValue) {
            return "<input type='radio' name='" . $this->_encodeFieldName($contentPath) . "' value='" . $value . "' checked='YES'/>";            
        }
        else {
            return "<input type='radio' name='" . $this->_encodeFieldName($contentPath) . "' value='" . $value . "'/>";
        }

    }
}

class MCCheckbox extends MCText {
    function renderEditor($field, $base="", $style="") {
        $contentPath = $this->_getContentPath($field, $base);
        $value = $this->_getValue($field, $base);
        $dataValue = $this->instance->getData($contentPath);

        if ($dataValue == $value) {
            return "<input type='checkbox' name='" . $this->_encodeFieldName($contentPath) . "' value='" . $value . "' checked='YES'/>";            
        }
        else {
            return "<input type='checkbox' name='" . $this->_encodeFieldName($contentPath) . "' value='" . $value . "'/>";
        }

    }
}

class MCTextarea extends MCText {
    function renderEditor($field, $base="", $style) {
        $contentPath = $this->_getContentPath($field, $base);
        $label = $this->_getLabel($field, $base);

        $dataValue = $this->instance->getData($contentPath);
	$cols = intval($this->_getAttr($field, "cols", 80));
	$width = $this->_getAttr($field, "width");
	if ($width) $width = ' style="width: '.$width.';"'; else $width = '';
        return '<textarea name="' . $this->_encodeFieldName($contentPath) . '" cols="'.$cols.'"'.$width.' rows="10">' . $dataValue . '</textarea>';
    }   
}

class MCSelect extends MCText {
    function renderEditor($field, $base="", $style) {
        $contentPath = $this->_getContentPath($field, $base);
        $label = $this->_getLabel($field, $base);

        $dataValue = $this->instance->getData($contentPath);

        $select = '<select name="' . $this->_encodeFieldName($contentPath) . '">';
        $options = $this->view->match($field . "/option");
        foreach ($options as $option) {
            $optionText = $this->view->getData($option);
            
            # If there's a value attribute specified we'll use that            
            $optionValue = $this->view->getAttributes($option, "value");
            if ($optionValue === false) {
                # Other wise we just use the optionText for the value
                $optionValue = $optionText;
            }
            
            if ($optionValue == $dataValue) {
                $select .= "<option selected='true' value='" . $optionValue . "'>" . $optionText . "</option>";
            }
            else {
                $select .= "<option value='" . $optionValue . "'>" . $optionText . "</option>";            
            }

        }
        $select .= '</select>';
        return $select;
    }
}

class MCRating extends MCText {
    function renderEditor($field, $base="", $style) {
        $contentPath = $this->_getContentPath($field, $base);
        $label = $this->_getLabel($field, $base);

        $dataValue = $this->instance->getData($contentPath);
        if (!$dataValue && $dataValue != "0") $dataValue = false; // handle "" etc as null values, not 0 out of 5.
        
        $min = $this->view->getData($field . "/@min");
        $max = $this->view->getData($field . "/@max");
	$min = empty($min) ? 0 : intval($min);
	$max = empty($max) ? 5 : intval($max);

        $select = '<input type="hidden" name="' . $this->_encodeFieldName($contentPath . "/@max") . 
            '" value="' . $max . '"/>';
        $select .= '<input type="hidden" name="' . $this->_encodeFieldName($contentPath . "/@min") . 
            '" value="' . $min . '"/>';

        $select .= '<select name="' . $this->_encodeFieldName($contentPath) . '"><option value="">No rating</option>';
        for ($i = $min; $i <= $max; $i++) {
            if ($dataValue !== false && $i == $dataValue) {
                $select .= "<option selected='true' value='$i'>" . $i . " Star" . ($i==1 ? "" : "s") . "</option>";
            }
            else {
                $select .= "<option value='$i'>" . $i . " Stars</option>";            
            }

        }
        $select .= '</select>';

        return $select;
    }        
    
    function renderView($field, $base="", $style) { 
        $contentPath = $this->_getContentPath($field, $base);

        $result = $this->instance->match($contentPath);
        if (count($result) > 0) {
            $max = intval($this->instance->getData($result[0] . "/@max"));
            $rating = $this->instance->getData($result[0]);
            if ($rating === false || $rating === "") return "No rating";
            $rating = intval($rating);
            
            $stars = "$rating out of $max";
            for ($i = 0; $i < $max; $i++) {
                if ($i >= $rating) {
                    $stars .= '<div class="sb-emptystar"> </div>';                
                }
                else {
                    $stars .= '<div class="sb-fullstar"> </div>';                
                }
            }
            return $stars . '<div style="clear: left"></div>';
        }
    }            
}

class MCLink extends MCText {
    function renderView($field, $base="", $style) { 
        $linkURL = $this->_getData($field, $base);
        return "<a href='$linkURL'>$linkURL</a>";
    }
}

class MCImage extends MCText {
    function renderView($field, $base="", $style) {        
        $classData = $this->_getClass($field, $base);
        $imageURL = $this->_getData($field, $base);
        if ($imageURL) {
            if ($classData) {
               return '<div class="' . $classData . '"><img src="' . $imageURL . '"/></div>';
            }
            else {
                return '<div><img src="' . $imageURL . '"/></div>';
           }            
        }       
    }    
}

class MCText {
    var $view;
    var $instance;
    var $viewPath;
    var $editWrapper;
    var $viewWrapper;
    
    function MCText(&$view, &$instance, $editWrapper, $viewWrapper) {
        $this->view = $view;
        $this->instance = $instance;
        $this->editWrapper = $editWrapper;
        $this->viewWrapper = $viewWrapper;
    }
    
    function render($mode, $field, $base, $style) {
        if ($mode == "custom") {
            return $this->renderEditor($field, $base, $style);
        }
        else if ($mode == "simpleeditor") {
            return $this->renderSimpleEditor($field, $base, $style);
        }
        else {
            return $this->renderView($field, $base, $style);
        }
    }
    
    function renderEditor($field, $base="", $style) {
        $contentPath = $this->_getContentPath($field, $base);
        $length = $this->_getLength($field, $base);
        $data = $this->_getData($field, $base);
        $label = $this->_getLabel($field, $base);        
        $id = $this->_getId($field, $base);
        
        $field = '<input type="text" name="' . $this->_encodeFieldName($contentPath) . 
            '" size="' . $length . '" value="' . $data . '" id="' . $id . '"/>';

        return $field;
    }
    
    function renderSimpleEditor($field, $base="", $style) {
        $input = $this->renderEditor($field, $base, $style);
        $label = $this->_getLabel($field, $base);
        if ($this->editWrapper) {
            $wrapper = $this->editWrapper;
            
            return $wrapper($label, $input, $style);                
        }
        
        return $input;
    }
    
    function renderView($field, $base="", $style) {
    	$text = $this->_getData($field, $base);
    	// MCD files often do things like: <div><field content="description"/></div>.
    	// If the description includes a paragraph break ("\n\n"), Wordpress will
    	// insert </p><p>, which results in invalid HTML.  So we check here for that
    	// possibility and put <p></p> around the text first.
    	if (strpos($text, "\n") !== false)
    	    return "<p>$text</p>";
    	// If there aren't any linefeeds, though, we don't worry...
    	return $text;
    }

    function validate() {
        return TRUE;
    }

    # For fields types that the form contains mutliple controls this method 
    # is used to pull the individual pieces back together into a single
    # value. For other types it should never be called.
    function getValueFromComponents($components) {
        return "";
    }
    
    function _getData($field, $base="") {
        $contentPath = $this->_getContentPath($field, $base);

        $result = $this->instance->match($contentPath);
        if (count($result) > 0) {
            return html_entity_decode($this->instance->getData($result[0]));        
        }
        
        return "";
    }
    
    function _getContentPath($field, $base) {
        $contentPath = $this->view->getData($field . '/@content');
        if ($contentPath && $contentPath[0] != "/") {
            $contentPath = $base . "/" . $contentPath;
        }        
        
        if (! $contentPath) {
            $contentPath = $base;
        }

        return $contentPath;
    }

    function _getAttr($field, $attr, $default=FALSE) {
        $value = $this->view->getData("$field/@$attr");
	if ($value === FALSE) return $default;
	return $value;
    }

    function _getValue($field) {
        return $this->view->getData($field . '/@value');        
    }
    
    function _getLabel($field) {
        return $this->view->getData($field . '/@label');        
    }

    function _getClass($field) {
        return $this->view->getData($field . '/@class');        
    }
    
    function _getId($field) {
        return $this->view->getData($field . '/@id');        
    }
    
    function _getLength($field) {
        $length = $this->view->getData($field . '/@length');
        if (! $length) {
            $length = 60;
        }
        return $length;
    }
    
    ##### Utility functions
    function _encodeFieldName($name) {
        $name = str_replace("[", "|", $name);
        $name = str_replace("]", "|", $name);
        return $name;
    }
}
?>