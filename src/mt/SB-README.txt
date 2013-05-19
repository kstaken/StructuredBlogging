Structured Blogging for Movable Type
	Version ##VERSION##
	##DATE##
	http://structuredblogging.org/

Copyright (C) 2005 PubSub Concepts, Inc.

Author: Broadband Mechanics
	http://www.broadbandmechanics.com/
	
	Phillip Pearson
	Kimbro Staken
	Chad Everett
	Marc Senasac
	Marc Canter

Notes
=====

	This is an alpha release.  It might look a bit broken.  We'll
	be fixing it up over the following days/weeks/months.  Send
	bug reports using the mail form at
	http://www.myelin.co.nz/phil/email.php for the time being -
	we'd love to hear from you :-)

	Known bugs:

	- File uploads don't work

	- In Internet Explorer the 'create new entry' UI sometimes
          falls below the left sidebar due to excessively long input
          fields.

Prerequisites
=============

	- Movable Type 3.2

	  (It might work with 3.12, but hasn't been tested, so YMMV.)

	- MySQL database

	  (This won't work with a BSD database, sorry.)

	- The XML::Parse Perl module

	  We think this is fairly universal, so you probably don't
	  need to install this separately, but if
	  MT-StructuredBlogging just completely doesn't work for you,
	  check to see if you are missing XML::Parse.

	- The LWP::UserAgent Perl module

	  You need this if you want the "lookup" links to work (look
	  up books, CDs etc on Amazon and elsewhere).

Installation
============

	If you have shell access to your web server:
	-------------------------------------------
	
		* Change to the directory containing mt.cgi.
		
		* Unpack structuredblogging-mt-##VERSION##.tar.gz
		
		* Change to the structuredblogging-mt-##VERSION## directory.
		
		* Type:
		
			for f in `find . -type f`; do install -D $f ../$f; done
			
		* Now create an image upload directory, making sure to give the 
		web server access.  If you are on a typical hosting account, you 
		will have to make your image directory world writable.  For 
		example:
		
			mkdir -m 777 ../sbimages
	
	If you only have FTP access:
	---------------------------
	
		* Unpack structuredblogging-mt-##VERSION##.tar.gz somewhere.
		
		* Copy all files from the "plugins" directory into the "plugins" 
		directory on your web server.
		
		* Make sure that all files are accessible by your web server.
		
		* Now create an image upload directory, making sure to give the 
		web server access.  If you are on a typical hosting account, you 
		will have to make your image directory world writable.

Template setup
==============

	Before the plugin will be of any use to you, you need to edit your 
	templates.
	
	First edit the individual entry archive template (under "archives") and 
	add the following after <$MTEntryMore$>:
	
	If you want to see the Structured Blogging posts on the main index, edit 
	your main index template and add the following after 
	</MTEntryIfExtended>:
	
		<MTStructuredBloggingHTML>
		<MTStructuredBloggingXML>

Configuration
=============

	Once you have unpacked the files, go to the "plugins" page (under 
	"system overview") and make sure that BigPAPI and MT-StructuredBlogging 
	are functioning properly.  If either show errors, check that all the 
	files under plugins/StructuredBlogging are accessible to the web server.

	Now visit the Structured Blogging config page by clicking on
	"settings", then "plugins", then scrolling down to
	MT-StructuredBlogging and clicking on "Show Settings".  You
	will need to tell it the path to the image upload directory,
	and the URL of this directory.
	
	For example, if your blog is at http://www.myblog.com/, and the path to 
	the blog is /home/me/public_html/, and the path to the upload directory 
	is /home/me/public_html/sbimages/, then the URL to the upload directory 
	will be:
	
		http://www.myblog.com/sbimages/

	If you have an Amazon ID or an outputthis.org account, enter the details 
	on the config page.
	