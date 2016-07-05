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

require "header.php";
my_header("",0,0);

require "msgbox.php";

titlebox("Military Stat");
?>
<center>

<table border="0" width="650" style="font-size: 10px;text-align:center;">
<tr class="a"><th colspan="19">Ship/PDS stats</th></tr>
<tr class="b">
    <th>Name</th>

    <th>Spez</th>
    <th>Type</th>

    <th>T1</th>
    <th>T2</th>
    <th>T3</th>

    <th>Init</th>
    <th>Agil.</th>
    <th>Wsp</th>

    <th>Guns</th>
    <th>Pwr</th>
    <th>Armor</th>
    <th>Resist.</th>

    <th>Metal</th>
    <th>Cryst</th>
    <th>Eon</th>

    <th>Ticks</th>
    <th>Fuel</th>
    <th>Speed</th>
</tr>
<?php
$result = mysqli_query($db, "SELECT name,class,type,t1,t2,t3,init,agility,".
		      "weapon_speed,guns,power,armor,resistance,".
		      "metal,crystal,eonium,build_ticks,fuel,speed ".
		      "FROM unit_class ORDER BY id");

if ($result && mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_row($result)) {
    echo "<tr><td align=\"left\">$row[0]</td>\n";
    
    echo "<td>";
    switch ($row[1]) {
    case 1: echo "NORM"; break;
    case 2: echo "EMP"; break;
    case 3: echo "CLOAK"; break;
    case 4: echo "CAP"; break;
    case 5: echo "PDS"; break;
    case 6: echo "APDS"; break;
    case 6: echo "APDS"; break;
    }
    echo "</td>";

    for ($i=2; $i<6; $i++) {
      echo "<td>";
      switch ($row[$i]) {
      case 1: echo "FI"; break;
      case 2: echo "CO"; break;
      case 3: echo "FR"; break;
      case 4: echo "DE"; break;
      case 5: echo "CR"; break;
      case 6: echo "BS"; break;
      case 7: echo "MISS"; break;
      case 255: echo "ALL"; break;
      }
      echo "</td>";
    }
    for ($i=6; $i<19; $i++) {
      echo "<td>" . $row[$i] ."</td>\n";
    }
    echo "</tr>\n";
  }
}
?>
<tr><td colspan="19" style="text-align: left" height="30">
Typically a ship may target two different 
types of ships - (T1 and T2), some ships even target a third 
type (T3) as given in this 
table.</td></tr>
</table>

</center>

<?php
require "footer.php";
?>
