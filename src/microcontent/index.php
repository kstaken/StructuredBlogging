<h3>Types with sample data</h3>
<table>
    <tr>
    <th>Content type</th><th>Actions</th>
    </tr>
    <tr>
        <td>Songlist</td><td>
        <a href="edit.php?action=new&type=list/songlist">Create new</a> 
        <a href="edit.php?action=edit&type=list/songlist&instance=songlist-instance.xml">Edit existing</a>
        <a href="edit.pl?action=new&type=list/songlist">Create new (perl)</a> 
        <a href="edit.pl?action=edit&type=list/songlist&instance=songlist-instance.xml">Edit existing (perl)</a>

        <a href="songlist.xml">View MCD</a>
        </td>
    </tr>

        <tr>
            <td>Songlist using custom editor</td><td>
            <a href="edit.php?action=new&type=list/songlist-detail">Create new</a> 
            <a href="edit.php?action=edit&type=list/songlist-detail&instance=songlist-instance.xml">Edit existing</a>
            <a href="edit.pl?action=new&type=list/songlist-detail">Create new (perl)</a> 
            <a href="edit.pl?action=edit&type=list/songlist-detail&instance=songlist-instance.xml">Edit existing (perl)</a>
            <a href="songlist-detail.xml">View MCD</a>
            </td>
        </tr>
        <tr>
            <td>Hotel Review</td><td>
            <a href="edit.php?action=new&type=review/hotel">Create new</a> 
            <a href="edit.php?action=edit&type=review/hotel&instance=hotel-instance.xml">Edit existing</a>
            <a href="edit.pl?action=new&type=review/hotel">Create new (perl)</a> 
            <a href="edit.pl?action=edit&type=review/hotel&instance=hotel-instance.xml">Edit existing (perl)</a>

            <a href="hotel.xml">View MCD</a>

            </td>
        </tr>
        <tr>
            <td>Concert Event</td><td>
            <a href="edit.php?action=new&type=event/concert">Create new</a> 
            <a href="edit.php?action=edit&type=event/concert&instance=concert-event-instance.xml">Edit existing</a>
            <a href="edit.pl?action=new&type=event/concert">Create new (perl)</a> 
            <a href="edit.pl?action=edit&type=event/concert&instance=concert-event-instance.xml">Edit existing (perl)</a>

            <a href="concert-event.xml">View MCD</a>

            </td>
        </tr>
    </table>
    
<h3>Types without sample data</h3>
    <table>
        <tr>
        <th>Content type</th><th>Actions</th>
        </tr>

<?php
function _buildDescriptorMap($dir) {
   if ( ! is_dir($dir)) {
       die ("Not a directory: $dir!");
   }
   
   $map = Array();
   
   if ($root = @opendir($dir)) {
       while ($file = readdir($root)) {               
           if ($file == "." || $file == ".." ){ 
               continue; 
           }
           $pathinfo = pathinfo($file);
           if (! is_dir($dir . "/" . $file) && $pathinfo['extension'] == 'xml') {
               $entry = _readMapEntry($dir . "/" . $file);                                  
               $map[$entry['type']] = $entry;
           }
       }
   }
   
   return $map;
}

function _readMapEntry($file) {
    $handle = fopen($file, "r");
    $type = "";
    $label = ""; 
           
    if ($handle) {
        while (!feof($handle)) {
            $buffer = fgets($handle, 4096);
            if (preg_match("/<micro-content.*?type=[\"|\'](.*?)[\"|\'].*>/", $buffer, $match)) {
                $type = $match[1];
                $category = explode("/", $type);
                $category = $category[0];
            } 
            
            if (preg_match("/<micro-content.*?label=[\"|\'](.*?)[\"|\'].*>/", $buffer, $match)) {
                $label = $match[1];
            }   
            
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
    }
    
    return $entry;
}

$map = _buildDescriptorMap("../wp/wpsb-files/microcontent/descriptions");
foreach ($map as $key => $value) {
?>
<tr>
    <td><?= $value['label']?></td><td>
    <a href="edit.php?action=new&type=<?= $value['type']?>">Create new</a> 
    <a href="edit.pl?action=new&type=<?= $value['type']?>">Create new (perl)</a> 

    <a href="<?= $value['path']?>">View MCD</a>
    </td>
</tr>
<?php } ?>

</table>
