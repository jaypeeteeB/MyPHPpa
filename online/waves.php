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
require "planet_util.inc";
require "news_util.php";

require "scan_util_2.inc";
require "logging.php";

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

function print_scan_row ($row, $stock) {

  $cost = print_cost ($row[3], $row[4], $row[5]);

  echo "<tr><td>$row[1]</td><td>$row[2]<br>$cost" .
    "</td><td align=\"right\">$stock</td>".
    "<td align=\"right\">$row[6] ticks</td>" .
    "<td><input type=\"text\" name=\"scan_$row[0]\" size=\"8\"></td></tr>\n"; 
}

function prod_scan ($scan, $num) {
  global $myrow; /* resources */
  global $Planetid, $db;

  $num = (int) $num;

  if (!$num || $num < 1) return;
  $res = mysqli_query ($db, "SELECT metal, crystal, eonium, build_ticks ".
		      "FROM scan_class AS sc, rc WHERE sc.id='$scan' ".
		      "AND rc.rc_id=sc.rc_id AND rc.status=3 ".
		      "AND rc.planet_id='$Planetid'" );

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

    if ($num >0) {
      $cm = $price[0] * $num;
      $cc = $price[1] * $num;
      $ce = $price[2] * $num;
      $myrow["metal"]   -= $cm;
      $myrow["crystal"] -= $cc;
      $myrow["eonium"]  -= $ce;

      $result = mysqli_query($db, "UPDATE planet SET metal='$myrow[metal]',".
		            "crystal='$myrow[crystal]',".
			    "eonium='$myrow[eonium]' ".
			    "WHERE id='$Planetid' ".
                            "AND metal>='$cm' AND crystal>='$cc' AND eonium>='$ce'" );
      if (mysqli_affected_rows($db)==1) {
        $q = "INSERT DELAYED INTO scan_build SET planet_id='$Planetid',scan_id='$scan',".
  	     "build_ticks=$price[3], num=$num";
        $res = mysqli_query ($db, $q );
      }
    }
  }
}

function scan_roids ($num) {
  global $db, $Planetid, $myrow, $msg;

  $q = "SELECT num,wave_id FROM scan ".
     "WHERE (wave_id=1 OR wave_id=3) AND planet_id='$Planetid'";

  $result = mysqli_query($db, $q );

  // preset
  $amps  = 0;
  $scans = 0;

  $num = (int) $num;

  if ($result && mysqli_num_rows($result)>0) {
    while ($row = mysqli_fetch_row($result)) {
      if ($row[1] == 1) $amps =$row[0]; 
      if ($row[1] == 3) $scans=$row[0]; 
    }

    if ($scans < $num) $num = $scans;
    $scans = $num; // save for later use
    if ($num < 1) return;
    $total_found = 0;

    while ($num) {
      $total_roids = $myrow["metalroids"]+$myrow["crystalroids"]
         +$myrow["eoniumroids"]+$myrow["uniniroids"];

      if ($total_roids > 200) {
	$chance = 30. * ($amps/($total_roids*2));
      } else if ($total_roids > 0) {
	$chance = 30. * (1 + $amps/($total_roids*3));
      } else {
	$chance = 31. * (1 + $amps/2);
      }
      
      $chance = 1000 * $chance;
      if ( $chance >= 99990) $chance =  99990;
      if ( $chance < 10) $chance =  10;
      $num--;

      mt_srand ((double) microtime() * 1000000);
      $rval = mt_rand(0, 100000);

      // echo "Found [$rval, $chance] ($amps Amps, $total_roids)<br>\n";
      do_log_me( 8, 1, "Roid scan found [$rval, $chance] ($amps Amps, $total_roids)");

      if ($rval < $chance ) {
	$total_found++;
	$myrow["uniniroids"]++;
      }
    }

    $q = "UPDATE scan SET num=num-'$scans' ".
       "WHERE planet_id='$Planetid' AND wave_id=3";
    mysqli_query($db, $q );

    if ( $total_found ) {
      $q = "UPDATE planet SET uniniroids=uniniroids+'$total_found' ".
	 "WHERE id='$Planetid'";
      mysqli_query($db, $q );
    }
    $msg = "Found a Total of $total_found Asteroids";
  }

}

$msg = "";

if (ISSET($_POST["submit"])) {
  /* aeusserst uncooles handling */
  if (ISSET($_POST["scan_1"])) prod_scan (1, $_POST["scan_1"]);
  if (ISSET($_POST["scan_2"])) prod_scan (2, $_POST["scan_2"]);
  if (ISSET($_POST["scan_3"])) prod_scan (3, $_POST["scan_3"]);
  if (ISSET($_POST["scan_4"])) prod_scan (4, $_POST["scan_4"]);
  if (ISSET($_POST["scan_5"])) prod_scan (5, $_POST["scan_5"]);
  if (ISSET($_POST["scan_6"])) prod_scan (6, $_POST["scan_6"]);
  if (ISSET($_POST["scan_7"])) prod_scan (7, $_POST["scan_7"]);
  if (ISSET($_POST["scan_8"])) prod_scan (8, $_POST["scan_8"]);
}

/* top table is written now */
top_header($myrow);

if (ISSET($_REQUEST["search"]) && ISSET($_REQUEST["roid"])) {
  $roid = (int) $_REQUEST["roid"];
  scan_roids ($roid);
}

if (ISSET($_REQUEST["scan"]) && ISSET($_REQUEST["number"]) && 
    ISSET($_REQUEST["x"]) && ISSET($_REQUEST["y"]) && ISSET($_REQUEST["z"])) {
  $scan= (int) $_REQUEST["scan"];
  $x = (int) $_REQUEST["x"];
  $y = (int) $_REQUEST["y"];
  $z = (int) $_REQUEST["z"];
  $number = (int) $_REQUEST["number"];
  echo "<br>\n";
  $number = ($number>1000?1000:$number);
  $reach = scan_target ($scan, $x, $y, $z, $number);

  if ($reach && $scan != 7) 
    $save_link =
      "<a href=\"".$_SERVER['PHP_SELF']."?save=$scan&x=$x&y=$y&z=$z\">Save scan</a>";

  if (!ISSET($save_link)) $save_link = "";

  echo <<<EOF
<center>
<table border=0 width=650>
<tr><td align="left">$save_link</td><td align="right">
<a href="galaxy.php?submit=1&x=$x&y=$y">To galaxy $x:$y</a></td></tr>
</table>
</center>
EOF;

} else if (ISSET($_REQUEST["save"]) && 
    ISSET($_REQUEST["x"]) && ISSET($_REQUEST["y"]) && ISSET($_REQUEST["z"]) ) {
  $save= (int) $_REQUEST["save"];
  $x = (int) $_REQUEST["x"];
  $y = (int) $_REQUEST["y"];
  $z = (int) $_REQUEST["z"];

  $tid = get_id ($x, $y, $z);

  if ($tid) {

    $q = "SELECT id,data FROM journal WHERE planet_id='$Planetid' ".
       "AND target_id='$tid' AND type='$save'"; // AND hidden=1
    $result = mysqli_query ($db, $q );

    if ($result && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_row($result);
      echo "<br>\n$row[1]";
      mysqli_query ($db, "UPDATE journal SET hidden=0 WHERE id=$row[0]" );

      echo <<<EOF
<center>
<table border=0 width=650><tr><td align="right">
<a href="galaxy.php?submit=1&x=$x&y=$y">To galaxy $x:$y</a></td></tr>
</table>
</center>
EOF;
    }
  }
}

titlebox("Waves", $msg);
?>

<center>
<?php
$q = "SELECT sc.id, sc.name FROM scan, scan_class AS sc ".
     "WHERE scan.planet_id='$Planetid' AND scan.wave_id = sc.id ".
     "AND scan.num > 0 AND sc.id > 1 AND sc.id != 6 ORDER BY sc.id ASC";

$result = mysqli_query ($db, $q );
if ($result && mysqli_num_rows($result) > 0) {

  // preset
  $roids_scan_found=0;
  $opt = "";

  // have some scans available
  while ($row = mysqli_fetch_row($result)) {
    if ($row[0] == 3) {
      /* roid scan */
      $roids_scan_found=1;
    } else {
      /* normal scans */
      if (ISSET($scan) && $scan == $row[0])
	$opt .= "<option selected value=\"$row[0]\">$row[1]</option>";
      else
	$opt .= "<option value=\"$row[0]\">$row[1]</option>";
    }
  }

  echo "<table border=\"1\" width=\"650\"><tr>".
    "<th colspan=\"4\" class=\"a\">Launch Wave Scans</th></tr>".
    "<tr><th width=\"175\">Wave Type</th><th width=\"175\">Target</th>".
    "<th width=\"150\">Number</th><th width=\"150\">Execute</th></tr>\n";

  if (!ISSET($_REQUEST["number"]) || $_REQUEST["number"] == "" || $_REQUEST["number"] == 0) $_REQUEST["number"] = 1;
   if ($opt != "") {
     $xx = $yy = $zz = "";
     $number = $_REQUEST["number"];
     if (ISSET($_REQUEST["x"])) $xx = $_REQUEST["x"];
     if (ISSET($_REQUEST["y"])) $yy = $_REQUEST["y"];
     if (ISSET($_REQUEST["z"])) $zz = $_REQUEST["z"];
     echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">".
       "<tr><td align=\"center\"><select name=\"scan\">$opt</select></td>".
       "<td align=\"center\">".
       "<input type=\"text\" name=\"x\" size=\"3\" maxlength=\"3\" value=\"$xx\">&nbsp".
       "<input type=\"text\" name=\"y\" size=\"2\" maxlength=\"2\" value=\"$yy\">&nbsp".
       "<input type=\"text\" name=\"z\" size=\"2\" maxlength=\"2\" value=\"$zz\"></td>".
       "<td align=\"center\">".
       "<input type=\"text\" name=\"number\" size=\"8\" maxlength=\"4\" value=\"$number\"></td>".
       "<td align=\"center\">".
       "<input type=submit value=\"  Launch  \" name=\"launch\"></td>".
       "</tr></form>\n";
   }

   if ($roids_scan_found) {
     echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">".
       "<tr><td align=\"center\">Asteroid Scan</td><td>&nbsp;</td>".
       "<td align=\"center\">".
       "<input type=\"text\" name=\"roid\" size=\"8\" maxlength=\"6\"></td>".
       "<td align=\"center\">".
       "<input type=submit value=\"  Search  \" name=\"search\"></td>".
     "</tr></form>\n";
   }

  echo "</table>\n<br>\n";
}
?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']?>">
<table border="1" width="650">
<tr><th colspan="5" class="a">Purchase Energy Packs</th></tr>
<tr><th width="110">Wave Type</th>
    <th width="360">Description</th>
    <th width="50">Stock</th>
    <th width="60">Ticks</th>
    <th width="70">Order</th>
</tr>
<?php

$q = "SELECT sc.id, sc.name, sc.description, sc.metal, sc.crystal, sc.eonium, ".
     "sc.build_ticks FROM scan_class AS sc, rc ".
     "WHERE rc.planet_id='$Planetid' AND rc.status=3 AND sc.rc_id=rc.rc_id ";

$result = mysqli_query ($db, $q );
if ($result && mysqli_num_rows($result) > 0) {

  while ($myunit = mysqli_fetch_row($result)) {

    $nr = mysqli_query ($db, "SELECT sum(num) FROM scan WHERE ".
		       "planet_id='$Planetid' AND wave_id='$myunit[0]'" );
    $stock = mysqli_fetch_row ($nr);
    if ( !$stock[0]) $stock[0] = 0;

    print_scan_row ($myunit, $stock[0]);
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
<tr><th colspan="18" class="a">Current Production</th></tr>
<tr><td width="150"></td>
<?php 
  for ($i=1; $i<=16; $i++) {
     echo "<td width=\"20\">$i</td>"; 
  }
?>
<td width="160"></td></tr>

<?php

$q = "SELECT sc.id, sc.name, sc.build_ticks FROM scan_class AS sc, rc ".
     "WHERE rc.planet_id='$Planetid' AND rc.status=3 AND sc.rc_id=rc.rc_id";

$qq = "SELECT scan_id, sum(num), build_ticks FROM scan_build ".
      "WHERE planet_id='$Planetid' ".
      "AND build_ticks!=0 GROUP BY scan_id, build_ticks";

$result = mysqli_query ($db, $q );
if (mysqli_num_rows($result) > 0) {

  $prod_res = mysqli_query ($db, $qq );
  $mybuild = mysqli_fetch_row($prod_res);

  while ($myunit = mysqli_fetch_row($result)) {
    /* the name of it */
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
      /* momentan keine scans des typs in bau */
      for ($i=1; $i<=$myunit[2]; $i++) {
	echo "<td>&nbsp;</td>";
      }
    }
    echo "</tr>\n";
  }
}

?>
<tr>
  <td colspan="18" align="center" class="tdlink">
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>">  Reload  </a></td>
</tr>
</table>
</center>

<?php
require "footer.php";
?>
