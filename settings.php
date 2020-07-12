<?php
/* >>> SETUP YOUR GUESTBOOK <<< */
/* Detailed information found in the readme file */
/* File version: 1.34 $ Timestamp: November 16 2005 18:08 */

/* What type of server is your website on?
 1 = UNIX (Linux), 2 = Windows, 3 = Machintos */
$settings['system']=1;

/* Password for admin area */
$settings['apass']="admin";

/* Website title */
$settings['website_title']="XOOPS Ziyaretçi Defteri";

/* Website URL */
$settings['website_url']="http://www.xoopsum.com/";

/* Guestbook title */
$settings['gbook_title']="Ziyaretçi Defteri";

/* Allow smileys? 1 = YES, 0 = NO */
$settings['smileys']=1;

/* Send you an e-mail when a new entry is added? 1 = YES, 0 = NO */
$settings['notify']=1;

/* Your e-mail. Only required if $settings['notify'] is set to 1 */
$settings['admin_email']="you@yourdomain.com";

/* URL of the gbook.php file. Only required if $settings['notify'] is set to 1 */
$settings['gbook_url']="http://www.domain.com/guestbook/gbook.php";

/* Filter bad words? 1 = YES, 0 = NO */
$settings['filter']=1;

/* Filter language. Please refer to readme for info on how to add more bad words
to the list! */
$settings['filter_lang']="en";

/* Prevent automated submissions (SPAM)? 1 = YES, 0 = NO */
$settings['autosubmit']=1;

/* Checksum - just type some digits or chars. Used tohelp prevent SPAM */
$settings['filter_sum']='dhjx72js';

/* Prevent multiple submissions in the same session? 1 = YES, 0 = NO */
$settings['one_per_session']=1;

/* Maximum chars word length */
$settings['max_word']=75;


/* >>> OPTIONAL SETTINGS <<< */

/* Name of the file where guestbook entries will be stored */
$settings['logfile']="entries.txt";

/* >>> DO NOT EDIT BELOW <<< */
$settings['verzija']="1.34";
?>