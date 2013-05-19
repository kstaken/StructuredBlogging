Structured Blogging for Wordpress 1.5
	Version ##VERSION##
	##DATE##
	http://structuredblogging.org/

Copyright (C) 2005 PubSub Concepts, Inc.

Author: Broadband Mechanics
	http://www.broadbandmechanics.com/
	
	Phillip Pearson
	Kimbro Staken
	Marc Senasac
	Marc Canter

General information on structured blogging, and up-to-date
documentation for the plugin (as well as future releases), is
maintained on the website:

http://structuredblogging.org/

This file contains directions for getting the plugin installed and up
and running on a previously configured wordpress host.

----------------------------------------------------
PLEASE NOTE

This plugin is designed to work with the 1.5 version of wordpress.  It will 
not work with earlier versions.

----------------------------------------------------
INSTALL

Installation requires copying files from the archive (the .tgz or .zip)
to the Wordpress directory.  The files in the archive are in a set of
directories that correspond to the Wordpress directories, so the whole
archive can be copied directly.  

Make sure to copy recursively, so all the files get copied.  The target
directory is the root of your Wordpress install - it should contain a lot
of files named wp-xxx.php, and several directories named wp-admin, wp-content,
wp-images, and so on.

On Linux, this should be something like

tar -xzf structuredblogging-wp-1.0.tgz
cd structuredblogging-wp-1.0
for f in `find . -type f`; do install -D $f /path/to/wordpress/installation/$f; done

On Windows, you should be able to copy-and-paste out of the windows 
explorer.  

----------------------------------------------------
CONFIGURE

Once the files are copied, you should be able to see the plugin in your
Wordpress admin screen (although it will be deactivated).  Go to your
admin screen (usually /wp-admin/ in a web browser) and click on "Plugins"
in the menu.  

On the plugins page, you should see a "Structured Blogging" plugin.  Click 
"Activate" on the left to turn it on.  That's it!  If you click on "Write" 
in the menu, you will now see new options for writing structured blog 
posts.  Remember that you can deactivate it at any time.

----------------------------------------------------
STYLE AND OPTIONS

By default, structured blogging uses its own stylesheet, since most themes
don't support it.  You can modify this stylesheet directly, or you can 
turn it off and add styles to your main stylesheet.  

Turning the stylesheet on and off is controlled by the structured blogging
options page.  You can access this page from the admin screen - select
"Options" from the menu, and then "Structured Blogging" from the submenu.
