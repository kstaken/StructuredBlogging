<div id="poststuff" style="clear: all">
<form action="save.php" method="post" enctype="multipart/form-data">

<?php
require_once("../wp/wpsb-files/microcontent/microcontent.php");

# Required by the microcontent editor to know how to output the fields
function print_field($title, $field, $style) {
    $wrapper = "<div style='%STYLE%'><fieldset id='titlediv'><legend><a>%TITLE%</a></legend><div>%FIELD%</div></fieldset></div>";
    
    $wrapped = str_replace("%TITLE%", $title, $wrapper);
    $wrapped = str_replace("%FIELD%", $field, $wrapped);
    $wrapped = str_replace("%STYLE%", $style, $wrapped);
    
    return $wrapped;
}

$data = NULL;
if ($_GET["action"] == "edit") {    
    $filename = dirname(__FILE__) . "/" . $_GET["instance"];
    $handle = fopen($filename, "rb");
    $data = fread($handle, filesize($filename));
    fclose($handle);
}

$mc = new MicroContent("../wp/wpsb-files/microcontent/descriptions", $_GET["type"], $data, $post = NULL, "", 
    "/Users/kstaken/Sites/files", "http://127.0.0.1/~kstaken/files");
$mc->registerEditWrapper("print_field");

print $mc->getEditor();
?>
    <input type="hidden" name="type" value="<?=$_GET["type"]?>"/>
    <input type="hidden" name="instance" value="<?=$_GET["instance"]?>"/>

    <input type="submit" name="xml" value="View XML"/>
    <input type="submit" name="html" value="View HTML"/>
</form>
</div>