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

require_once "options.php";
require_once "dblogon.php";
require_once "newcoords.php";
include_once "get_ip.php";
include_once "check_ip.php";
require_once "create_user.php";
require_once "auth_check.php";
require "logging.php";

$imgpath="true";
$_SESSION["ImgPath"] = $imgpath;
require_once "header.php";

function db_error ($txt="Database error") {

  echo "<html><body><br>";
  echo "<b>$txt</b>";
  echo "</body></html>";
  die;
}

function check_legal ($n, $p) {

  if (preg_match ("/</", $n) || preg_match ("/</", $p) ||  
      preg_match ("/admin/i",  $n) || preg_match ("/admin/i", $p) ) { 
     return 7;
  }
  return 0;
}

function check_length ($l, $n, $p) {

  if (strlen($n) < 4  || strlen($p) < 4  || strlen($l) < 2 || 
      strlen($n) > 20 || strlen($p) > 20 || strlen($l) > 25) {
    if (strlen($l) < 2 || strlen($l) > 25) return 51;
    return 52;
  }
  return 0;
}

function check_taken_planet ($n, $p) {
  global $db;

  $result = mysqli_query($db, "SELECT * FROM planet " .
                        "WHERE leader='$n' OR planetname='$p'");
  if (!$result) db_error();
  if (mysqli_num_rows($result) == 0) return 0;

  $ret = 32;
  $result = mysqli_query($db, "SELECT * FROM planet " .
			"WHERE leader='$n' ");

  if (!$result) db_error();
  if (mysqli_num_rows($result) == 1) $ret = 31;
  return $ret;
}

function check_taken_user ($l, $e) {
  global $db;

  $result = mysqli_query($db, "SELECT * FROM user " .
                        "WHERE login='$l' OR email='$e'");
  if (!$result) db_error();
  if (mysqli_num_rows($result) == 0) return 0;

  $ret = 12;
  $result = mysqli_query($db, "SELECT * FROM user " .
			"WHERE login='$l' ");

  if (!$result) db_error();
  if (mysqli_num_rows($result) == 1) $ret = 11;
  return $ret;
}

function check_email ($e) {

  if ($e && (!preg_match ("/@/", $e) || !preg_match ("/\./", $e) ||
	     strlen ($e) < 7))
    return 4;
  /* should check domain here */
  return 0;
}

function send_password($pid) {
  global $game, $db, $round, $imgpath;

  $q = "SELECT email, login, password FROM user WHERE planet_id=$pid ";
  $result = mysqli_query($db, $q);
    
  if ($result && mysqli_num_rows($result) == 1) {
    $rowu = mysqli_fetch_array($result);
  } else {
    db_error("DBerror: sending password");
  }

  $q = "SELECT leader, planetname, x, y, z FROM planet WHERE id=$pid ";
  $result = mysqli_query($db, $q);
  
  if ($result && mysqli_num_rows($result) == 1) {
    $rowp = mysqli_fetch_array($result);
  } else {
    db_error("DBerror: sending password");
  }

  setcookie("Valid",md5($round),time()+432000);
  $_SESSION["ImgPath"] = $imgpath;

  my_header(0, 0, 0);

  mail("$rowu[email]", "$game signup password", 
       "\nLogin: $rowu[login]\n".
       "Password: $rowu[password]\n".
       "Email: $rowu[email]\nCoords: [$rowp[x]:$rowp[y]:$rowp[z]] ".
       "$rowp[leader] of $rowp[planetname]\n\nHave Fun!!\n\n".
       "PS: Remember that idle planets with less then 4 roids will ".
       "be deleted\n    after 12 hours.\n",
       "From: MyPHPpa@web.de\nReply-To: MyPHPpa@web.de\n".
       "X-Mailer: PHP/" . phpversion());

  do_log_id($pid, 6, 1, get_ip()); 
  do_log_id($pid, 6, 2, get_type()); 
      
  echo "<center><br><img src=\"img/logo.jpg\"" .
    "width=\"290\" height=\"145\"><br>\n";

  echo "<b>The password has been mailed to $rowu[email]</b>";
  echo "<br><br><a href=\"index.php\" target=\"_top\">Login</a></center>";
  echo "</body></html>";
  die;
} 

// if ($signupclosed == 1 || !check_ip(get_ip()) || $mytick > $end_of_round) {
if ($signupclosed == 1 || !check_ip(get_ip())) {
  my_header("",0,0);
  echo "<center><br><img src=\"img/logo.jpg\"" .
     "width=\"290\" height=\"145\"><br>\n";
  echo "<br><br><br>\n<h1>Sorry, atm signup is closed</h1>";
  echo "</center></body></html>";
  die();
}

if (ISSET($_COOKIE["Valid"]) && $_COOKIE["Valid"] != "") {
  
  if ($_COOKIE["Valid"] == md5($round)) {
     my_header(0,0,0);
     echo "<center><br><img src=\"img/logo.jpg\"" .
       "width=\"290\" height=\"145\"><br>\n";
     
     echo "<H1>You signed up already!!</H1></CENTER></body></html>\n";
    die();
  } else {
    setcookie("Valid","");
  }
}

/* hier gehts los */
$login = "";
$email = "";
$nick = "";
$planet = "";

if (ISSET($_POST["submit"]) && $_POST["submit"] != "") {

  $taken = 0;

  $login = trim(chop($_POST["login"]));
  $email = trim(chop($_POST["email"]));
  $nick  = trim(chop($_POST["nick"]));
  $planet= trim(chop($_POST["planet"]));
  $pat = array("/^\((.*)\)/","/^{(.*)}/","/^\[(.*)\]/","/</","/'/");
  $rep =  array("\\1","\\1","\\1","","");
  $planet = preg_replace ($pat, $rep, $planet);
  $nick = htmlspecialchars ($nick);
  $planet = htmlspecialchars ($planet);
  $nick = trim(chop($nick));
  $planet= trim(chop($planet));


  if (!$login || !$email || !$nick || !$planet)
    $taken = 100;
  
  if (!$taken && ($login=="" || $email=="" 
      || $nick==""  || $planet=="")) 
    $taken = 101;

  if (!$taken) $taken = check_email ($email);
  if (!$taken) $taken = check_length ($login, $nick, $planet);
  if (!$taken) $taken = check_taken_user ($login, $email);

  if (!$taken) $taken = check_legal ($nick, $planet);
  if (!$taken) $taken = check_taken_planet ($nick, $planet);

  if (!$taken) {
    /* jetzt sollte es tun */
    require_once "strong-passwords.php";

    $result = mysqli_query($db, "SELECT tick FROM general"); 
    $row = mysqli_fetch_row($result);
    $mytick = $row[0];

    $pw = generateStrongPassword(8);

    $res = get_new_coords ($x, $y, $z);
    if ($res) db_error("Sorry universe is full!!");

    $result = mysqli_query($db, "INSERT into planet set planetname='$planet'," .
			  "leader='$nick',mode=0xF1,x=$x,y=$y,z=$z");
    if (!$result) db_error("DBerror: Insert planet");

    $planet_id = mysqli_insert_id ($db);
    if (!$planet_id) db_error("DBerror: Get Planetid");

    $result = mysqli_query($db, "INSERT into user ".
			  "SET login='$login',password='$pw'," .
			  "email='$email',planet_id='$planet_id',".
			  "signup=NOW(),first_tick='$mytick'");

    if (!$result) db_error("DBerror: Insert User");

    /* add galaxy counter
     */
    $result = mysqli_query ($db, "UPDATE galaxy SET members=members+1 ".
                           "WHERE x=$x AND y=$y");
    if (!$result) db_error("DBerror: Update galaxy");

    /* suplementary entries 
     */
    create_user($planet_id);

    /* here we go */
    send_password($planet_id);

    die;
  }
}

my_header(0,0,0);

echo "<center><br><img src=\"img/logo.jpg\"" .
     "width=\"290\" height=\"145\"><br>\n";

if (ISSET($_POST["submit"])) {

  /* Failed signup */
  echo "<b>";

  switch ($taken) {
  case 11: 
    echo "Login already taken!!";
    $login = "";
    break;
  case 12:
    echo "Email already taken!!";
    $email = "";
    break;
  case "31":
    echo "Leader name already taken!!";
    $nick = "";
    break;
  case "32":
    echo "Planetname already taken!!";
    $planet = "";
    break;
  case 4:
    echo "Your email seems unbelievable";
    $email = "";
    break;
  case 51:
    echo "Minimum Login length is 2 chars with a maximum of 25";
    break;
  case 52:
    echo "Nick / Planetname must have a " .
      "length of at least 4 and a maximum of 20 chars";
    break;
  case 7:
    echo "Illegal char (&lt;) or leader/planetname\n";
    break;
  default:
    echo "<b>Please fill all fields !!";
  }
  echo "</b><br>\n";

} else {
  echo "<br>";
}

echo <<<EOF
<br>
<FORM method="post" action="$_SERVER[PHP_SELF]">
<TABLE border=1><tr><td>
<TABLE border=0 width=450 cellspacing=5>
  <tr><td colspan=3>&nbsp;</td></tr>
  <TR>
    <TD align=center colspan=3>
    <b>A password will be mailed to you after <br>
       the login has been verified</b>.</TD>
  </TR>
  <tr><td colspan=3><hr></td></tr>
  <TR>
    <TD align=right width=120>Login:</TD>
    <TD><input type="text" name="login" size="25" maxlength="29" value="$login"></TD>
    <td rowspan="4" bgcolor="#525252">
      Using offensive or insulting names 
      will lead to deletion of planet!</td>
  </TR>
  <TR>
    <TD align=right>Email:</TD>
    <TD><input type="text" name="email" size="25" maxlength="249" value="$email"></TD>
  </TR>
  <TR>
    <TD align=right>Leader:</TD>
    <TD><input type="text" name="nick" size="25" maxlength="20" value="$nick"></TD>
  </TR>
  <TR>
    <TD align=right>Planetname:</TD>
    <TD><input type="text" name="planet" size="25" maxlength="20" value="$planet"></TD>
  </TR>
  <tr><td colspan=3><hr></td></tr>
  <TR>
   <td align=center colspan=3>
    <span class="red"><b>By signing up you accept to follow the 
    <a href="help_general.php">rules</a> -
    <br>otherwise your account will be banned from gameplay.</b></span><br>
   </TD>
  </TR>
  <TR>
    <TD align=center colspan=3>
    <input type=submit value="Accept" name="submit">
    &nbsp;<input type=reset value=" Reset "></TD>
  </TR>
  <tr><td colspan=3>&nbsp;</td></tr>
</TABLE>
</td></tr></table>
</FORM>

EOF;

require "footer.php";

?>
