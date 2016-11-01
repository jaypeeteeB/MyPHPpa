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

function print_td_pop ($link, $desc, $target, $option="") {
  global $browser_type, $imgpath;
  $mlink = $link . ".php";
  if ($option && $option!="")
    $mlink .= "?$option";

  echo "<tr><td><a href=\"$mlink\" target=\"$target\">".
       "<b>$desc</b></a></td></tr>\n";
}

function print_td ($link, $desc, $target="main", $option="") {
  global $browser_type, $imgpath;
  $mlink = "$link.php";
  if ($option && $option!="")
    $mlink .= "?$option";

  echo "<tr><td><a href=\"$mlink\" target=\"$target\">".
       "<b>$desc</b></a></td></tr>\n";
}

function print_empty() {
  echo "<tr><td class=\"hidden\"></td></tr>\n";
}

$browser=getenv ("HTTP_USER_AGENT");
if ($mysettings & 32 || 
    preg_match("/lynx/i", $browser) ||
    preg_match("/w3m/i", $browser) ||
    preg_match("/Mozilla\/4.7/i", $browser)) {
  $browser_type=0;
} else {
  $browser_type=1;
}


if (ISSET($imgpath) && $imgpath != "") {
  
echo "<html>\n<head>\n";
require_once "mobile.inc";
if ($mobile_detect) {
  echo "   <LINK rel=stylesheet type=\"text/css\" href=\"mobile.css\">";
} else {
  if ($mysettings&32)
    echo "<LINK rel=stylesheet type=\"text/css\" href=\"npb.css\">";
  else
    echo "<LINK rel=stylesheet type=\"text/css\" href=\"mpb.css\">";
}
echo <<<EOF
<meta name="viewport" content="width=150,initial-scale=1.0" />

</head>
<body background="$imgpath/myphppa/navbg.jpg" text="#FFFFFF">
<div class="nav">
<table class="nav">
EOF;

} else {

echo "<html>\n<head>\n";
if ($mysettings&32)
  echo "<LINK rel=stylesheet type=\"text/css\" href=\"npa.css\">";
else
  echo "<LINK rel=stylesheet type=\"text/css\" href=\"mpa.css\">";
echo <<<EOF
</head>
<body class="nav">
<div class="nav">
<table class="nav">
EOF;

}

print_td("overview","Overview");
print_td("galstatus","Galstatus");
print_empty();
if (ISSET($_GET["help"]) && $_GET["help"]) {
  print_td("navigation", "Command", "navigation");

  if ($_GET["help"]) {
    print_empty();
    print_td("help_general","Rules");
    print_td("help_goal","Goals");
    print_td("help_startup","Startup");
    print_td("help_links","Links");
    print_empty();
    print_td("help_rc","Res/Con");
    print_td("help_stat","MilStats");
    print_td("help_form","Formulas");
    print_empty();
    print_td("help_story","Story");
    print_td("highscore","Hall of Fame");
    print_td("statistics","Statistics");
    print_empty();
    print_td_pop("battlecalc/index","Battlecalc", "Battlecalc");
  }

} else {
  print_td("news","News");
  print_td("journal","Journal");
  print_td("messages","Messages");
  print_td("politics","Politics");
  print_td("forum","Forum");
  print_empty();
  print_td("research","Research");
  print_td("construct","Construction");
  print_td("resource","Resource");
  // print_td("population","Population");
  print_empty();
  if ($Username == "admin") {
    print_td("market","Market");
  }
  print_td("product","Production");
  print_td("pds","PDS");
  print_td("waves","Waves");
  print_td("military","Military");
  print_empty();
  print_td("galaxy","Galaxy");
  print_td("universe","Universe");
  if (ISSET($_GET["alliance"]) && 1 == (int)$_GET["alliance"]) {
    print_empty();
    print_td("navigation","Alliance","navigation","alliance=0");
    print_td("alliance","Main");
    print_td("alllist","Listing");
    print_td("allmembers","Members");
    print_td("allforum","Forum");
  } else {
    print_td("navigation","Alliance","navigation","alliance=1");
  }
  print_empty();
  print_td("affairs","Affairs");
  print_td("preferences","Preferences");
  print_empty();
  print_td("navigation", "Help", "navigation", "help=1");
  print_td("logout","Logout","_top");
  
  if ($Username == "admin" || $Username == "moderator") {
    print_empty();
    print_td_pop("admin","Admin","MyPHPpaAdmin");
  }
}
?>
</table>
</div>
</body>
</html>
