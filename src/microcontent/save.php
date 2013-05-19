<?php
require_once("../wp/wpsb-files/microcontent/microcontent.php");

$mc = new MicroContent("../wp/wpsb-files/microcontent/descriptions", $_POST["type"], "", $_POST, "", 
    "/Users/kstaken/Sites/files", "http://127.0.0.1/~kstaken/files");

if (array_key_exists("xml", $_POST)) {
    header("Content-type: text/xml");    
    print $mc->getInstanceXml(); 
}
else if (array_key_exists("html", $_POST)){
    print $mc->getView();
}
else {
?>
    <div id="poststuff" style="clear: all">
    <form action="save.php" method="post" enctype="multipart/form-data">

    <?php

    # Required by the microcontent editor to know how to output the fields
    function print_field($title, $field, $style) {
        $wrapper = "<div style='%STYLE%'><fieldset id='titlediv'><legend><a>%TITLE%</a></legend><div>%FIELD%</div></fieldset></div>";
    
        $wrapped = str_replace("%TITLE%", $title, $wrapper);
        $wrapped = str_replace("%FIELD%", $field, $wrapped);
        $wrapped = str_replace("%STYLE%", $style, $wrapped);
    
        return $wrapped;
    }


    print $mc->getEditor();
    ?>
        <input type="hidden" name="type" value="<?=$_POST["type"]?>"/>

        <input type="submit" name="xml" value="View XML"/>
        <input type="submit" name="html" value="View HTML"/>
    </form>
    </div>

<?php } ?>