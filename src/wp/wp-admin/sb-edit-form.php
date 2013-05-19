<?php
include_once(dirname(__FILE__) . "/../wpsb-files/microcontent/IXR_Library.inc.php");

// Service endpoint for the output this web service
$client = new IXR_Client($SB_OUTPUTTHIS_ENDPOINT);

$sb_outputthis_message = "";
$sb_outputthis_username = get_option("sb-outputthis-username");
$sb_outputthis_password = get_option("sb-outputthis-password");

// Check to see if the outputthis service is enabled.
if (empty($sb_outputthis_username) || empty($sb_outputthis_password)) {
    $sb_outputthis_message = "You must <a href='sb-options.php'>configure</a> your outputthis.org account " .
        "information if you want to use the outputthis.org service.";
}
else {
    // Cache up to date?
    $sb_outputthis_cache = get_option("outputthis_target_cache");
    if ($sb_outputthis_cache && (time() - $sb_outputthis_cache[0] < 10 * 60)) {
        list($sb_outputthis_cache_time, $sb_outputthis_message, $sb_outputthis_targets) = $sb_outputthis_cache;
    }
    else {
        // Cache not up to date - get outputtthis targets from outtputthis.org
        if (! $client->query('outputthis.getPublishedTargets', $sb_outputthis_username, $sb_outputthis_password)) {
            $sb_outputthis_message = "The outputthis.org service is currently unavailable";
        }
        else {
            $sb_outputthis_targets = $client->getResponse();    
        }
        // Cache that
        update_option("outputthis_target_cache", array(
                          time(),
                          $sb_outputthis_message,
                          $sb_outputthis_targets));
    }
}

if (! $sb_mc_instance) {    	        
    $sb_mc_instance = new MicroContent($sb_mc_location, $sb_mc_type, "", $post = NULL, "", 
        get_option("sb-file-upload-directory"), get_option("sb-file-upload-url"));    
}
?>

<div class="wrap">
<?php if ($sb_mc_type) { ?>     
    <h2><?php _e("Write ".htmlspecialchars($sb_mc_instance->getLabel())." Post"); ?></h2>
    
    <form method="post" id="simple" enctype="multipart/form-data">
        <div id="poststuff" style="clear: all">
            
            <input type="hidden" id="sb_mc_type" name="sb_mc_type" value="<?=$sb_mc_type?>" />

            <?php if (isset($mode) && 'bookmarklet' == $mode) : ?>
                <input type="hidden" name="mode" value="bookmarklet" />
            <?php endif; ?>
            
            <input type="hidden" name="user_ID" value="<?php echo $user_ID ?>" />

            <?php if( $action == 'edit' ) : ?>
                <input type="hidden" name="action" value="editpost" />
            <?php else : ?>
                <input type="hidden" name="action" value="post" />
            <?php endif; ?>

            <?php if( isset( $post_ID )) : ?>
                <input type="hidden" name="post_ID" value="<?php echo $post_ID; ?>" />
            <?php endif; ?>

            <?php if( isset( $_GET['indirect_ref'] )) : ?>
                <input type="hidden" name="indirect_ref" value="<?php echo $_GET['indirect_ref']; ?>" />
            <?php elseif( isset( $_POST['indirect_ref'] )) : ?>
                <input type="hidden" name="indirect_ref" value="<?php echo $_POST['indirect_ref']; ?>" />
            <?php endif; ?>
            
                <fieldset id="categorydiv" class="sb_groupbox">
                  <legend><a href="http://wordpress.org/docs/reference/post/#category" title="<?php _e('Help on categories') ?>"><?php _e('Categories') ?></a></legend> 
            	  <div><?php dropdown_categories($default_post_cat); ?></div>
                </fieldset>
	      <table class="sb_boxtable" width="100%"><tr><td width="50%">
                <fieldset class="sb_titlediv">
                  <legend><a><?php _e( $sb_type . 'Post Title') ?></a></legend> 
            	  <div><input type="text" name="post_title" size="50" value="<?php echo $edited_post_title; ?>" id="title" /></div>
                </fieldset>
                <script type="text/javascript">
                <!--
                function focusit() {
                	// focus on first input field
                	document.getElementById('title').focus();
                }
                window.onload = focusit;
                //-->
                </script>
              </td><td width="50%">
                <fieldset id="sb_commentstatusdiv">
                      <legend><a href="http://wordpress.org/docs/reference/post/#comments" title="<?php _e('Help on comment status') ?>"><?php _e('Discussion') ?></a></legend> 
                	  <div>
                	  <input name="advanced_view" type="hidden" value="1" />
                	  <label for="comment_status" class="selectit">
                	      <input name="comment_status" type="checkbox" id="comment_status" value="open"  <?php checked($comment_status, 'open'); ?> />
                         <?php _e('Allow Comments') ?></label> 
                		 <label for="ping_status" class="selectit"><input name="ping_status" type="checkbox"  id="ping_status" value="open" <?php checked($ping_status, 'open'); ?> /> <?php _e('Allow Pings') ?></label>
                </div>
                </fieldset>
	      </td></tr></table>
<?php } ?>

<?php
$debug = 0;

// Required by the microcontent editor to know how to output the fields
function sb_print_field($title, $field, $style) {
    if (! $style) {
        $style = "clear: left;";
    }
    
    $wrapper = "<div style='%STYLE%'><fieldset id='sb_titlediv'><legend><a>%TITLE%</a></legend><div>%FIELD%</div></fieldset></div>";
    
    $wrapped = str_replace("%TITLE%", $title, $wrapper);
    $wrapped = str_replace("%FIELD%", $field, $wrapped);
    $wrapped = str_replace("%STYLE%", $style, $wrapped);
    
    return $wrapped;
}

function sb_display_menu($mc) {
    /*    // Display menu of categories
    foreach ($mc->getTopLevelCategories() as $key) {
	print '<li><a href="sb-post.php?sb_cat='.urlencode($key).'">'.htmlspecialchars($key)."</a></li>\n";
    }*/
    
    $sb_cat = trim(@$_REQUEST['sb_cat']);
    
    if ($sb_cat) {
       
	print "<h2>Write Structured ".htmlspecialchars($sb_cat)." Post</h2>";
	print "<p><b>Select ".htmlspecialchars($sb_cat)." type to create:</b></p>";
	
	// Display grouped by category
	$descriptors = $mc->getDescriptorMap();
	$display_types = array();
	foreach ($descriptors as $key => $descriptor) {
	    if (ucfirst($descriptor["category"]) == $sb_cat) {
		$display_types[$descriptor["label"]] = $descriptor;
	    }
	}

	sort($display_types);

	print "<ul>";
	foreach ($display_types as $descriptor) {
	    print '<li><a href="sb-post.php?&sb_mc_type=' . $descriptor["type"] . 
		'">' . $descriptor["label"] . "</a></li>\n";
	}
	print "</ul>";
    }
    else {
        // Something went wrong - so just display the old-style menu with all groups + types
	print "<h2>Write Structured Blogging Post</h2>";
	print "<p><b>What type of Structured Blogging post would you like to create?</b>: <br />";
	$contentTypes = $mc->getDescriptorMap();
	$categories = '';
	
        // Group the descriptors by category
	foreach ($contentTypes as $key => $descriptor) {
	    $categories[$descriptor["category"]][] = $descriptor;
	}
	
	// Display grouped by category
	foreach ($categories as $key => $descriptorList) {
	    print '<fieldset><legend><a href="sb-post.php?sb_cat='.urlencode(ucfirst($key)).'">'. htmlspecialchars(ucfirst($key)) . "</a></legend>\n";
	    
	    foreach ($descriptorList as $descriptor) {
		print '<a href="sb-post.php?&sb_mc_type=' . $descriptor["type"] .
		    '">' . $descriptor["label"] . "</a> &nbsp;&nbsp;\n";
	    }
	    print "</fieldset>";
	}
	print "</p>";
    }
}

if ($sb_mc_type) {
    $sb_mc_instance->registerEditWrapper("sb_print_field");
    # display the editor
    print $sb_mc_instance->getEditor();    
?>

<div><fieldset id='titlediv'><legend><a>OutputThis.org</a></legend><div>            
<?php
if (empty($sb_outputthis_message)) {
    $i = 1;
    foreach ($sb_outputthis_targets as $service) {
?>

        <input type="checkbox" name="sb_outputthis_service[<?= $i - 1?>]" value="<?=$service["ID"]?>"> <?=$service["title"]?>
<?php 
        if (($i % 2) == 0) echo "<br/>";
        $i++;
    } 
}
else {
    echo "$sb_outputthis_message";
}
?>
</div></fieldset></div>

<?php
}
else {
    sb_display_menu($sb_mc_instance);    
}

?>

<?php if ($sb_mc_type) { ?>        
        <input type="hidden" name="post_pingback" value="1" id="post_pingback" />

        <p class="submit" style="clear: both">

    <?php if ('edit' == $action) : ?>
        <input name="deletepost" class="button" type="submit" id="deletepost" value="<?php _e('Delete this post') ?>" 
            <?php echo "onclick=\"return confirm('" . sprintf(__("You are about to delete this post \'%s\'\\n  \'Cancel\' to stop, \'OK\' to delete."), addslashes($edited_post_title) ) . "')\""; ?> />

	<?php else: ?>

        <input name="saveasdraft" type="submit" id="saveasdraft"  value="<?php _e('Save as Draft') ?>" /> 
        <input name="saveasprivate" type="submit" id="saveasprivate" value="<?php _e('Save as Private') ?>" />

    <?php endif; ?>

    <?php if ( 1 < $user_level || (1 == $user_level && 2 == get_option('new_users_can_blog')) ) : ?>
        <input name="publish" type="submit" id="publish"  style="font-weight: bold;" value="<?php if ('edit' == $action) _e('Save'); else _e('Publish'); ?>" /> 
    <?php endif; ?>

          <input name="referredby" type="hidden" id="referredby" value="<?php if (isset($_SERVER['HTTP_REFERER'])) echo urlencode($_SERVER['HTTP_REFERER']); else echo urlencode(@$_REQUEST['indirect_ref']) ?>" />
        </p>

        <?php do_action('simple_edit_form', ''); ?>
<?php } ?>
        </div>
    </form>

    <script type="text/javascript">
        lookupAddLinks('sb-get.php', "<?= get_option('sb-amazon-site') ?>", "<?= get_option('sb-amazon-affiliate-code') ?>");
    </script>
    
</div>