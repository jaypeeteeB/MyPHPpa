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

require "options.php";
require "dblogon.php";

$imgpath="true";
require "header.php";
my_header("",0,0);

require "msgbox.php";

require_once "session.inc";
session_init();
$need_navigation=0;
if (!session_check())
  $need_navigation=1;

if($need_navigation == 1) {
  require_once "navigation.inc";
  echo "<div id=\"main\">\n";
}

titlebox("Goals");
?>
<center>
<table class="std_nb">
<tr class="a"><th colspan=>Goals of the Game</th></tr>
<tr><td><br>
This game is a round or better <b>tick</b> based tactical/strategical
war game in a SciFi environment.<br>
<br>
Major goal (besides having fun) is to reach rank #1 in universe
ranking or at least a good position in it. To achieve this you need
<b>score</b> which is in turn a result of spending <b>resources</b>
(named: metal, crystal or eonium) into Spaceships and similar more or
less military equipment. <br>
<br>
Resources are mined on <em>initiliazed</em>
<b>Asteroids</b> floating around your planet. These asteroids may be found in
deep space by using appropriate scans or by attacking other planets in
the universe using <b>Astropods</b>, a specialized ship for transporting
asteroids to your home planet. To support your astropods you could
(and should) send other ships along to destroy or distract enemy
forces.<br>
<br>
The different types of implemented ships are divided in several classes
(see <a href="help_stat.php">MilStats</a>) which can attack (or stunn
using EMP) a distinct class of ships. To build these ships you have to
<b>research</b> the appropriate knowledge and <b>construct</b> (see <a
href="help_rc.php">Res/Con</a>) some industrial production place.<br>
To be able to plan (see <a href="battlecalc/index.php">Battlecalc</a>)
better and more effective attacks you can research
and build scans giving you different information about potential
g<br>
<br>
In turn, beeing attacked yourself, you may build a planetarian defense
system (<b>PDS</b>) or a big fleet to defeat any attacker.<br>
<br>
Planets are grouped together in <b>galaxies</b> (and may not attack
each other) which in turn are grouped into <b>clusters</b> forming the
universe.
</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td align=left>Khan</td></tr>
</table>

</center>

<?php
if($need_navigation == 1)
  echo "</div>\n";

require "footer.php";
?>
