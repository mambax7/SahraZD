<?php
# PHP guestbook (GBook)
# Version: 1.34
# File last modified: November 16 2005 18:08
# File name: gbook.php
# Written 27th December 2004 by Klemen Stirn (info@phpjunkyard.com)
# http://www.PHPJunkYard.com

##############################################################################
# COPYRIGHT NOTICE                                                           #
# Copyright 2004-2005 PHPJunkYard All Rights Reserved.                       #
#                                                                            #
# This script may be used and modified free of charge by anyone so long as   #
# this copyright notice and the comments above remain intact. By using this  #
# code you agree to indemnify Klemen Stirn from any liability that might     #
# arise from it's use.                                                       #
#                                                                            #
# Selling the code for this program without prior written consent is         #
# expressly forbidden. In other words, please ask first before you try and   #
# make money off this program.                                               #
#                                                                            #
# Obtain permission before redistributing this software over the Internet or #
# in any other medium. In all cases copyright and header must remain intact. #
# This Copyright is in full effect in any country that has International     #
# Trade Agreements with the United States of America or with                 #
# the European Union.                                                        #
##############################################################################

#############################
#     DO NOT EDIT BELOW     #
#############################

error_reporting(E_ALL ^ E_NOTICE);

require_once('settings.php');

if($settings['system'] == 2) {$settings['newline']="\r\n";}
elseif($settings['system'] == 3) {$settings['newline']="\r";}
else {$settings['newline']="\n";}

$a=$_REQUEST['a'];

/* This should take care of the signup form not caching problem */
if($a!="sign") {printNoCache();}
/* And this will start session which will help prevent multiple submissions */
if($a=="add") {
session_start();
	if (!isset($_SESSION['checked']))
    {
	    $_SESSION['checked']="N";
	    $_SESSION['secnum']=rand(10000,99999);
	    $_SESSION['checksum']=$_SESSION['secnum'].$settings['filter_sum'].date('dmy');
	    session_regenerate_id();
    }
}

printTopHTML();

if (!(empty($a))) {
	if($a=="sign") {
    	printSign();
    } elseif($a=="delete") {
        $num=gbook_isNumber($_REQUEST['num'],"Invalid ID");
        confirmDelete($num);
    } elseif($a=="viewprivate") {
        $num=gbook_isNumber($_REQUEST['num'],"Invalid ID");
		confirmViewPrivate($num);
    } elseif($a=="add") {
        $name=gbook_input($_REQUEST['name'],"Please enter your name");
        $from=gbook_input($_REQUEST['from']);
        $a=check_mail_url(); $email=$a['email']; $url=$a['url'];
        $comments=gbook_input($_REQUEST['comments'],"Please enter your comments");
        $isprivate=gbook_input($_REQUEST['private']);

        if ($settings['autosubmit'] == 1)
        {
			if ($_SESSION['checked'] == "N")
	        {
                print_secimg($name,$from,$email,$url,$comments,$isprivate);
	        }
            elseif ($_SESSION['checked'] == "P")
            {
                $_SESSION['checked'] = "N";
                $secnumber=gbook_isNumber($_REQUEST['secnumber']);
	            if(empty($secnumber)) {print_secimg($name,$from,$email,$url,$comments,$isprivate,1);}
	            $secimg=check_secnum($secnumber,$_SESSION['checksum']);
                if (empty($secimg))
                {print_secimg($name,$from,$email,$url,$comments,$isprivate,2);}
            }
            else {problem("Internal script error. Wrong session parameters!");}
        }

    	addEntry($name,$from,$email,$url,$comments,$isprivate);

    } elseif($a=="confirmdelete") {
    	$pass=gbook_input($_REQUEST['pass'],"Please enter your password");
        $num=gbook_isNumber($_REQUEST['num'],"Invalid ID");
    	doDelete($pass,$num);
    } elseif($a=="showprivate") {
    	$pass=gbook_input($_REQUEST['pass'],"Please enter your password");
        $num=gbook_isNumber($_REQUEST['num'],"Invalid ID");
	    showPrivate($pass,$num);
    } else {
    problem("This is not a valid action!");
    }
}

$page=gbook_isNumber($_REQUEST['page']);
if ($page>0) {
	$start=($page*10)-9;$end=$start+9;
} else {
	$page=1;$start=1;$end=10;
}

$filesize=filesize($settings['logfile']);
$fp = @fopen($settings['logfile'],"rb") or problem("Can't open the log file ($settings[logfile]) for reading! CHMOD this file to 666 (rw-rw-rw)!");
$content=@fread($fp,$filesize);
fclose($fp);
$content = trim(chop($content));
$lines = explode($settings['newline'],$content);

if ($filesize == 0) {$total=0;}
else {
$total = count($lines);
	if ($end > $total) {$end=$total;}
$pages = ceil($total/10);
echo '<p>Displaying page '.$page.' of '.$pages.'. Pages: ';
	for ($i=1; $i<=$pages; $i++) {
		if($i == $page) {echo "<b>$i</b>\n";}
        else {echo '<a href="gbook.php?page='.$i.'">'.$i.'</a> ';}
	}
}

echo '</p>
<table border="0" cellspacing="0" cellpadding="2" width="95%" class="entries">';

if ($filesize == 0) {
echo '<tr>
<td>No entries yet!</td>
</tr>';
}
else {printEntries($lines,$start,$end);}

echo '</table>';

if ($filesize != 0) {
echo '<p>Pages: ';
	for ($i=1; $i<=$pages; $i++) {
		if($i == $page) {echo "<b>$i</b>\n";}
        else {echo '<a href="gbook.php?page='.$i.'">'.$i.'</a> ';}
	}
}

printCopyHTML();
printDownHTML();
exit();


// >>> START FUNCTIONS <<< //

function check_secnum($secnumber,$checksum) {
global $settings;
$secnumber.=$settings['filter_sum'].date('dmy');
    if ($secnumber == $checksum)
        {
        unset($_SESSION['checked']);
        return true;
        }
    else
    {
        return false;
    }
} // END check_secnum


function print_secimg($name,$from,$email,$url,$comments,$isprivate,$message=0) {
$_SESSION['checked']="P";
?>
<h3 align="center">Anti-SPAM check</h3>
</p>
<form action="gbook.php?<?php echo strip_tags (SID)?>" method="POST" name="form"><input type="hidden" name="a" value="add">
<table class="entries" cellspacing="0" cellpadding="4" border="0">
<tr>
<td>

<p>&nbsp;</p>
<?php
if ($message == 1) {echo '<p align="center"><b>Please type in the security number</b></p>';}
elseif ($message == 2) {echo '<p align="center"><b>Wrong security number. Please try again</b></p>';}
?>
<p>&nbsp;</p>
<p>This is a security check that prevents automated signups of this guestbook (SPAM).
Please enter the security number displayed below into the input field and click
the continue button.</p>
<p>&nbsp;</p>
<p>Security number: <b><?php echo $_SESSION['secnum']; ?></b><br>
Please type in the security number displayed above:
<input type="text" size="7" name="secnumber" maxlength="5" id="input"></p>
<p>&nbsp;
<input type="hidden" name="name" value="<?php echo $name; ?>">
<input type="hidden" name="from" value="<?php echo $from; ?>">
<input type="hidden" name="email" value="<?php echo $email; ?>">
<input type="hidden" name="url" value="<?php echo $url; ?>">
<input type="hidden" name="comments" value="<?php echo $comments; ?>">
<input type="hidden" name="private" value="<?php echo $isprivate; ?>">
<input type="hidden" name="nosmileys" value="<?php echo $_REQUEST['nosmileys']; ?>">
</p>
<p align="center"><input type="submit" value=" Continue "></p>
<p>&nbsp;</p>
<p>&nbsp;</p>
</td>
</tr>
</table>
</form>

<?php
printCopyHTML();
printDownHTML();
exit();
} // END print_secimg



function filter_bad_words($text) {
global $settings;
$file = 'badwords/'.$settings['filter_lang'].'.php';

	if (file_exists($file))
    {
    	include_once($file);
    }
    else
    {
    	problem("The bad words file ($file) can't be found! Please check the
        name of the file. On most servers names are CaSe SeNsiTiVe!");
    }

	foreach ($settings['badwords'] as $k => $v)
    {
    	$text = preg_replace("/$k/i",$v,$text);
    }

return $text;
} // END filter_bad_words

function showPrivate($pass,$num) {
global $settings;
if ($pass != $settings[apass]) {problem("Wrong password! Only the guestbook owner may read this post!","1");}

$delimiter="\t";
$lines = file($settings['logfile']);

list($name,$from,$email,$url,$comment,$added,$isprivate)=explode($delimiter,$lines[$num]);
echo '
<table border="0" cellspacing="0" cellpadding="2" width="95%" class="entries">
<tr>
<td class="upper" width="35%"><b>Submitted by</b></td>
<td class="upper" width="65%"><b>Comments:</b></td>
</tr>
<tr>
<td valign="top" width="35%"><b>'.$name.'</b><br>
<font class="smaller">From: '.$from.'</font><br>
<font class="smaller">Website:</font> ';
if (strlen($url)>0) {
echo '<a href="go.php?url='.$url.'" target="_blank" class="smaller">'.$url.'</a>';
}
echo '<br>
<font class="smaller">E-mail:</font> ';
if (strlen($email)>0) {
echo '<a href="mailto&#58;'.$email.'" target="_blank" class="smaller">'.$email.'</a>';
}
echo '</td>
<td valign="top" width="65%">'.$comment.'
<hr>
<font class="smaller">Added: '.$added.' &nbsp;&nbsp;&nbsp;&nbsp;
<a href="gbook.php?a=delete&num='.$num.'"><img src="images/delete.gif" width="16" height="14" border="0" alt="Delete this entry"></a></font>
</td>
</tr>
</table>
<p align="center"><a href="gbook.php">Back to Guestbook</a></p>
';

printCopyHTML();
printDownHTML();
exit();
} // END showPrivate

function confirmViewPrivate($num) {
?>
<p>&nbsp;</p>
<p>&nbsp;</p>
<form action="gbook.php" method="POST"><input type="hidden" name="a" value="showprivate">
<input type="hidden" name="num" value="<?php echo($num); ?>">
<p><b>This is a private post for the owner of this questbook.</b></p>
<p>Please enter your administration password:<br>
<input type="password" name="pass" size="20"></p>
<p><input type="submit" value="View this entry"> | <a href="Javascript:history.go(-1)">Back</a></p>
</form>
<p>&nbsp;</p>
<p>&nbsp;</p>
<?php
printCopyHTML();
printDownHTML();
exit();
} // END confirmViewPrivate

function processsmileys($text) {
$text = str_replace(':)','<img src="images/icon_smile.gif" border="0" alt="">',$text);
$text = str_replace(':(','<img src="images/icon_frown.gif" border="0" alt="">',$text);
$text = str_replace(':D','<img src="images/icon_biggrin.gif" border="0" alt="">',$text);
$text = str_replace(';)','<img src="images/icon_wink.gif" border="0" alt="">',$text);
$text = preg_replace("/\:o/i",'<img src="images/icon_redface.gif" border="0" alt="">',$text);
$text = preg_replace("/\:p/i",'<img src="images/icon_razz.gif" border="0" alt="">',$text);
$text = str_replace(':cool:','<img src="images/icon_cool.gif" border="0" alt="">',$text);
$text = str_replace(':rolleyes:','<img src="images/icon_rolleyes.gif" border="0" alt="">',$text);
$text = str_replace(':mad:','<img src="images/icon_mad.gif" border="0" alt="">',$text);
$text = str_replace(':eek:','<img src="images/icon_eek.gif" border="0" alt="">',$text);
$text = str_replace(':clap:','<img src="images/yelclap.gif" border="0" alt="">',$text);
$text = str_replace(':bonk:','<img src="images/bonk.gif" border="0" alt="">',$text);
$text = str_replace(':chased:','<img src="images/chased.gif" border="0" alt="">',$text);
$text = str_replace(':crazy:','<img src="images/crazy.gif" border="0" alt="">',$text);
$text = str_replace(':cry:','<img src="images/cry.gif" border="0" alt="">',$text);
$text = str_replace(':curse:','<img src="images/curse.gif" border="0" alt="">',$text);
$text = str_replace(':err:','<img src="images/errr.gif" border="0" alt="">',$text);
$text = str_replace(':livid:','<img src="images/livid.gif" border="0" alt="">',$text);
$text = str_replace(':rotflol:','<img src="images/rotflol.gif" border="0" alt="">',$text);
$text = str_replace(':love:','<img src="images/love.gif" border="0" alt="">',$text);
$text = str_replace(':nerd:','<img src="images/nerd.gif" border="0" alt="">',$text);
$text = str_replace(':nono:','<img src="images/nono.gif" border="0" alt="">',$text);
$text = str_replace(':smash:','<img src="images/smash.gif" border="0" alt="">',$text);
$text = str_replace(':thumbsup:','<img src="images/thumbup.gif" border="0" alt="">',$text);
$text = str_replace(':toast:','<img src="images/toast.gif" border="0" alt="">',$text);
$text = str_replace(':welcome:','<img src="images/welcome.gif" border="0" alt="">',$text);
$text = str_replace(':ylsuper:','<img src="images/ylsuper.gif" border="0" alt="">',$text);
return $text;
} // END processsmileys

function doDelete($pass,$num) {
global $settings;
if ($pass != $settings[apass]) {problem("Wrong password! The entry hasn't been deleted.","1");}

$filesize=filesize($settings['logfile']);
$fp = @fopen($settings['logfile'],"rb") or problem("Can't open the log file ($settings[logfile]) for reading! CHMOD this file to 666 (rw-rw-rw)!");
$content=@fread($fp,$filesize);
fclose($fp);
$content = trim(chop($content));
$lines = explode($settings['newline'],$content);
unset($lines[$num]);
$fp = fopen($settings['logfile'],"wb") or problem("Couldn't open links file ($settings[logfile]) for writing! Please CHMOD all $settings[logfile] to 666 (rw-rw-rw)!");
foreach ($lines as $thisline) {
$thisline .= $settings['newline'];
fputs($fp,$thisline);
}
fclose($fp);

?>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p><b>Selected entry was successfully removed!</b></p>
<p><a href="gbook.php?page=1">Click here to continue</a></p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<?php
printCopyHTML();
printDownHTML();
exit();
} // END doDelete

function confirmDelete($num) {
?>
<p>&nbsp;</p>
<p>&nbsp;</p>
<form action="gbook.php" method="POST"><input type="hidden" name="a" value="confirmdelete">
<input type="hidden" name="num" value="<?php echo($num); ?>">
<p><b>Please enter your administration password:</b><br>
<input type="password" name="pass" size="20"></p>
<p><b>Are you sure you want to delete this entry? This action cannot be undone!</b></p>
<p><input type="submit" value="YES, delete this entry"> | <a href="gbook.php">NO, I changed my mind</a></p>
</form>
<p>&nbsp;</p>
<p>&nbsp;</p>
<?php
printCopyHTML();
printDownHTML();
exit();
} // END confirmDelete


function check_mail_url()
{
$v = array('email' => '','url' => '');
$char = array('.','@');
$repl = array("&#46;","&#64;");

$v['email']=htmlspecialchars("$_REQUEST[email]");
if (strlen($v['email']) > 0 && !(preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$v['email']))) {problem("Please enter a valid e-mail address!","1");}
$v['email']=str_replace($char,$repl,$v['email']);

$v['url']=htmlspecialchars("$_REQUEST[url]");
if ($v['url'] == "http://" || $v['url'] == "https://") {$v['url'] = "";}
elseif (strlen($v['url']) > 0 && !(preg_match("/(http(s)?:\/\/+[\w\-]+\.[\w\-]+)/i",$v['url']))) {problem("The site URL is not valid, make sure you start it with http:// or https://!","1");}

return $v;
} // END check_mail_url


function addEntry($name,$from,$email,$url,$comments,$isprivate="0") {
global $settings;

	/* This part will help prevent multiple submissions */
    if ($settings['one_per_session'] && $_SESSION['add'])
    {
        problem("You may only submit this guestbook once per session!");
    }

$delimiter="\t";
$added=date ("F j, Y");

$comments_nosmileys=$comments;
$comments = str_replace("\r\n","<br>",$comments);
$comments = str_replace("\n","<br>",$comments);
$comments = str_replace("\r","<br>",$comments);
$comments = wordwrap($comments,$settings['max_word'],'<br>',1);
if ($settings['smileys'] == 1 && $_REQUEST['nosmileys'] != "Y") {$comments = processsmileys($comments);}

if ($settings['filter']) {
$comments = filter_bad_words($comments);
$name = filter_bad_words($name);
$from = filter_bad_words($from);
}

$addline = "$name$delimiter$from$delimiter$email$delimiter$url$delimiter$comments$delimiter$added$delimiter$isprivate$settings[newline]";

$fp = @fopen($settings['logfile'],"rb") or problem("Can't open the log file ($settings[logfile]) for reading! CHMOD this file to 666 (rw-rw-rw)!");
$links = @fread($fp,filesize($settings['logfile']));
fclose($fp);
$addline .= $links;
$fp = fopen($settings['logfile'],"wb") or problem("Couldn't open links file ($settings[logfile]) for writing! Please CHMOD all $settings[logfile] to 666 (rw-rw-rw)!");
fputs($fp,$addline);
fclose($fp);

if ($settings['notify'] == 1)
	{
    $char = array('.','@');
	$repl = array("&#46;","&#64;");
    $email=str_replace($repl,$char,$email);
    $message = "Hello!

Someone has just signed your guestbook!

Name: $name
From: $from
E-mail: $email
Website: $url

Message (without smileys):
$comments_nosmileys


Visit the below URL to view your guestbook:
$settings[gbook_url]

End of message
";

    mail("$settings[admin_email]","Someone has just signed your guestbook",$message);
    }

/* Register this session variable */
$_SESSION['add']=1;

?>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p><b>Your message was successfully added!</b></p>
<p><a href="gbook.php?page=1">Click here to continue</a></p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<?php
printCopyHTML();
printDownHTML();
exit();
} // END addEntry

function printSign() {
global $settings;
?>
<h3 align="center">Sign guestbook</h3>
<p>Required fields are <b>bold</b>.
<script language="Javascript" type="text/javascript"><!--
function openSmiley() {
w=window.open("smileys.htm", "smileys", "fullscreen=no,toolbar=no,status=no,menubar=no,scrollbars=yes,resizable=yes,directories=no,location=no,width=300,height=300");
  if(!w.opener)
  {
  w.opener=self;
  }
}
//-->
</script>
</p>
<form action="gbook.php" method="POST" name="form"><input type="hidden" name="a" value="add">
<table class="entries" cellspacing="0" cellpadding="4" border="0">
<tr>
<td>

<table cellspacing="0" cellpadding="3" border="0">
<tr>
<td><b>Your name:</b></td>
<td><input type="text" name="name" size="30" maxlength="30"></td>
</tr>
<tr>
<td>Where are you from?</td>
<td><input type="text" name="from" size="30" maxlength="30"></td>
</tr>
<tr>
<td>Your e-mail:</td>
<td><input type="text" name="email" size="30" maxlength="50"></td>
</tr>
<tr>
<td>Your website:</td>
<td><input type="text" name="url" value="http://" size="40" maxlength="80"></td>
</tr>
</table>
<p align="center"><b>Comments:</b><br>
<textarea name="comments" rows="9" cols="50"></textarea><?php
if ($settings['smileys'] == 1) {
echo '<br><a href="javascript:openSmiley()">Insert smileys</a> (Opens a new window)<br>
<input type="checkbox" name="nosmileys" value="Y"> Disable smileys';
}
?></p>
<p align="center"><input type="checkbox" name="private" value="Y">Make this post private</p>
<p align="center"><input type="submit" value=" Add my comments "></p>
</td>
</tr>
</table>
</form>
<?php
printCopyHTML();
printDownHTML();
exit();
} // END printSign


function printEntries($lines,$start,$end) {
$start=$start-1;
$end=$end-1;
$delimiter="\t";
for ($i=$start;$i<=$end;$i++) {
list($name,$from,$email,$url,$comment,$added,$isprivate)=explode($delimiter,$lines[$i]);
echo '
<tr>
<td class="upper" width="35%"><b>Submitted by</b></td>
<td class="upper" width="65%"><b>Comments:</b></td>
</tr>
<tr>
<td valign="top" width="35%"><b>'.$name.'</b><br>
<font class="smaller">From: '.$from.'</font><br>
<font class="smaller">Website:</font> ';
if (strlen($url)>0) {
echo '<a href="go.php?url='.$url.'" target="_blank" class="smaller">'.$url.'</a>';
}
echo '<br>
<font class="smaller">E-mail:</font> ';
if (strlen($email)>0) {
echo '<a href="mailto&#58;'.$email.'" target="_blank" class="smaller">'.$email.'</a>';
}
echo '</td>
<td valign="top" width="65%">';

	if (empty($isprivate)) {echo $comment;}
    else {
    	echo '<p>&nbsp;</p>
    	<p><i><a href="gbook.php?a=viewprivate&num='.$i.'">Private post. Click to view.</a></i></p>';
    }

echo '<hr>
<font class="smaller">Added: '.$added.' &nbsp;&nbsp;&nbsp;&nbsp;
<a href="gbook.php?a=delete&num='.$i.'"><img src="images/delete.gif" width="16" height="14" border="0" alt="Delete this entry"></a></font>
</td>
</tr>
';
}
} // END printEntries


function problem($myproblem,$backlink="1") {
$html = '<p>&nbsp;</p>
<p>&nbsp;</p>
<p align="center"><b>Error</b></p>
<p align="center">'.$myproblem.'</p>
<p>&nbsp;</p>
';
	if ($backlink) {
		$html .= '<p align="center"><a href="Javascript:history.go(-1)">Back to the previous page</a></p>';
	}

$html .= '<p>&nbsp;</p> <p>&nbsp;</p>';

echo $html;

printCopyHTML();
printDownHTML();
exit();
} // END problem


function printNoCache() {
header("Expires: Mon, 26 Jul 2000 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
} // END printNoCache


function printTopHTML() {
global $settings;
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>'.$settings['gbook_title'].'</title>
<meta content="text/html; charset=windows-1250">
<link href="style.css" type="text/css" rel="stylesheet">
</head>
<body>
';
include_once 'header.txt';
echo '<h3 align="center">'.$settings['gbook_title'].'</h3>
<p align="center"><a href="'.$settings['website_url'].'">Back to '.$settings['website_title'].'</a>
| <a href="gbook.php">View guestbook</a>
| <a href="gbook.php?a=sign">Sign guestbook</a></p>
<div align="center">
<center>
';
} // END printTopHTML


function printDownHTML() {
global $settings;
echo '</center>
</div>';
include_once 'footer.txt';
echo '</body>
</html>';
}  // END printDownHTML


function printCopyHTML() {
global $settings;
echo rawurldecode('%3Chr%20width%3D%2295%25%22%3E%0D%0A%3C%21--%0D%0AChanging%20the%20%22Powered%20by%22%20credit%20sentence%20without%20purchasing%20a%20licence%20is%20illegal%21%0D%0APlease%20visit%20http%3A%2F%2Fwww.phpjunkyard.com%2Fcopyright-removal.php%20for%20more%20information.%0D%0A--%3E%0D%0A%3Cp%20align%3D%22center%22%3E%3Cfont%20class%3D%22smaller%22%3EPowered%20by%20%3Ca%20href%3D%22http%3A%2F%2Fwww.phpjunkyard.com%2Fphp-guestbook-script.php%22%20class%3D%22smaller%22%20target%3D%22_blank%22%3EPHP%20guestbook%3C%2Fa%3E%20').$settings['verzija'].rawurldecode('%20from%0D%0A%3Ca%20href%3D%22http%3A%2F%2Fwww.phpjunkyard.com%2F%22%20target%3D%22_blank%22%20class%3D%22smaller%22%3EPHPJunkyard%20-%20Free%20PHP%20scripts%3C%2Fa%3E%3C%2Ffont%3E%3C%2Fp%3E');
} // END printCopyHTML

function gbook_input($in,$error=0) {
	$in = trim($in);
    if (strlen($in))
    {
        $in = htmlspecialchars($in);
    }
    elseif ($error)
    {
    	problem($error);
    }
    return stripslashes($in);
} // END gbook_input()

function gbook_isNumber($in,$error=0) {
	$in = trim($in);
	if (preg_match("/\D/",$in) || $in=="")
    {
    	if ($error)
        {
        	problem($error);
        }
        else
        {
        	return '0';
        }
    }
    return $in;
} // END gbook_isNumber()
?>