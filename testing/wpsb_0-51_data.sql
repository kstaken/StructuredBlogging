-- MySQL dump 10.9
--
-- Host: localhost    Database: sb_test_wp_no_sb
-- ------------------------------------------------------
-- Server version	4.1.14-Debian_6-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wp_categories`
--

DROP TABLE IF EXISTS `wp_categories`;
CREATE TABLE `wp_categories` (
  `cat_ID` bigint(20) NOT NULL auto_increment,
  `cat_name` varchar(55) NOT NULL default '',
  `category_nicename` varchar(200) NOT NULL default '',
  `category_description` longtext NOT NULL,
  `category_parent` int(4) NOT NULL default '0',
  PRIMARY KEY  (`cat_ID`),
  KEY `category_nicename` (`category_nicename`)
);

--
-- Dumping data for table `wp_categories`
--


/*!40000 ALTER TABLE `wp_categories` DISABLE KEYS */;
LOCK TABLES `wp_categories` WRITE;
INSERT INTO `wp_categories` VALUES (1,'Uncategorized','uncategorized','',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `wp_categories` ENABLE KEYS */;

--
-- Table structure for table `wp_comments`
--

DROP TABLE IF EXISTS `wp_comments`;
CREATE TABLE `wp_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL auto_increment,
  `comment_post_ID` int(11) NOT NULL default '0',
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL default '',
  `comment_author_url` varchar(200) NOT NULL default '',
  `comment_author_IP` varchar(100) NOT NULL default '',
  `comment_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL default '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL default '0',
  `comment_approved` enum('0','1','spam') NOT NULL default '1',
  `comment_agent` varchar(255) NOT NULL default '',
  `comment_type` varchar(20) NOT NULL default '',
  `comment_parent` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`comment_ID`),
  KEY `comment_approved` (`comment_approved`),
  KEY `comment_post_ID` (`comment_post_ID`)
);

--
-- Dumping data for table `wp_comments`
--


/*!40000 ALTER TABLE `wp_comments` DISABLE KEYS */;
LOCK TABLES `wp_comments` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `wp_comments` ENABLE KEYS */;

--
-- Table structure for table `wp_linkcategories`
--

DROP TABLE IF EXISTS `wp_linkcategories`;
CREATE TABLE `wp_linkcategories` (
  `cat_id` bigint(20) NOT NULL auto_increment,
  `cat_name` tinytext NOT NULL,
  `auto_toggle` enum('Y','N') NOT NULL default 'N',
  `show_images` enum('Y','N') NOT NULL default 'Y',
  `show_description` enum('Y','N') NOT NULL default 'N',
  `show_rating` enum('Y','N') NOT NULL default 'Y',
  `show_updated` enum('Y','N') NOT NULL default 'Y',
  `sort_order` varchar(64) NOT NULL default 'rand',
  `sort_desc` enum('Y','N') NOT NULL default 'N',
  `text_before_link` varchar(128) NOT NULL default '<li>',
  `text_after_link` varchar(128) NOT NULL default '<br />',
  `text_after_all` varchar(128) NOT NULL default '</li>',
  `list_limit` int(11) NOT NULL default '-1',
  PRIMARY KEY  (`cat_id`)
);

--
-- Dumping data for table `wp_linkcategories`
--


/*!40000 ALTER TABLE `wp_linkcategories` DISABLE KEYS */;
LOCK TABLES `wp_linkcategories` WRITE;
INSERT INTO `wp_linkcategories` VALUES (1,'Blogroll','N','Y','N','Y','Y','rand','N','<li>','<br />','</li>',-1);
UNLOCK TABLES;
/*!40000 ALTER TABLE `wp_linkcategories` ENABLE KEYS */;

--
-- Table structure for table `wp_links`
--

DROP TABLE IF EXISTS `wp_links`;
CREATE TABLE `wp_links` (
  `link_id` bigint(20) NOT NULL auto_increment,
  `link_url` varchar(255) NOT NULL default '',
  `link_name` varchar(255) NOT NULL default '',
  `link_image` varchar(255) NOT NULL default '',
  `link_target` varchar(25) NOT NULL default '',
  `link_category` int(11) NOT NULL default '0',
  `link_description` varchar(255) NOT NULL default '',
  `link_visible` enum('Y','N') NOT NULL default 'Y',
  `link_owner` int(11) NOT NULL default '1',
  `link_rating` int(11) NOT NULL default '0',
  `link_updated` datetime NOT NULL default '0000-00-00 00:00:00',
  `link_rel` varchar(255) NOT NULL default '',
  `link_notes` mediumtext NOT NULL,
  `link_rss` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`link_id`),
  KEY `link_category` (`link_category`),
  KEY `link_visible` (`link_visible`)
);

--
-- Dumping data for table `wp_links`
--


/*!40000 ALTER TABLE `wp_links` DISABLE KEYS */;
LOCK TABLES `wp_links` WRITE;
INSERT INTO `wp_links` VALUES (1,'http://blog.carthik.net/index.php','Carthik','','',1,'','Y',1,0,'0000-00-00 00:00:00','','','http://blog.carthik.net/feed/'),(2,'http://blogs.linux.ie/xeer/','Donncha','','',1,'','Y',1,0,'0000-00-00 00:00:00','','','http://blogs.linux.ie/xeer/feed/'),(3,'http://zengun.org/weblog/','Michel','','',1,'','Y',1,0,'0000-00-00 00:00:00','','','http://zengun.org/weblog/feed/'),(4,'http://boren.nu/','Ryan','','',1,'','Y',1,0,'0000-00-00 00:00:00','','','http://boren.nu/feed/'),(5,'http://photomatt.net/','Matt','','',1,'','Y',1,0,'0000-00-00 00:00:00','','','http://xml.photomatt.net/feed/'),(6,'http://zed1.com/journalized/','Mike','','',1,'','Y',1,0,'0000-00-00 00:00:00','','','http://zed1.com/journalized/feed/'),(7,'http://www.alexking.org/','Alex','','',1,'','Y',1,0,'0000-00-00 00:00:00','','','http://www.alexking.org/blog/wp-rss2.php'),(8,'http://dougal.gunters.org/','Dougal','','',1,'','Y',1,0,'0000-00-00 00:00:00','','','http://dougal.gunters.org/feed/');
UNLOCK TABLES;
/*!40000 ALTER TABLE `wp_links` ENABLE KEYS */;

--
-- Table structure for table `wp_options`
--

DROP TABLE IF EXISTS `wp_options`;
CREATE TABLE `wp_options` (
  `option_id` bigint(20) NOT NULL auto_increment,
  `blog_id` int(11) NOT NULL default '0',
  `option_name` varchar(64) NOT NULL default '',
  `option_can_override` enum('Y','N') NOT NULL default 'Y',
  `option_type` int(11) NOT NULL default '1',
  `option_value` longtext NOT NULL,
  `option_width` int(11) NOT NULL default '20',
  `option_height` int(11) NOT NULL default '8',
  `option_description` tinytext NOT NULL,
  `option_admin_level` int(11) NOT NULL default '1',
  `autoload` enum('yes','no') NOT NULL default 'yes',
  PRIMARY KEY  (`option_id`,`blog_id`,`option_name`),
  KEY `option_name` (`option_name`)
);

--
-- Dumping data for table `wp_options`
--


/*!40000 ALTER TABLE `wp_options` DISABLE KEYS */;
LOCK TABLES `wp_options` WRITE;
INSERT INTO `wp_options` VALUES (1,0,'siteurl','Y',1,'http://workweb/sb_testing/wpsb_upgrade',20,8,'WordPress web address',1,'yes'),(2,0,'blogname','Y',1,'Structured Blogging upgrade test',20,8,'Blog title',1,'yes'),(3,0,'blogdescription','Y',1,'This started off with SB 0.51 - this is here to test that we can upgrade OK',20,8,'Short tagline',1,'yes'),(4,0,'new_users_can_blog','Y',1,'0',20,8,'',1,'yes'),(5,0,'users_can_register','Y',1,'',20,8,'',1,'yes'),(6,0,'admin_email','Y',1,'asdf@myelin.co.nz',20,8,'',1,'yes'),(7,0,'start_of_week','Y',1,'1',20,8,'',1,'yes'),(8,0,'use_balanceTags','Y',1,'1',20,8,'',1,'yes'),(9,0,'use_smilies','Y',1,'1',20,8,'',1,'yes'),(10,0,'require_name_email','Y',1,'1',20,8,'',1,'yes'),(11,0,'comments_notify','Y',1,'1',20,8,'',1,'yes'),(12,0,'posts_per_rss','Y',1,'10',20,8,'',1,'yes'),(13,0,'rss_excerpt_length','Y',1,'50',20,8,'',1,'yes'),(14,0,'rss_use_excerpt','Y',1,'0',20,8,'',1,'yes'),(15,0,'use_fileupload','Y',1,'0',20,8,'',1,'yes'),(16,0,'fileupload_realpath','Y',1,'/home/phil/public_html/sb_testing/wpsb_upgrade/wp-content',20,8,'',1,'yes'),(17,0,'fileupload_url','Y',1,'/wp-content',20,8,'',1,'yes'),(18,0,'fileupload_allowedtypes','Y',1,'jpg jpeg gif png',20,8,'',1,'yes'),(19,0,'fileupload_maxk','Y',1,'300',20,8,'',1,'yes'),(20,0,'fileupload_minlevel','Y',1,'6',20,8,'',1,'yes'),(21,0,'mailserver_url','Y',1,'mail.example.com',20,8,'',1,'yes'),(22,0,'mailserver_login','Y',1,'login@example.com',20,8,'',1,'yes'),(23,0,'mailserver_pass','Y',1,'password',20,8,'',1,'yes'),(24,0,'mailserver_port','Y',1,'110',20,8,'',1,'yes'),(25,0,'default_category','Y',1,'1',20,8,'',1,'yes'),(26,0,'default_comment_status','Y',1,'open',20,8,'',1,'yes'),(27,0,'default_ping_status','Y',1,'open',20,8,'',1,'yes'),(28,0,'default_pingback_flag','Y',1,'1',20,8,'',1,'yes'),(29,0,'default_post_edit_rows','Y',1,'9',20,8,'',1,'yes'),(30,0,'posts_per_page','Y',1,'10',20,8,'',1,'yes'),(31,0,'what_to_show','Y',1,'posts',20,8,'',1,'yes'),(32,0,'date_format','Y',1,'F j, Y',20,8,'',1,'yes'),(33,0,'time_format','Y',1,'g:i a',20,8,'',1,'yes'),(34,0,'links_updated_date_format','Y',1,'F j, Y g:i a',20,8,'',1,'yes'),(35,0,'links_recently_updated_prepend','Y',1,'<em>',20,8,'',1,'yes'),(36,0,'links_recently_updated_append','Y',1,'</em>',20,8,'',1,'yes'),(37,0,'links_recently_updated_time','Y',1,'120',20,8,'',1,'yes'),(38,0,'comment_moderation','Y',1,'0',20,8,'',1,'yes'),(39,0,'moderation_notify','Y',1,'1',20,8,'',1,'yes'),(40,0,'permalink_structure','Y',1,'',20,8,'',1,'yes'),(41,0,'gzipcompression','Y',1,'0',20,8,'',1,'yes'),(42,0,'hack_file','Y',1,'0',20,8,'',1,'yes'),(43,0,'blog_charset','Y',1,'UTF-8',20,8,'',1,'yes'),(44,0,'moderation_keys','Y',1,'',20,8,'',1,'no'),(45,0,'active_plugins','Y',1,'a:2:{i:0;s:0:\"\";i:1;s:22:\"structuredblogging.php\";}',20,8,'',1,'yes'),(46,0,'home','Y',1,'http://workweb/sb_testing/wpsb_upgrade',20,8,'',1,'yes'),(47,0,'category_base','Y',1,'',20,8,'',1,'yes'),(48,0,'ping_sites','Y',1,'http://rpc.pingomatic.com/',20,8,'',1,'yes'),(49,0,'advanced_edit','Y',1,'0',20,8,'',1,'yes'),(50,0,'comment_max_links','Y',1,'2',20,8,'',1,'yes'),(51,0,'default_email_category','Y',1,'1',20,8,'Posts by email go to this category',1,'yes'),(52,0,'recently_edited','Y',1,'',20,8,'',1,'no'),(53,0,'use_linksupdate','Y',1,'0',20,8,'',1,'yes'),(54,0,'template','Y',1,'default',20,8,'',1,'yes'),(55,0,'stylesheet','Y',1,'default',20,8,'',1,'yes'),(56,0,'comment_whitelist','Y',1,'1',20,8,'',1,'yes'),(57,0,'page_uris','Y',1,'a:1:{s:5:\"page1\";s:5:\"page1\";}',20,8,'',1,'yes'),(58,0,'blacklist_keys','Y',1,'',20,8,'',1,'no'),(59,0,'comment_registration','Y',1,'',20,8,'',1,'yes'),(60,0,'open_proxy_check','Y',1,'1',20,8,'',1,'yes'),(61,0,'rss_language','Y',1,'en',20,8,'',1,'yes'),(62,0,'html_type','Y',1,'text/html',20,8,'',1,'yes'),(63,0,'use_trackback','Y',1,'0',20,8,'',1,'yes'),(64,0,'gmt_offset','Y',1,'0',20,8,'',1,'yes');
UNLOCK TABLES;
/*!40000 ALTER TABLE `wp_options` ENABLE KEYS */;

--
-- Table structure for table `wp_post2cat`
--

DROP TABLE IF EXISTS `wp_post2cat`;
CREATE TABLE `wp_post2cat` (
  `rel_id` bigint(20) NOT NULL auto_increment,
  `post_id` bigint(20) NOT NULL default '0',
  `category_id` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`rel_id`),
  KEY `post_id` (`post_id`,`category_id`)
);

--
-- Dumping data for table `wp_post2cat`
--


/*!40000 ALTER TABLE `wp_post2cat` DISABLE KEYS */;
LOCK TABLES `wp_post2cat` WRITE;
INSERT INTO `wp_post2cat` VALUES (2,2,1),(3,3,1),(4,4,1),(5,5,1);
UNLOCK TABLES;
/*!40000 ALTER TABLE `wp_post2cat` ENABLE KEYS */;

--
-- Table structure for table `wp_postmeta`
--

DROP TABLE IF EXISTS `wp_postmeta`;
CREATE TABLE `wp_postmeta` (
  `meta_id` bigint(20) NOT NULL auto_increment,
  `post_id` bigint(20) NOT NULL default '0',
  `meta_key` varchar(255) default NULL,
  `meta_value` text,
  PRIMARY KEY  (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`)
);

--
-- Dumping data for table `wp_postmeta`
--


/*!40000 ALTER TABLE `wp_postmeta` DISABLE KEYS */;
LOCK TABLES `wp_postmeta` WRITE;
INSERT INTO `wp_postmeta` VALUES (1,3,'_wp_page_template','default');
UNLOCK TABLES;
/*!40000 ALTER TABLE `wp_postmeta` ENABLE KEYS */;

--
-- Table structure for table `wp_posts`
--

DROP TABLE IF EXISTS `wp_posts`;
CREATE TABLE `wp_posts` (
  `ID` bigint(20) unsigned NOT NULL auto_increment,
  `post_author` int(4) NOT NULL default '0',
  `post_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL default '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_category` int(4) NOT NULL default '0',
  `post_excerpt` text NOT NULL,
  `post_status` enum('publish','draft','private','static','object') NOT NULL default 'publish',
  `comment_status` enum('open','closed','registered_only') NOT NULL default 'open',
  `ping_status` enum('open','closed') NOT NULL default 'open',
  `post_password` varchar(20) NOT NULL default '',
  `post_name` varchar(200) NOT NULL default '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL default '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL default '0000-00-00 00:00:00',
  `post_content_filtered` text NOT NULL,
  `post_parent` int(11) NOT NULL default '0',
  `guid` varchar(255) NOT NULL default '',
  `menu_order` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `post_name` (`post_name`),
  KEY `post_status` (`post_status`)
);

--
-- Dumping data for table `wp_posts`
--


/*!40000 ALTER TABLE `wp_posts` DISABLE KEYS */;
LOCK TABLES `wp_posts` WRITE;
INSERT INTO `wp_posts` VALUES (2,1,'2005-11-30 09:17:56','2005-11-30 09:17:56','This is an ordinary WP post.','First post',0,'','publish','open','open','','first-post','','','2005-11-30 09:17:56','2005-11-30 09:17:56','',0,'http://workweb/sb_testing/wpsb_upgrade/?p=2',0),(3,1,'2005-11-30 09:18:10','2005-11-30 09:18:10','This is a page.','First page',0,'','static','open','open','','page1','','','2005-11-30 09:18:10','2005-11-30 09:18:10','',0,'http://workweb/sb_testing/wpsb_upgrade/?page_id=3',0),(4,1,'2005-11-30 09:18:38','2005-11-30 09:18:38','<div class=\"x-wpsb-simple-event\" id=\"sbentry_\">\n<div class=\"x-wpsb-event-date\">When: Sunday, December 25 2005</div>\n<div class=\"x-wpsb-event-location\">Where: All over the place</div>\n<div class=\"x-wpsb-event-information\">More Information: 123-456-789</div>\n<div class=\"x-wpsb-event-information\">More Information: <a href=\"http://northpole.com/\">http://northpole.com/</a></div>\n<div class=\"x-wpsb-event-role\">My Role: Attendee</div>\n<div>Christmas...</div>\n</div>\n\n<script type=\"application/x-subnode; charset=utf-8\">\n<!-- the following is structured blog data for machine readers. -->\n<subnode xmlns:data-view=\"http://www.w3.org/2003/g/data-view#\" data-view:interpreter=\"http://structuredblogging.org/subnode-to-rdf-interpreter.xsl\" xmlns=\"http://www.structuredblogging.org/xmlns#subnode\">\n<xml-structured-blog-entry xmlns=\"http://www.structuredblogging.org/xmlns\">\n	<generator id=\"wpsb-1\" type=\"x-wpsb-simple-event\" version=\"1\"/>\n	<simple-event version=\"1\" xmlns=\"http://www.structuredblogging.org/xmlns#simple-event\">\n		<datetime>2005-12-25</datetime>\n		<event-title>First event</event-title>\n		<location>All over the place</location>\n		<role>Attendee</role>\n		<more-information url=\"http://northpole.com/\"/>\n		<more-information phone=\"123-456-789\"/>\n		<description type=\"text/html\" escaped=\"true\">Christmas...</description>\n	</simple-event>\n</xml-structured-blog-entry>\n</subnode>\n</script>\n\n','First event',0,'<div class=\"x-wpsb-simple-event\" id=\"sbentry_\">\n<div class=\"x-wpsb-event-date\">When: Sunday, December 25 2005</div>\n<div class=\"x-wpsb-event-location\">Where: All over the place</div>\n<div class=\"x-wpsb-event-information\">More Information: 123-456-789</div>\n<div class=\"x-wpsb-event-information\">More Information: <a href=\"http://northpole.com/\">http://northpole.com/</a></div>\n<div class=\"x-wpsb-event-role\">My Role: Attendee</div>\n<div>Christmas...</div>\n</div>\n','publish','open','open','','first-event','','','2005-11-30 09:18:38','2005-11-30 09:18:38','',0,'http://workweb/sb_testing/wpsb_upgrade/?p=4',0),(5,1,'2005-11-30 09:19:21','2005-11-30 09:19:21','<div class=\"x-wpsb-review-concert\" id=\"sbentry_\">\n<div class=\"x-wpsb-review-image\"><a href=\"http://example.com/\"><img alt=\"Product Image: Black Eyed Peas in Christchurch\" border=\"0\" src=\"http://example.com/image.jpg\"/></a></div>\n<div class=\"x-wpsb-review-product\"><a href=\"http://example.com/\">Black Eyed Peas in Christchurch</a></div>\n<div class=\"x-wpsb-rating\">My rating: 5 out of 5 <div class=\"x-wpsb-fullstar\"> </div><div class=\"x-wpsb-fullstar\"> </div><div class=\"x-wpsb-fullstar\"> </div><div class=\"x-wpsb-fullstar\"> </div><div class=\"x-wpsb-fullstar\"> </div><div class=\"x-wpsb-endstars\"> </div>\n</div>\n<div class=\"x-wpsb-review-description\">\n<p>Good concert ... the Peas had loads of energy, and while they\'re not quite as in-tune live as in the studio, they were still definitely worth watching.</p></div>\n</div>\n\n<script type=\"application/x-subnode; charset=utf-8\">\n<!-- the following is structured blog data for machine readers. -->\n<subnode xmlns:data-view=\"http://www.w3.org/2003/g/data-view#\" data-view:interpreter=\"http://structuredblogging.org/subnode-to-rdf-interpreter.xsl\" xmlns=\"http://www.structuredblogging.org/xmlns#subnode\">\n<xml-structured-blog-entry xmlns=\"http://www.structuredblogging.org/xmlns\">\n	<generator id=\"wpsb-1\" type=\"x-wpsb-simple-review\" version=\"1\"/>\n	<simple-review version=\"1\" xmlns=\"http://www.structuredblogging.org/xmlns#simple-review\">\n		<review-title>First review</review-title>\n		<review-type>Concert</review-type>\n		<rating number=\"5\" base=\"5\" value=\"1\">5 out of 5</rating>\n		<product-name>Black Eyed Peas in Christchurch</product-name>\n		<product-link>http://example.com/</product-link>\n		<product-image-link>http://example.com/image.jpg</product-image-link>\n		<description type=\"text/html\" escaped=\"true\">Good concert ... the Peas had loads of energy, and while they\'re not quite as in-tune live as in the studio, they were still definitely worth watching.</description>\n	</simple-review>\n</xml-structured-blog-entry>\n</subnode>\n</script>\n\n','First review',0,'<div class=\"x-wpsb-review-concert\" id=\"sbentry_\">\n<div class=\"x-wpsb-review-image\"><a href=\"http://example.com/\"><img alt=\"Product Image: Black Eyed Peas in Christchurch\" border=\"0\" src=\"http://example.com/image.jpg\"/></a></div>\n<div class=\"x-wpsb-review-product\"><a href=\"http://example.com/\">Black Eyed Peas in Christchurch</a></div>\n<div class=\"x-wpsb-rating\">My rating: 5 out of 5 <div class=\"x-wpsb-fullstar\"> </div><div class=\"x-wpsb-fullstar\"> </div><div class=\"x-wpsb-fullstar\"> </div><div class=\"x-wpsb-fullstar\"> </div><div class=\"x-wpsb-fullstar\"> </div><div class=\"x-wpsb-endstars\"> </div>\n</div>\n<div class=\"x-wpsb-review-description\">\n<p>Good concert ... the Peas had loads of energy, and while they\'re not quite as in-tune live as in the studio, they were still definitely worth watching.</p></div>\n</div>\n','publish','open','open','','first-review','','','2005-11-30 09:20:08','2005-11-30 09:20:08','',0,'http://workweb/sb_testing/wpsb_upgrade/?p=5',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `wp_posts` ENABLE KEYS */;

--
-- Table structure for table `wp_users`
--

DROP TABLE IF EXISTS `wp_users`;
CREATE TABLE `wp_users` (
  `ID` bigint(20) unsigned NOT NULL auto_increment,
  `user_login` varchar(60) NOT NULL default '',
  `user_pass` varchar(64) NOT NULL default '',
  `user_firstname` varchar(50) NOT NULL default '',
  `user_lastname` varchar(50) NOT NULL default '',
  `user_nickname` varchar(50) NOT NULL default '',
  `user_nicename` varchar(50) NOT NULL default '',
  `user_icq` int(10) unsigned NOT NULL default '0',
  `user_email` varchar(100) NOT NULL default '',
  `user_url` varchar(100) NOT NULL default '',
  `user_ip` varchar(15) NOT NULL default '',
  `user_domain` varchar(200) NOT NULL default '',
  `user_browser` varchar(200) NOT NULL default '',
  `user_registered` datetime NOT NULL default '0000-00-00 00:00:00',
  `user_level` int(2) unsigned NOT NULL default '0',
  `user_aim` varchar(50) NOT NULL default '',
  `user_msn` varchar(100) NOT NULL default '',
  `user_yim` varchar(50) NOT NULL default '',
  `user_idmode` varchar(20) NOT NULL default '',
  `user_activation_key` varchar(60) NOT NULL default '',
  `user_status` int(11) NOT NULL default '0',
  `user_description` longtext NOT NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `user_login` (`user_login`)
);

--
-- Dumping data for table `wp_users`
--


/*!40000 ALTER TABLE `wp_users` DISABLE KEYS */;
LOCK TABLES `wp_users` WRITE;
INSERT INTO `wp_users` VALUES (1,'admin','acbd18db4cc2f85cedef654fccc4a4d8','','','Administrator','administrator',0,'asdf@myelin.co.nz','http://','','','','2005-11-30 22:16:53',10,'','','','nickname','',0,'');
UNLOCK TABLES;
/*!40000 ALTER TABLE `wp_users` ENABLE KEYS */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

