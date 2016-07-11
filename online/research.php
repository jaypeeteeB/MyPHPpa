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

function print_rc_row ($row, $status) {

  global $db, $Planetid;

  if ($row[7] !=0) {
    $r = mysqli_query ($db, "SELECT name from rc_class WHERE id='$row[7]'" );
    $myr = mysqli_fetch_row($r);
    $blocked_name = $myr[0];
  }

  echo "<tr>\n";
  echo "<td>$row[0]</td>\n";
  echo "<td>$row[1]<br><b>Cost: $row[2] Metal, $row[3] Crystal";
  if ($row[8]!=0) {
    echo ", $row[8] Eonium";
  } 
  if (ISSET($blocked_name)) 
    echo "<br><font color=\"red\">Disables $blocked_name<font>";
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
  case 2: echo "<b>Researching</b>"; break;
  case 1: if ($status) {
    echo "<span class=\"red\">Other research in progress</span>";
  } else {
    echo "<a href=\"".$_SERVER['PHP_SELF']."?id=$row[6]\">Research</a>";
  }
  break;
  case -1: echo "Blocked"; break;
  }
  echo "</td>\n";
  echo "</tr>\n";
}

function build_research ($id) {

  global $myrow; /* resources */
  global $Planetid, $db,$msg;

  $res = mysqli_query ($db, "SELECT metal, crystal, eonium ".
		      "FROM rc_class WHERE id='$id'" );

  if (mysqli_num_rows($res) == 1) {
    $price = mysqli_fetch_row($res);
    
    if ( $myrow["metal"] < $price[0] || $myrow["crystal"] < $price[1] ||
         $myrow["eonium"] < $price[2] ) {

      $msg = "Not enough Resources!!!";
      return 0;
    }

    $res = mysqli_query($db, "UPDATE rc set status=2 ".
		       "WHERE rc_id='$id' AND status=1 AND planet_id='$Planetid'" );

    if (!$res || mysqli_affected_rows($db)!=1) {
      $msg="Can't research that now";
      return 0;
    } else {
      $query = "INSERT DELAYED INTO rc_build (planet_id,rc_id,build_ticks) ".
	 "SELECT '$Planetid','$id',build_ticks from rc_class ".
	 "WHERE id='$id'";
      $res = mysqli_query($db, $query );

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
 * wird aber auch gebraucht um festzustellen ob research ueberhaupt moeglich 
 * ist
 */
$researching = 0;
$result = mysqli_query ($db, "SELECT status from rc, rc_class ".
		       "WHERE rc.planet_id='$Planetid' ".
		       "AND rc.rc_id=rc_class.id AND rc_class.type=0 ".
		       "AND rc.status=2" 
		       );
if (mysqli_num_rows($result) > 0) $researching = 1;

if (ISSET($_REQUEST["id"]) && $researching == 0) {
  $researching = build_research($_REQUEST["id"]);
}

if (ISSET($_REQUEST["toggle"])) {
  if ($_REQUEST["toggle"]==1) $mysettings -= 128;
  else $mysettings += 128;
  mysqli_query ($db, "UPDATE user SET settings='$mysettings' ".
               "WHERE planet_id='$Planetid'");
}

/* top table is written now */
top_header($myrow);

if (ISSET($msg) && $msg != "")
     titlebox("Research",$msg);
else
     titlebox("Research");

if ($mysettings & 128) {
  $rc_status = "rc.status IN (1,2)";
  $toggle = 1;
} else {
  $rc_status = "rc.status != 0";
  $toggle = 2;
}

echo <<<EOF
<center>
<table border="0" width="650">
<tr><td align="right"><a href="$_SERVER[PHP_SELF]?toggle=$toggle">
    <span class="small">Toggle visibility</span></a></td></tr>
</table>
<table border="1" width="650">
<tr class="a"><th width="140">Research</th>
    <th width="350">Description</th>
    <th width="70">Ticks</th>
    <th width="90">Status</th>
</tr>

EOF;

$q = "SELECT rc_class.name, rc_class.description, rc_class.metal, ".
  "rc_class.crystal, rc_class.build_ticks, rc.status, rc.rc_id, ".
  "rc_class.block_id, rc_class.eonium FROM rc, rc_class WHERE rc.planet_id = '$Planetid' ".
  "AND rc_class.type=0 AND rc.rc_id = rc_class.id AND rc_class.id!=0 ".
  "AND $rc_status ORDER BY rc_class.id";

$result = mysqli_query($db,  $q );
if (mysqli_num_rows($result) > 0) {
  while ($myres = mysqli_fetch_row($result)) {
    print_rc_row ($myres, $researching);
  }
} else {
  echo "<tr><td colspan=4>No researches available</td></tr>";
}
echo "</table>\n";

require "footer.php";
?>
