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

require "standard.php";
/* top table is written now */

/* folgendes ist fast identisch zu research.php
 * nur tape = 1
 * bzw construct.. statt research..
 */

function print_rc_row ($row, $status) {

  global $Planetid, $db;

  if ($row[7] !=0) {
    $r = mysqli_query ($db, "SELECT name from rc_class WHERE id='$row[7]'" );
    $myr = mysqli_fetch_row($r);
    $blocked_name = $myr[0];
  }

  echo "<tr>\n";
  echo "<td>$row[0]</td>\n";
  echo "<td>$row[1]<br><b>Cost: $row[2] Metal, $row[3] Crystal";
  if ($row[8] != 0) echo ", $row[8] Eonium";

  if (ISSET($blocked_name)) 
    echo "<br><font color=\"red\">Disables $blockedname<font>";
  echo "</td>\n<td>";
  if ($row[5] == 2) {
    $rb = mysqli_query ($db, "SELECT build_ticks from rc_build ".
		       "WHERE planet_id='$Planetid' AND rc_id='$row[6]'" );
    $myb = mysqli_fetch_row($rb);
    echo "<b>$myb[0] ticks</b>";
  } else {
    echo "$row[4] ticks";
  }
  echo "</td>\n<td>";

  switch ($row[5]) {
  case 3: echo "<span class=\"green\">Completed</span>"; break;
  case 2: echo "<b>Constructing</b>"; break;
  case 1: if ($status) {
    echo "<span class=\"red\">Other construction in progress</span>";
  } else {
    echo "<a href=\"".$_SERVER['PHP_SELF']."?id=$row[6]\">Construct</a>";
  }
  break;
  case -1: echo "Blocked"; break;
  }
  echo "</td>\n";
  echo "</tr>\n";
}

function build_construct ($id) {

  global $myrow; /* resources */
  global $Planetid, $db, $msg;

  $res = mysqli_query ($db, "SELECT metal, crystal, eonium ".
		      "FROM rc_class WHERE id='$id'" );

  if (mysqli_num_rows($res) == 1) {
    $price = mysqli_fetch_row($res);
    
    if ( $myrow["metal"] < $price[0] || $myrow["crystal"] < $price[1] ||
         $myrow["eonium"] < $price[2] ) {

      $msg .= "Not enough Resources!!!";
      return 0;
    }

    $res = mysqli_query($db, "UPDATE rc set status=2 ".
		       "WHERE rc_id='$id' AND status=1 AND planet_id='$Planetid'" );

    if (!$res ||  mysqli_affected_rows($db)!=1) {
      $msg .= "Can't construct that now";
      return 0;
    } else {

      $res = mysqli_query($db, "INSERT DELAYED INTO rc_build (planet_id,rc_id,build_ticks) ".
			 "SELECT '$Planetid','$id',build_ticks from rc_class ".
			 "WHERE id='$id'" );
      $myrow["metal"]   -= $price[0];
      $myrow["crystal"] -= $price[1];
      $myrow["eonium"]  -= $price[2];
      
      $q = "UPDATE planet SET metal='$myrow[metal]',crystal='$myrow[crystal]',".
	 "eonium='$myrow[eonium]' where id='$Planetid'";
      $res = mysqli_query($db, $q );
    }

    return 1;
  } else {
    return 0;
  }
}

/* dies koennte in unter query als zusaetzliche spalte eingebaut werden 
 * wird aber auch gebraucht um festzustellen ob research ueberhaupt moeglich ist
 */
$researching = 0;
$result = mysqli_query ($db, "SELECT status from rc, rc_class where rc.planet_id='$Planetid' ".
		       "AND rc.rc_id=rc_class.id AND rc_class.type=1 AND rc.status=2");
if (mysqli_num_rows($result) > 0) $researching = 1;

if (ISSET($_REQUEST["id"]) && $researching == 0) {
  $researching = build_construct($_REQUEST["id"]);
}

if (ISSET($_REQUEST["toggle"])) {
  if ($_REQUEST["toggle"]==1) $mysettings -= 128;
  else $mysettings += 128;
  mysqli_query ($db, "UPDATE user SET settings='$mysettings' ".
               "WHERE planet_id='$Planetid'");
}

require_once "navigation.inc";

echo "<div id=\"main\">\n";

/* top table is written now */
top_header($myrow);

if (ISSET($msg) && $msg != "")
     titlebox("Construction",$msg);
else
     titlebox("Construction");

if ($mysettings & 128) {
  $rc_status = "rc.status IN (1,2)";
  $toggle = 1;
} else {
  $rc_status = "rc.status != 0";
  $toggle = 2;
}

echo <<<EOF
<center>
<table class="std_nb" border="0" width="650">
<tr><td align="right"><a href="$_SERVER[PHP_SELF]?toggle=$toggle">
    <span class="small">Toggle visibility</span></a></td></tr>
</table>
<table class="std" border="1" width="650">
<tr class="a"><th width="140">Construction</th>
    <th width="350">Description</th>
    <th width="70">Ticks</th>
    <th width="90">Status</th>
</tr>
EOF;

$q = "SELECT rc_class.name, rc_class.description, rc_class.metal ,rc_class.crystal,".
     "rc_class.build_ticks, rc.status, rc.rc_id, rc_class.block_id,rc_class.eonium ".
     "FROM rc, rc_class WHERE rc.planet_id = '$Planetid' and rc_class.type=1 ".
     "AND rc.rc_id = rc_class.id AND $rc_status ORDER BY rc_class.id";

$result = mysqli_query($db,  $q );
if (mysqli_num_rows($result) > 0) {
  while ($myres = mysqli_fetch_row($result)) {
    print_rc_row ($myres, $researching);
  }
} else {
  echo "<tr><td colspan=4>No contructions available</td></tr>";
}

echo "</table>\n</center>\n";

echo "</div>\n";
require "footer.php";
?>
