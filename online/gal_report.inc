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

function print_gal_row ($row, $x, $y, $scans, $tag="") {
  global $myrow, $Planetid;

  $str = "<a href=\"javascript:popupWindow('New_Message',".
     "'nsend_message.php?send_to=$row[5]',340,700)\">$row[2]</a>";

  switch ( $row[6] & 0x0F ) {
  case 0: /* banned */
    $str = "<strike>$row[2]</strike>";
    $row[1] = "<strike>$row[1]</strike>"; break;
  case 1: /* not logged in */
    break;
  case 2: /* online */
    if (($myrow["x"] == $x && $myrow["y"] == $y) || $Planetid <= 2)
      $str .= "&nbsp;*";
  break;
  case 3: /* sleep */
    $str = "(&nbsp;$str&nbsp;)";
  break;
  case 4: /* vacation */
    $str = "{&nbsp;$str&nbsp;}";
  break;
  case 5: /* vacation */
    $str = "{&nbsp;$str&nbsp;}";
  break;
  }
  if ($row[6] & 0xF0) {
    /* protection */
    $str = "[&nbsp;$str&nbsp;]";
  }

  if ($scans != "")
    $sc = preg_replace ("/%s/", $row[0], $scans);
  else
    $sc = "";
 
  if ($row[7] || $row[9]) {
    if ($row[7])
      $leader = "<span class=\"gc\">$row[1]</span>";
    else
      $leader = "<span class=\"moc\">$row[1]</span>";
  } else {
    $leader = $row[1];
  }

  echo "<tr>\n";


  if ($row[8] != 0 && $row[8] == $myrow["alliance_id"] && $tag !="") {
     echo "<td><span class=\"red\"><b>$tag</b></span></td>\n";
  } else {
    if ($Planetid <= 2) {
      if ($row[8] != 0) {
        $t =  get_alliance_tag($row[8]);
        echo "<td><span class=\"red\"><b>$t</b></span></td>\n";
      } else {
        echo "<td style=\"visibility:hidden\"></td>\n";
      }
    } else {
      echo "<td style=\"visibility:hidden\"></td>\n";
    }
  }  

  echo "<td>$row[0]</td>\n".
    "<td>$str</td>\n".
    "<td>$leader</td>\n".
    "<td align=\"right\">".pval($row[3])."</td>\n".
    "<td align=\"right\">$row[4]</td>\n".
    "$sc</tr>\n";
}

function get_alliance_tag($aid) {
  global $db, $myrow;

  if ($aid == 0)
    return "";

  $res = mysqli_query($db, "SELECT tag FROM alliance WHERE id='$aid'");
  if ($res && mysqli_num_rows($res)>0) {
    $row = mysqli_fetch_row($res);
    return "$row[0]";
  } else {
    return "";
 }
}

function get_scan_data ($x, $y) {
  global $db, $Planetid, $mysettings, $override_settings;
  
  $val = "";
  $q = "SELECT scan.wave_id, scan_class.name FROM scan, scan_class ".
       "WHERE scan.planet_id='$Planetid' AND scan.wave_id = scan_class.id ".
       "AND scan.wave_id!=1 AND scan.wave_id!=3 AND ".
       "scan.wave_id!=6 AND scan.num>0";

  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) > 0) {
    while ($row=mysqli_fetch_row ($result)) {
      if ($row[0] > 1 && $row[0] != 6 && $row[0] != 3 ) {
	$n = $row[1][0];
        if ( $mysettings & 64 && ! $override_settings) {
          $val .= "<td><a href=\"javascript:popupScrollWindow('ScanWindow',".
            "'scan_window.php?scan=$row[0]&number=1&x=$x&y=$y&z=%s',".
            "340,720)\">$n</a></td>";
        } else {
	  $val .= "<td><a href=\"waves.php?scan=$row[0]".
	    "&number=1&x=$x&y=$y&z=%s\">$n</a></td>";
        }
      }
    }
  }
  return $val;
}

function print_gal_report ($x, $y) {

  global $db, $Planetid, $myrow;

  $result = mysqli_query ($db, "SELECT z, leader, planetname, score, ".
			 "metalroids+crystalroids+eoniumroids+uniniroids ".
			 "AS roids, planet.id, mode, planet.id=gc, alliance_id, ".
                         "planet.id=moc ".
			 "FROM planet, galaxy ".
			 "WHERE planet.x='$x' AND planet.y='$y' AND ".
			 "planet.x=galaxy.x AND galaxy.y=planet.y ".
			 "ORDER BY z ASC" );

  if (mysqli_num_rows($result) == 0) {
    /* empty,invalid Sector */
    echo "<tr><td style=\"visibility:hidden\"></td><td colspan=\"5\" " . 
      "align=\"center\"><span class=\"red\">".
      "The selected Sector is empty</span></td></tr>\n";
  } else {
    $scans = get_scan_data ($x, $y);
    if (($myrow["status"] & 2)==0)
      $tag = get_alliance_tag($myrow["alliance_id"]);
    else
      $tag = "";
    while ($mygal=mysqli_fetch_row ($result)) {
      print_gal_row ($mygal, $x, $y, $scans, $tag);
    }
  }
}

?>
