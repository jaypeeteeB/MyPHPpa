<?php

/*
 * MyPHPpa
 * Copyright (C) 2003, 2007 Jens Beyer
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require "auth_check.php";
require "options.php";
include_once "get_ip.php";

include_once "session.inc";
session_init();
if (session_check()) {
  echo "error check session";
  Header("Location: index.php");
  die;
 }

pre_auth($Username,$Password,$Planetid,$_COOKIE["Valid"]);

require "dblogon.php";
db_auth($db,$Username,$Password,$Planetid);

$result = mysqli_query($db, "SELECT tick FROM general"); 
$row = mysqli_fetch_row($result);
$mytick = $row[0];

$result = mysqli_query($db, "SELECT * FROM planet WHERE id=$Planetid");
$myrow = mysqli_fetch_array($result);

mysqli_query($db, "UPDATE user set last=NOW(),last_tick='$mytick' ".
	    "WHERE planet_id='$Planetid'"); 

// standard.php
require "logging.php";
require "msgbox.php";
require_once "mobile.inc";

function do_logout ($msg) {

  session_kill();

  require "header.php";
  my_header("<meta http-equiv=\"refresh\" content=\"1; URL=index.php\">",0,0);

  echo "<center><table class=\"std\" width=\"650\" border=\"1\" cellpadding=\"5\">";
  echo "<tr><td><span class=\"red\"><b>$msg</b></span></td></tr>".
	"<tr><td>Goto <a href=\"index.php\" target=\"_parent\">".
	"Login page</a></td></tr></table>"; 
  echo "</center>";

  require "footer.php";
  die;
}

$msg = "";

function check_status($sleep) {
  global $db, $Planetid;
  global $msg, $havoc;
  global $sleep_period;
  global $mytick, $end_of_round;

  if ($mytick == 0) {
    $msg = "Better wait till round start with this.";
    return 1;
  }

  if ($havoc==1) {
    $msg = "Sleep and vacation mode is disabled during havoc!";
    return 1;
  }

  if ($sleep) {
    if ($mytick > $end_of_round) {
	$msg = "Sleep mode is disabled due to end of round!";
    	return 1;
    }  

    // when did I sleep last time 
    $q = "SELECT UNIX_TIMESTAMP(now()) - UNIX_TIMESTAMP(last_sleep) ".
       "FROM user WHERE planet_id='$Planetid'";
    $res = mysqli_query($db, $q );
    $row = mysqli_fetch_row($res);
    if ($row[0] < $sleep_period * 3600) {
      $next = $sleep_period * 3600 - $row[0];
      $tim = sprintf("%d hours, %d minutes and %d seconds", 
		     (int) floor(($next)/3600), ((int) floor(($next)/60)) % 60, 
		     (int) $next % 60);
      $msg = "Last sleep time less then ".$sleep_period."h ". 
	 " - You have to wait $tim.";
      return 1;
    }
  }

  // if fleets are out
  $q = "SELECT num FROM fleet WHERE (target_id!=0 OR full_eta!=0 OR ".
     "ticks !=0 OR type!=0) AND planet_id='$Planetid'";
  $res = mysqli_query($db, $q );
  if ($res && mysqli_num_rows($res) > 0) {
    $msg = "Your fleets are out - wait until they return.";
    return 1;
  }

  // if somebody attacks me
  $q = "SELECT num FROM fleet WHERE target_id='$Planetid'";
  $res = mysqli_query($db, $q );
  if ($res && mysqli_num_rows($res) > 0) {
    $n = mysqli_num_rows($res);
    $msg = "You are currently under attack ($n, $Planetid).";
    return 1;
  }

  return 0;
}

function show_preference($descr, $choice, $set) {
  global $mysettings;
  $selected = array((($mysettings & $set)?"":"selected"),
                    (($mysettings & $set)?"selected":""));
  
  echo "<tr><td>$descr</td><td><select name=\"set_$set\">".
   "<option $selected[0] value=0>$choice[0]</option>".
   "<option $selected[1] value=1>$choice[1]</option>".
   "</select></td></tr>\n";
}

if (ISSET($_REQUEST["vacation"])) {
  $check = check_status(0);
  
  if ($check==0) {
    mysqli_query ($db, "UPDATE planet SET mode=4 WHERE id='$Planetid'" );
    mysqli_query ($db, "UPDATE user SET last_sleep=now() WHERE planet_id='$Planetid'" );
    // setcookie("Password","");
    // setcookie("Planetid",-1);
    // setcookie("Username","");
    do_log_me(2,3,"");

    $msg = "You are going into vacation. Have fun!<br>";
    do_logout ($msg);
  }
}
if (ISSET($_REQUEST["sleep"])) {

  $check = check_status(1);
  
  if ($check==0) {
    mysqli_query ($db, "UPDATE planet SET mode=3 WHERE id='$Planetid'" );
    mysqli_query ($db, "UPDATE user SET last_sleep=now() WHERE planet_id='$Planetid'" );
    // setcookie("Password","");
    // setcookie("Planetid",-1);
    // setcookie("Username","");
    do_log_me(2,2,"");

    $msg = "You are Sleeping now. Nice dreams!<br>";
    do_logout ($msg);
  }
}

if(ISSET($_REQUEST["pwchange"])) {
  if (!ISSET($_REQUEST["oldpw"]) || md5($_REQUEST["oldpw"]) != $Password) {
     $msg = "Old Password does not match!!<br>\n";
  } else {
    if (!ISSET($_REQUEST["newpw"]) || !ISSET($_REQUEST["newpw2"]) || 
        $_REQUEST["newpw"] != $_REQUEST["newpw2"]) {
      $msg = "New passwords do not match!<br>\n";
//    } else if (ereg("\\", $n) || ereg("<", $n)) {
//      $msg = "Illegal char &lt; or char \\<br>\n";
    } else {
      $newpw = $_REQUEST["newpw"];
      // setcookie("Password",md5($newpw));
      $Password = md5($newpw);
      $_SESSION["Password"] =  $Password;
      mysqli_query ($db, "UPDATE user SET password='$newpw' ".
		   "WHERE planet_id='$Planetid'" );
      $msg = "Password successfully changed<br>\n";
    }
  }
}

if(ISSET($_REQUEST["emchange"])) {
  $res = mysqli_query ($db, "SELECT email FROM user WHERE planet_id='$Planetid'" );
  $row = mysqli_fetch_row($res);

  if (!ISSET($_REQUEST["oldem"]) || $_REQUEST["oldem"] != $row[0]) {
    
    $msg = "Old Email does not match!<br>\n";
  } else {
    if (!ISSET($_REQUEST["newem"]) || !ISSET($_REQUEST["newem2"]) || 
        $_REQUEST["newem"] != $_REQUEST["newem2"]) {
      $msg = "New emails do not match !<br>\n";
    } else {
      $newem = $_REQUEST["newem"];
      mysqli_query ($db, "UPDATE user SET email='$newem' ".
		   "WHERE planet_id='$Planetid'" );
      $msg = "Email successfully changed<br>\n";
    }
  }
}

if (ISSET($_POST["imgset"])) {
  $images = ($_POST["images"]);
  
  if ($images == "CLEAR") {
    $imgpath="";
    setcookie("imgpath","");
    $_SESSION["ImgPath"] = $imgpath;
  } else {
    $images = chop($images);
    $imgpath = $images;
    setcookie("imgpath", $imgpath);
    $_SESSION["ImgPath"] = $imgpath;
  }
  mysqli_query ($db, "UPDATE user set imgpath='$imgpath' ".
               "WHERE planet_id='$Planetid'" );
}

if (ISSET($_POST["delete"])) {
  mysqli_query ($db, "UPDATE user SET delete_date=now()+INTERVAL 1 minute ".
     "WHERE planet_id='$Planetid'");
  $msg = "!!!!!! Your account has been marked for deletion !!!!!!!!<br>".
    "If you didnt want so - just login again during next 12 hours<br><br>".
    "Close this browser window now - Good Bye";
  do_log_me(2,4,"");
}

if (ISSET($_REQUEST["setting"])) {
  $new_setting = 0;
  $new_setting |= ((ISSET($_REQUEST["set_1"]) && $_REQUEST["set_1"])?1:0);
  $new_setting |= ((ISSET($_REQUEST["set_2"]) && $_REQUEST["set_2"])?2:0);
  $new_setting |= ((ISSET($_REQUEST["set_4"]) && $_REQUEST["set_4"])?4:0);
  $new_setting |= ((ISSET($_REQUEST["set_8"]) && $_REQUEST["set_8"])?8:0);
  $new_setting |= ((ISSET($_REQUEST["set_16"]) && $_REQUEST["set_16"])?16:0);
  $new_setting |= ((ISSET($_REQUEST["set_32"]) && $_REQUEST["set_32"])?32:0);
  $new_setting |= ((ISSET($_REQUEST["set_64"]) && $_REQUEST["set_64"])?64:0);
  $new_setting |= ((ISSET($_REQUEST["set_128"]) && $_REQUEST["set_128"])?128:0);

  $mysettings = $new_setting;
  mysqli_query ($db, "UPDATE user SET settings='$mysettings' ".
    "WHERE planet_id='$Planetid'");
}

require "header.php";
if (ISSET($extra_header)) {
  my_header($extra_header,0,0);
} else {
  my_header("",0,0);
}

require_once "navigation.inc";

echo "<div id=\"main\">\n";

/* top_header ($myrow); */
titlebox ("Preferences", $msg);
?>

<center>

<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table class="std" width="650" border="1" cellpadding="5">
<tr><th colspan="2" class="a">Feature settings</th></tr>

<?php
  show_preference("Forum reading direction", array("Forward","Reverse"), 1);
  show_preference("Forum post editor", array("Javascript popup", "Main browser frame"),2);
  show_preference("Show galaxy pictures", array("Yes", "No"), 4);
  show_preference("Limit News scan to 20 entries", array("Full", "Limit"), 8);
  show_preference("Use Javascript for last tick &sup1;", array("No", "Yes"), 16);
  show_preference("Use popup for scans in galaxy view", array("No", "Yes"), 64);
  show_preference("Reduced visibility in Res/Con", array("Max", "Min"), 128);
?>

<tr><td colspan="2" align="center">    
    <input type="submit" value="Change" name="setting">
</td></tr>
<tr><td colspan="2" align="left">
1)&nbsp; Browser dependend settings
</td></tr>
</table>
</form>
<br>

<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table class="std" width="650" border="1" cellpadding="5">
<tr><th colspan="2" class="a">Sleep Mode
</th></tr>
<tr><td colspan="2">Sleep mode will protect Your planet against offensive acts 
for a maximum of 6 hours. After this time Your account is woke up automagically,
but you may login anytime earlier.<p>
You may go in sleep mode only <?php echo $sleep_period ?>  hours after last Sleep-Time-Start.<p>
While sleeping, You <b>do</b> get resources!<p>
You cannot go into sleep mode if You are attacking or under attack (or defending).
</td></tr>
<tr><td colspan="2">
<?php

if ($mytick > $end_of_round) {
  echo "Sleep mode is disabled due to end of round!";
} else {
  $q = "SELECT UNIX_TIMESTAMP(now()) - UNIX_TIMESTAMP(last_sleep) ".
     "FROM user WHERE planet_id='$Planetid'";
  $res = mysqli_query($db, $q );
  $row = mysqli_fetch_row($res);
  if ($row[0] < $sleep_period * 3600) {
     $next = $sleep_period * 3600 - $row[0];
     $tim = sprintf("%d hours, %d minutes and %d seconds",
                 (int) floor(($next)/3600), ((int) floor(($next)/60)) % 60,
                 (int) $next % 60);
     echo "Last sleep time less then ".$sleep_period."h ".
           " - You have to wait $tim.";
  } else {
    echo "You may go into sleep mode now.";
  }
}
?>
</td></tr>
<tr><td width="60%">Go into sleep mode</td><td align="center" width="40%">
    <input type="submit" value="Sleep" name="sleep"></td></tr>
</table>
</form>
<br>

<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table class="std" width="650" border="1" cellpadding="5">
<tr><th colspan="2" class="a">Change Password</th></tr>
<tr><td width="50%">Old Password</td>
    <td width="50%" align="center">
    <input type="password" size="16" maxlength="29" name="oldpw"></td></tr>
<tr><td width="50%">New Password</td>
    <td width="50%" align="center">
    <input type="password" size="16" maxlength="29" name="newpw"></td></tr>
<tr><td width="50%">Verify new Password</td>
    <td width="50%" align="center">
    <input type="password" size="16" maxlength="29" name="newpw2"></td></tr>
<tr><td colspan="2" align="center">    
    <input type="submit" value="Change" name="pwchange">
    <input type="reset" value=" Reset ">
</td></tr>
</table>
</form>
<br>

<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table class="std" width="650" border="1" cellpadding="5">
<tr><th colspan="2" class="a">Change Email</th></tr>
<?php
   $q = "SELECT email FROM user WHERE planet_id='$Planetid'";
   $res = mysqli_query($db, $q );  
   $row = mysqli_fetch_row($res);
   if ($row[0] == 'myphppa@web.de') {
echo <<<EOF
<tr><td colspan="2"><span class="red"><b>PLEASE update your EMAIL! (curr: myphppa@web.de)</b></span>
    <input type="hidden" name="oldem" value="myphppa@web.de"></td></tr>
EOF;
   } else {
echo <<<EOF
<tr><td width="50%">Old Email</td>
    <td width="50%" align="center">
    <input type="text" size="25" maxlength="249" name="oldem"></td></tr>
EOF;
   }
?>
<tr><td width="50%">New Email</td>
    <td width="50%" align="center">
    <input type="text" size="25" maxlength="249" name="newem"></td></tr>
<tr><td width="50%">Verify new Email</td>
    <td width="50%" align="center">
    <input type="text" size="25" maxlength="249" name="newem2"></td></tr>
<tr><td colspan="2" align="center">    
    <input type="submit" value="Change" name="emchange">
    <input type="reset" value=" Reset ">
</td></tr>
</table>
</form>
<br>

<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table class="std" width="650" border="1" cellpadding="5">
<tr><th colspan="2" class="a">Images</th></tr>
<tr><th align=left colspan=2>Path to images</th></tr>
<tr><td colspan=2>Download the Zip-file and unzip it. You get a directory 
myphppa with some images in. If c:\temp is the place where the myphppa 
directory was extracted then enter as path:</td></tr>
<tr><td>Browser / OS</td><td>Example</td></tr>
<tr><td width=50%>Win98 Explorer 5.5</td><td>file:///C|/temp</td></tr>
<tr><td>Win XP Explorer 6.0</td><td>file:///C:/temp</td></tr>
<tr><td>Unix</td><td>file://localhost/home/khan/tmp</td></tr>
<tr><td>Remote (from server, no download)</td><td>/img</td></tr>
<tr><td align="center" colspan=2>Enter CLEAR to reset completly</td></tr>

<tr><td>Path to myphppa directory: (/myphppa is appended automatically)</td>
  <td> <input type="text" size="35" maxlength="249" name="images" 
           value="<?php echo $imgpath ?>"></td></tr>
<tr><td colspan="2"><a href="img/myphppa-img.zip">Download myphppa-img.zip</a><br>
Thanx to Fann for these ones
</td></tr>
<tr><td colspan="2" align="center">    
    <input type="submit" value=" Submit " name="imgset">
    <input type="reset" value=" Reset ">
</td></tr>
</table>
</form>
<br>

<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table class="std" width="650" border="1" cellpadding="5">
<tr><th colspan="2" class="a">Vacation Mode</th></tr>
<tr><td colspan="2">Vacation mode will disable your account for at least 48 Hours (not ticks!).
After this time You may login anytime to reenable Your account.<p>
While Your planet is in vacation <b>You do not get any resources nor advances in
Research/Construction/Production</b><p>
You cannot go into vacation if You are attacking or under attack (or defending).
</td></tr>
<tr><td width="60%">Go into vacation mode</td><td align="center" width="40%">
    <input type="submit" value="Vacation" name="vacation"></td></tr>
</table>
</form>
<br>

<?php

echo<<<EOF
<form method="post" action="$_SERVER[PHP_SELF]">
<table class="std" width="650" border="1" cellpadding="5">
<tr><th class="a">Delete</th></tr>
<tr><td>To delete your planet You have to press the delete button - 
and stop playing. <b>After beeing 12 h idle</b> your account will be 
deleted automatically. If you login during this time deletion is 
cancelled.</td></tr>
<tr><td align="center"><input type="submit" value=" Delete " name="delete"></td></tr>
</table>
</form>

EOF;

?>

</center>
</div>

<?php

require "footer.php";
?>
