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

function print_cost ($m, $c, $e=0) {
  $res = "Cost: ";
  if ($m) $res .= "$m Metal";
  if ($c) {
    if ($m) $res .= ", $c Crystal";
    else  $res .= "$c Crystal";
  }
  if ($e) {
    if ($m || $c) $res .= ", $e Eonium";
    else  $res .= "$e Eonium";
  }
  return $res;
}

function print_unit_row ($row, $stock) {

  $cost = print_cost ($row[3], $row[4], $row[5]);

  echo "<tr><td>$row[1]</td><td>$row[2]<br>$cost" .
    "</td><td align=\"right\">$stock</td><td align=\"right\">$row[6] ticks</td>" .
    "<td><input type=\"text\" name=\"ship_$row[0]\" size=\"8\"></td></tr>\n"; 
}

function prod_unit ($unit, $num) {
  global $myrow; /* resources */
  global $Planetid, $db, $msg;

  $num = (int) $num;
  
  $res = mysqli_query ($db, "SELECT metal, crystal, eonium, build_ticks ".
		      "FROM unit_class AS uc, rc WHERE uc.id='$unit' AND uc.class!=5 ".
		      "AND rc.rc_id=uc.rc_id AND rc.status=3 ".
		      " AND rc.planet_id='$Planetid'" );

  if (mysqli_num_rows($res) == 1) {
    $price = mysqli_fetch_row($res);
    
    if ( $myrow["metal"] < ($price[0] * $num) ) {
      $num = (int) ($myrow["metal"] / $price[0]);
    }
    if ( $myrow["crystal"] < ($price[1] * $num) ) {
      $num = (int) ($myrow["crystal"] / $price[1]);
    }
    if ( $myrow["eonium"] < ($price[2] * $num) ) {
      $num = (int) ($myrow["eonium"] / $price[2]);
    }

    if ($num > 0) {
      $cm = $price[0] * $num;
      $cc = $price[1] * $num;
      $ce = $price[2] * $num;
      $myrow["metal"]   -= $cm;
      $myrow["crystal"] -= $cc;
      $myrow["eonium"]  -= $ce;

      $q = "UPDATE planet SET metal='$myrow[metal]',crystal='$myrow[crystal]',".
	   "eonium='$myrow[eonium]' WHERE id='$Planetid' ".
           "AND metal>='$cm' AND crystal>='$cc' AND eonium>='$ce'";
      $result = mysqli_query($db, $q );

      if (mysqli_affected_rows($db)==1) {
        $q = "INSERT DELAYED INTO unit_build SET planet_id='$Planetid',unit_id='$unit',".
	   "build_ticks=$price[3], num=$num";
        $res = mysqli_query ($db, $q );
      } 

    }
  }
}

if (ISSET($_REQUEST["submit"])) {

  /* aeusserst uncooles handling */
  if (ISSET($_POST["ship_1"])) prod_unit (1, $_POST["ship_1"]);
  if (ISSET($_POST["ship_2"])) prod_unit (2, $_POST["ship_2"]);
  if (ISSET($_POST["ship_3"])) prod_unit (3, $_POST["ship_3"]);
  if (ISSET($_POST["ship_4"])) prod_unit (4, $_POST["ship_4"]);
  if (ISSET($_POST["ship_5"])) prod_unit (5, $_POST["ship_5"]);
  if (ISSET($_POST["ship_6"])) prod_unit (6, $_POST["ship_6"]);
  if (ISSET($_POST["ship_7"])) prod_unit (7, $_POST["ship_7"]);
  if (ISSET($_POST["ship_8"])) prod_unit (8, $_POST["ship_8"]);
  if (ISSET($_POST["ship_9"])) prod_unit (9, $_POST["ship_9"]);
  if (ISSET($_POST["ship_10"])) prod_unit (10, $_POST["ship_10"]);
  if (ISSET($_POST["ship_11"])) prod_unit (11, $_POST["ship_11"]);
  if (ISSET($_POST["ship_12"])) prod_unit (12, $_POST["ship_12"]);
  if (ISSET($_POST["ship_13"])) prod_unit (13, $_POST["ship_13"]);
  if (ISSET($_POST["ship_14"])) prod_unit (14, $_POST["ship_14"]);
}

/* top table is written now */
top_header($myrow);

if (ISSET($msg)) {
  $msg .= "";
} else {
  $msg = "";
}
titlebox("Production", $msg);
?>

<center>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table border="1" width="650">
<tr><th colspan="5" class="a">Ship Production</th></tr>
<tr><th width="100">Unit</th>
    <th width="360">Description</th>
    <th width="60">Stock</th>
    <th width="60">Ticks</th>
    <th width="70">Order</th>
</tr>
<?php

/*
 * $q = "SELECT uc.id, uc.name, uc.description, uc.metal, uc.crystal, uc.eonium, " .
 *    "sum(units.num) as stock, uc.build_ticks " .
 *    "FROM unit_class AS uc, rc, fleet, units " .
 *    "WHERE rc.planet_id='$Planetid' AND rc.status=3 AND uc.rc_id=rc.rc_id " .
 *    "AND fleet.planet_id='$Planetid' AND units.id=fleet.fleet_id AND units.unit_id=uc.id ".
 *    "GROUP BY uc.id";
 */

$q = "SELECT uc.id, uc.name, uc.description, uc.metal, uc.crystal, uc.eonium, ".
     "uc.build_ticks FROM unit_class AS uc, rc ".
     "WHERE rc.planet_id='$Planetid' AND rc.status=3 AND uc.rc_id=rc.rc_id AND uc.class!=5 ";

$result = mysqli_query ($db, $q );
if ($result && mysqli_num_rows($result) > 0) {

  while ($myunit = mysqli_fetch_row($result)) {

    $nr = mysqli_query ($db, "SELECT sum(units.num) FROM fleet, units WHERE ".
		       "fleet.planet_id='$Planetid' AND units.id=fleet.fleet_id " .
		       "AND units.unit_id='$myunit[0]'" );
    $stock = mysqli_fetch_row ($nr);
    if ( !$stock[0]) $stock[0] = 0;

    print_unit_row ($myunit, $stock[0]);
  }
}
?>

<tr>
  <td colspan="5" align="center">
    <input type=submit value="  Order  " name="submit">&nbsp;&nbsp;&nbsp;<input type=reset value="  Reset  "></td>
</tr>

</table>
</form>

<br>
<table border="1" width="650">
<tr><th colspan="25" class="a">Current Production</th></tr>
<tr><td width="150"></td>
<?php 
  for ($i=1; $i<=24; $i++) {
     echo "<td width=\"20\">$i</td>"; 
  }
?>
</tr>

<?php

$q = "SELECT uc.id, uc.name, uc.build_ticks FROM unit_class AS uc, rc ".
     "WHERE rc.planet_id='$Planetid' AND rc.status=3 AND uc.rc_id=rc.rc_id AND uc.class!=5 ";

$qq = "SELECT unit_id, sum(num), build_ticks FROM unit_build WHERE planet_id='$Planetid' ".
      "AND build_ticks!=0 GROUP BY unit_id, build_ticks";

$result = mysqli_query ($db, $q );
if (mysqli_num_rows($result) > 0) {

  $prod_res = mysqli_query ($db, $qq );
  $mybuild = mysqli_fetch_row($prod_res);

  while ($myunit = mysqli_fetch_row($result)) {
    /* name of it */
    echo "<tr><td>$myunit[1]</td>";

    if ($mybuild && $mybuild[0] == $myunit[0]) {

      for ($i=1; $i<=$myunit[2]; $i++) {
	if ($i == $mybuild[2] && $mybuild && $mybuild[0] == $myunit[0]) {
	  /* in bau */
	  echo "<td>$mybuild[1]</td>";
	  $mybuild = mysqli_fetch_row($prod_res);
	} else {
	  echo "<td>&nbsp;</td>";
	}
      }
    } else {
      /* momentan keine schiffe des typs in bau */
      for ($i=1; $i<=$myunit[2]; $i++) {
	echo "<td>&nbsp;</td>";
      }
    }
    echo "</tr>\n";
  }
}

?>
<tr>
  <td colspan="25" align="center" class="tdlink">
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>">  Reload  </a></td>
</tr>
</table>
</center>

<?php
require "footer.php";
?>
