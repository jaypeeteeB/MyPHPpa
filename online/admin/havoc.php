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

require_once "admhead.php";
require_once "admform.php";
require_once "../logging.php";

?>

<b>Havoc</b>
<br>

<?php
if (file_exists('/tmp/ticker.run')) {
  echo "Ticker still running!<br>";
  die;
} else {

$res = mysqli_query($db, "SELECT leader, planetname, x, y, z, score,".
        "metalroids+crystalroids+eoniumroids+uniniroids ".
        "FROM planet WHERE mode!=0 AND mode!=4 ".
	"ORDER BY score DESC LIMIT 1" );

$high = 0;

if ($res && mysqli_num_rows($res)>0) {
  $row = mysqli_fetch_row($res);
//  $rnd = substr($round,-2,2);
//  $q = "INSERT INTO highscore set round=$rnd,leader='$row[0]',".
//       "planetname='$row[1]',coords='$row[2]:$row[3]:$row[4]',score=$row[5],".
//       "roids=$row[6],date=now()";
//  echo "[$q]<br>";
//  mysqli_query ($db, $q);
  $high = $row[5];
} else {
  echo "Failed";
  die;
}

//$res = mysqli_query($db, "SELECT x, y, SUM(score) AS sc, SUM(metalroids + ".
//	 	   "crystalroids + eoniumroids + uniniroids) " .
//		   "FROM planet WHERE mode != 0 GROUP by x, y ".
//		   "ORDER BY sc DESC LIMIT 1" );
//if ($res && mysqli_num_rows($res)>0) {
//  $row = mysqli_fetch_row($res);
//  $re = mysqli_query($db, "SELECT name FROM galaxy ".
//	  	    "WHERE x='$row[0]' AND y='$row[1]'" );
//  $ro = mysqli_fetch_row($re);
//  $q = "INSERT INTO highscore_gal set round=$rnd,galname='$ro[0]',".
//       "coords='$row[0]:$row[1]',score=$row[2],roids=$row[3],date=now()";
//  echo "[$q]<br>";
//  mysqli_query ($db, $q);
//}

mysqli_query($db, "UPDATE planet SET mode=1 WHERE mode=4 OR mode=0" );
echo "Removed vacation<br>";

$q = "UPDATE planet set metal=metal+30000000000".
	"+10*($high-score),".
	"crystal=crystal+30000000000,".
	"eonium=eonium+15000000000, ".
	"metalroids=metalroids+1500,".
	"crystalroids=crystalroids+800,".
	"eoniumroids=eoniumroids+150 ".
        "WHERE mode!=0";

echo "[$q]<br>";
mysqli_query ($db, $q);
echo "Updated score.<br>";
}

require_once "../footer.php";
?>
