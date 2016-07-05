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
require "fleet_util.php";

function print_coords ($x, $y, $z) {
  return "<A HREF=\"galaxy.php?submit=1&x=$x&y=$y\">$x:$y:$z</A>";
}

function print_color ($text, $color=0) {
  if ($color == 0) {
    $c="red";
  } else {
    $c = "green";
  }
  return "<span class=\"$c\">$text</span>";
}

function add_recall_link ($f) {
  $a = "<td class=\"tdlink\"><a href=\"military.php?execute=1&fleet_$f=255\">R</a></td>\n";
  return $a;
}

function print_fleet_id ($pid, $eta, $size, $type, $num=0)
{  
  global $db, $Planetid;

  $result = mysqli_query ($db, "SELECT planetname, x, y, z FROM planet WHERE id='$pid'");
  if (!$result) {
    return "Error querying planet $id from DB<br>\n";
  }

  $prow = mysqli_fetch_row ($result);
  if (!$prow) {
    return "Error fetching planet $id from DB<br>\n";
  }

  if ($type) $p = "+ ";
  else $p = "- ";

  $p .= $prow[0];
  $p = print_color ($p, $type);
  
  if ($num != 0 && $num!=4) {
    $ret = "<tr><td></td><td>$p (" . 
        print_coords($prow[1],$prow[2],$prow[3]) .")</td>".
        add_recall_link($num);
  } else {
    $ret = "<tr><td></td><td colspan=\"2\">$p (" . 
        print_coords($prow[1],$prow[2],$prow[3]) .")</td>";
  }

  $ret .= "<td align=\"center\">". print_color($eta,$type) .
    "</td><td align=\"right\">". 
    print_color("". (int)$size."",$type) ."</td></tr>\n";
  return $ret;
}

function add_def_link ($x, $y, $z) {
  $a = "<th><a href=\"military.php?execute=1&fleet_1=6&".
     "fleet_1_x=$x&fleet_1_y=$y&fleet_1_z=$z\">1</a>&nbsp;";
  $a .= "<a href=\"military.php?execute=1&fleet_2=6&".
     "fleet_2_x=$x&fleet_2_y=$y&fleet_2_z=$z\">2</a>&nbsp;";
  $a .= "<a href=\"military.php?execute=1&fleet_3=6&".
     "fleet_3_x=$x&fleet_3_y=$y&fleet_3_z=$z\">3</a></th>";
  return $a;
}

function print_planet ($name, $x, $y, $z, $f, $h, $id, $d) {
  global $Planetid;

  if ($id == $Planetid) {
    if ($d == 0) $m = "<tr class=\"incoming\">";
    else $m = "<tr class=\"outgoing\">";
  } else {
    $m = "<tr>";
  }

  if ($id != $Planetid && $d == 0) {
    $m .= "<th align=\"right\">$x:$y:$z</th><th>$name</th>";
    $m .= add_def_link ($x,$y,$z);
  } else {
    $m .= "<th align=\"right\">$x:$y:$z</th><th colspan=2>$name</th>";
  }

  $m.= "<th></th><th align=\"right\">" . 
      print_color("$h", 0) . "/" .
      print_color("$f", 1) ."</th>";
  $m .= "</tr>\n";
  return $m;
}

function fetch_planet ($pid, &$f, &$h, $direction) {

  global $db, $Planetid;

  if ( $direction == 0) {
    $q = "SELECT fleet_id, planet_id, type, (ticks & 0xF) "
       . "FROM fleet WHERE target_id='$pid'";
  } else {
    $q = "SELECT fleet_id, target_id, type, (ticks & 0xF), num "
       . "FROM fleet WHERE planet_id='$pid' AND type > 0";
  }

  // echo "$q<br>\n";

  $fres = mysqli_query ($db,  $q );
  if (!$fres || mysqli_num_rows($fres) == 0) {
    return "";
  }

  $target_fleet = "";
  while ($frow = mysqli_fetch_row($fres)) {

    $num_ships = fetch_fleet_sum ( $frow[0] );

    if ($frow[2] < 10) {
      /* defending */
      $type = 1;
      $f += (int) $num_ships;
    } else {
      /* attacking */
      $type = 0;
      $h += (int) $num_ships;
    }
    // echo "$f $h $num_ships $type<br>\n";

    if ($direction == 1 && $Planetid==$pid) {
      $target_fleet .= 
          print_fleet_id ($frow[1], $frow[3], $num_ships, $type, $frow[4]);
    } else {
      $target_fleet .= print_fleet_id ($frow[1], $frow[3], $num_ships, $type);
    }
  }

  return $target_fleet;
}

function print_total ($x, $y, $direction) {
  global $db;

  /*
   * select id, z, name from planet where x='$x' and y='$y' order by z
   * select fleet_id,type,eta FROM fleet where (target_id|planet_id)='$pid' 
   *
   * if (fleet_id)
   * select sum(num) from units,unit_class AS uc 
   *  where units.unit_id=uc.id AND uc.type!=cloaked AND fleet_id=fleet_id
   *
   * total (sum(num)) Hostile
   * total (sum(num)) Friendly
   */

  $q = "SELECT id, z, planetname FROM planet WHERE x='$x' and y='$y' order by z";
  $pres = mysqli_query ($db,  $q );

  if (!$pres)
    return;

  while ($prow = mysqli_fetch_array($pres) ) {
    $target_fleet = "";

    $hostile = 0;
    $friendly = 0;
    $target_fleet = fetch_planet ($prow["id"], $friendly, $hostile, $direction);

    if ($target_fleet != "") {
      echo print_planet ($prow["planetname"], $x, $y, $prow["z"], 
                         $friendly, $hostile, $prow["id"], $direction);
      echo $target_fleet;
    }
  }

}

function print_incoming ($x, $y) {
  print_total ($x, $y, 0);
}

function print_outgoing ($x, $y) {
  print_total ($x, $y, 1);
}

/* top table is written now */
top_header($myrow);

titlebox("Galstatus");
?>

<center>
<table width="650" border="1">
  <tr><th class="a" colspan="5">Incoming</th></tr>
  <tr><th width="120" >Coordinates</th>
      <th width="300">Planet</th>
      <th width="70">DEF</th>
      <th width="90" >ETA</th>
      <th width="170" align="right">Size(<span class="red">H</span>/<span class="green">F</span>)</th>
  </tr>
<?php
print_incoming ($myrow["x"], $myrow["y"]);
?>
<!-- </table>
<br>
<table width="650" border="1"> -->
<tr><td colspan=5 style="border-style:none"><br><br></td></tr>

  <tr><th class="a" colspan="5">Outgoing</th></tr>
  <tr><th width="120" align="center">Coordinates</th>
      <th width="370" colspan="2">Planet</th>
      <th width="90" align="center">ETA</th>
      <th width="170" align="right">Size(<span class="red">H</span>/
        <span class="green">F</span>)</th>
  </tr>
<?php
print_outgoing ($myrow["x"], $myrow["y"]);

?>
</table>
</center>

<?php
require "footer.php";
?>
