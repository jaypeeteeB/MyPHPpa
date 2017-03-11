<?php

/*
 * MyPHPpa
 * Copyright (C) 2016 Jens Beyer
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

function print_td ($link, $desc, $target="", $option="") {
  global $imgpath;
  $mlink = "$link.php";
  if ($option && $option!="")
    $mlink .= "?$option";

  if ($target == "") {
    echo "<li><a href=\"$mlink\"><b>$desc</b></a></li>\n";
  } else {
    echo "<li><a href=\"$mlink\" target=\"$target\">".
       "<b>$desc</b></a></li>\n";
  }
}

function print_empty() {
  echo "<li class=\"hidden\"></li>\n";
}

echo "<div class=\"nav\">\n";
echo "<div id=\"menu-icon\"></div>\n";

echo "<nav><ul id=\"nav\">\n";

print_td("overview","Overview");
print_td("galstatus","Galstatus");
print_empty();

  print_td("news","News");
  print_td("journal","Journal");
  print_td("messages","Messages");
  print_td("politics","Politics");
  print_td("forum","Forum");
  print_empty();
  print_td("research","Research");
  print_td("construct","Construction");
  print_td("resource","Resource");
  print_empty();
  print_td("product","Production");
  print_td("pds","PDS");
  print_td("waves","Waves");
  print_td("military","Military");
  print_empty();
  print_td("galaxy","Galaxy");
  print_td("universe","Universe");

  echo "<li><a href=\"#\"><b>Alliance</b></a>\n";
  echo "<ul>\n";
    print_td("alliance","Main");
    print_td("alllist","Listing");
    print_td("allmembers","Members");
    print_td("allforum","Forum");
  echo "</ul>\n";
  echo "</li>\n";

  print_empty();
  print_td("affairs","Affairs");
  print_td("preferences","Preferences");
  print_empty();

  echo "<li><a href=\"#\"><b>Help</b></a>\n";
  echo "<ul>\n";
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
    print_td("battlecalc/index","Battlecalc", "Battlecalc");
  echo "</ul>\n";
  echo "</li>\n";

  print_td("logout","Logout");
  
  if ($Username == "admin" || $Username == "moderator") {
    print_empty();
    print_td("admin","Admin","MyPHPpaAdmin");
    if ($Username == "admin") {
      print_td("population","Population");
      print_td("market","Market");
    }
  }
?>
</ul></nav>

</div>
