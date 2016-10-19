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

function sector_scan ($tid) {
  global $db;
  $data = "";

  $q = "SELECT planetname, leader, score, metal, crystal, eonium, ".
     "constructions, research, metalroids, crystalroids, eoniumroids,".
     "uniniroids, x, y, z, mode FROM planet WHERE id='$tid'";
  $qp = "SELECT sum(pds.num) FROM pds  WHERE pds.planet_id='$tid'";
  $qf = "SELECT sum(u.num) FROM fleet AS f, units AS u  ".
     "WHERE f.planet_id='$tid'  AND u.id=f.fleet_id";
  $qr = "SELECT COUNT(*),rc_class.type FROM rc,rc_class ".
     "WHERE planet_id='$tid' AND rc.status=3 AND rc_class.id=rc.rc_id ".
     "GROUP BY rc_class.type";

  $pds = 0;
  $res = mysqli_query ($db, $qp );
  if ( $res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_row($res);
    if (!$row || !$row[0]) $pds=0;
    else $pds = $row[0];
  }

  $units = 0;
  $res = mysqli_query ($db, $qf );
  if ( $res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_row($res);
    if (!$row || !$row[0]) $units=0;
    else $units = $row[0];
  }

  $research=0;
  $construction=0;
  $res = mysqli_query ($db, $qr );
  if ( $res && mysqli_num_rows($res) > 0) {
    while (($row = mysqli_fetch_row($res))) {
      if ($row) {
        if($row[1]==1) $construction=$row[0];
        if($row[1]==0) $research=$row[0];
      }
    }
  }

  $res = mysqli_query ($db, $q );
  if ( $res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_array($res);
    
    // get date 
    $gdate = date("d/m/y H:i:s");

    $data = "<table class=sc>\n".
      "<tr><th colspan=\"5\">".
      "Sector Scan Report on ".
      "$row[planetname] ($row[x]:$row[y]:$row[z]) $gdate";

    switch ($row["mode"] & 0x0F) {
    case 0: $data .= " (banned)"; break;
    case 1: $data .= " (offline)"; break;
    case 2: $data .= " (online)"; break;
    case 3: $data .= " (sleeping)"; break;
    case 4: $data .= " (vacation)"; break;
    case 5: $data .= " (vacation)"; break;
    }

    $data .= " </th></tr>\n".

      "<tr><td class=scl>Planet Name</td>".
      "<td class=scr> $row[planetname]</td>".
      "<td>&nbsp;</td>".
      "<td class=scl>Leader</td>".
      "<td class=scr> $row[leader]</td></tr>\n".

      "<tr><td>Score</td>".
      "<td class=scr> $row[score]</td>".
      "<td>&nbsp;</td>".
      "<td>Metal</td>".
      "<td class=scr> $row[metal]</td></tr>\n".

      "<tr><td>Crystal</td>".
      "<td class=scr> $row[crystal]</td>".
      "<td>&nbsp;</td>".
      "<td>Eonium</td>".
      "<td class=scr> $row[eonium]</td></tr>\n".

      "<tr><td>Constructions</td>".
      "<td class=scr> $construction</td>".
      "<td>&nbsp;</td>".
      "<td>Research</td>".
      "<td class=scr> $research</td></tr>\n".

      "<tr><td>Metal Asteroids</td>".
      "<td class=scr> $row[metalroids]</td>".
      "<td>&nbsp;</td>".
      "<td>Crystal Asteroids</td>".
      "<td class=scr> $row[crystalroids]</td></tr>\n".

      "<tr><td>Eonium Asteroids</td>".
      "<td class=scr> $row[eoniumroids]</td>".
      "<td>&nbsp;</td>".
      "<td>Unknown Asteroids</td>".
      "<td class=scr> $row[uniniroids]</td></tr>\n".

      "<tr><td>Fleet Size</td>".
      "<td class=scr> $units</td>".
      "<td>&nbsp;</td>".
      "<td>PDS Size</td>".
      "<td class=scr> $pds</td></tr>\n</table>";

  }
  return $data;
}

function unit_scan ($tid) {
  global $db;
  $data = "";

  $target = get_coord_name ($tid);

  $q = "SELECT uc.name, sum(units.num) as total ".
     "FROM fleet, units, unit_class AS uc ".
     "WHERE fleet.planet_id = '$tid' AND uc.class!=3 ".
     "AND fleet.fleet_id = units.id AND units.unit_id = uc.id ".
     "GROUP BY units.unit_id";

  $result = mysqli_query ($db, $q );

  $gdate = date("d/m/y H:i:s");

  $data = "<table class=sc>".
    "<tr><th colspan=\"5\">Unit Scan Report on $target[planetname] ".
    "($target[x]:$target[y]:$target[z]) $gdate&nbsp;</th></tr>\n";

  if ($result && mysqli_num_rows($result) > 0) {

    $row_counter = 0;
    while ($row = mysqli_fetch_row ($result)) {

      if ( ($row_counter % 2) == 0)  $data .= "<tr>";
      $data .= "<td class=scl>$row[0]</td><td class=scr> $row[1]</td>";
      if ( ($row_counter % 2) == 1)  $data .= "</tr>\n";
      else $data .= "<td>&nbsp;</td>";
      $row_counter++;
    }
    if ( ($row_counter % 2) == 1) {
      $data .= "<td class=scl></td><td class=scr></td></tr>\n";
    }
  } else {
    $data .= "<tr><td colspan=\"5\">No Units found</td></tr>";
  }
  $data .= "</table>";
  return $data;
}

function pds_scan ($tid) {
  global $db;
  $data = "";

  $target = get_coord_name ($tid);
  
  $q = "SELECT unit_class.name, pds.num FROM pds,unit_class ".
     "WHERE planet_id='$tid' AND pds.pds_id=unit_class.id";

  $result = mysqli_query ($db, $q );

  $gdate = date("d/m/y H:i:s");

  $data = "<table class=sc>".
    "<tr><th colspan=5>PDS Scan Report on $target[planetname] ".
    "($target[x]:$target[y]:$target[z]) $gdate</th></tr>\n";
  
  if ($result && mysqli_num_rows($result) > 0) {

    $row_counter = 0;
    while ($row = mysqli_fetch_row ($result)) {

      if ( ($row_counter % 2) == 0) $data .= "<tr>";
      $data .= "<td class=scl>$row[0]</td><td class=scr> $row[1]</td>";
      if ( ($row_counter % 2) == 1) $data .= "</tr>\n";
      else $data .= "<td>&nbsp;</td>";
      $row_counter++;
    }
    if ( ($row_counter % 2) == 1) {
      $data .= "<td class=scl></td><td class=scr></td></tr>\n";
    }
  } else {
    $data .= "<tr><td colspan=\"5\">No PDS found</td></tr>";
  }
  $data .= "</table>";
  return $data;
}

function news_scan ($tid) {
  global $db, $mysettings;
  $data = "";

  $target = get_coord_name ($tid);

  $q = "SELECT date_format(date,'%D %b %H:%i') as date,tick,text,date as d ".
	"FROM news WHERE planet_id='$tid' and type < 11 ".
        "ORDER BY d DESC";

  if ($mysettings & 8) {
    $q .= " LIMIT 20";
  }

  $result = mysqli_query ($db, $q );

  $gdate = date("d/m/y H:i:s");
  
  $data = "<table class=sc><tr>".
    "<th colspan=\"2\">News Scan Report on $target[planetname] ".
    "($target[x]:$target[y]:$target[z]) $gdate</th></tr>\n";

  if ($result && mysqli_num_rows($result) > 0) {

    $data .= "<tr><td class=screc>Received</td>".
      "<td class=scmsg>Message</td></tr>";
    
    while ($row = mysqli_fetch_row($result)) {
      $tick="";
      if ($row[1] != 0) $tick = "<br>MyT: $row[1]";
      $data .= "<tr><td class=screc>$row[0]$tick</td>".
	"<td class=scmsg>$row[2]</td></tr>";
    }

  } else {
    $data .= "<tr><td class=screc>&nbsp;</td>".
      "<td class=scmsg>No News</td></tr>";
  }
  $data .= "</table>";
  return $data;
}

function mil_scan ($tid) {
  global $db;
  $data = "";
  $number_of_fleets = 4;

  $target = get_coord_name ($tid);

  // query shipnames
  $q = "SELECT id, name FROM unit_class WHERE class !=5 ORDER BY id";
  $res_ship = mysqli_query ($db, $q );

  while ($row = mysqli_fetch_row($res_ship)) {
    $ship_list[$row[0]]["name"] = $row[1];
    for ($i=0; $i<$number_of_fleets+1; $i++) {
      $ship_list[$row[0]]["$i"] = 0;
    }
  }
  
  // query units number
  $q = "SELECT units.unit_id, fleet.num, SUM(units.num) AS total ".
     "FROM fleet, units WHERE fleet.planet_id='$tid' ".
     "AND fleet.fleet_id=units.id GROUP BY units.unit_id,fleet.num";
  $res_units = mysqli_query ($db, $q );

  if ($res_units && mysqli_num_rows($res_units) > 0) {
    while ($row = mysqli_fetch_row($res_units)) {
      $ship_list[$row[0]]["$row[1]"] = $row[2];
    }
  }

  $gdate = date("d/m/y H:i:s");
  
  $data = "<table class=scm>".
    "<tr><th colspan=\"".($number_of_fleets+2)."\">".
    "Military Scan Report on $target[planetname] ".
    "($target[x]:$target[y]:$target[z]) $gdate</th></tr>\n".
    "<tr><td>&nbsp;</td><td>Base Fleet</td>";

  for ($i=1; $i<$number_of_fleets; $i++) {
    $data .= "<td>Fleet $i</td>";
  }
  $data .= "<td>Missiles</td></tr>\n";
  
  for ($j=1; $j<14; $j++) {
    $data .= "<tr><td>". $ship_list[$j]["name"] ."</td>";
    for ($i=0; $i<$number_of_fleets; $i++) {
      $data .= "<td> ". $ship_list[$j]["$i"] ."</td>";
    }
    $data .= "</td>\n";
  }
  // argh ..
  $data .= "<tr><td>". $ship_list["26"]["name"] ."</td>".
    "<td> ". $ship_list[26]["0"] ."</td>".
    "<td> 0</td><td> 0</td><td> 0</td>".
    "<td> ". $ship_list[26]["4"] ."</td></tr>\n";
  

  // query fleet status
  $q = "SELECT num, type, ticks, target_id FROM fleet ".
     "WHERE planet_id='$tid' ORDER BY num";
  $res_fleet = mysqli_query ($db, $q );

  if ($res_fleet && mysqli_num_rows($res_fleet) > 0) {
    $data .= "<tr><td>&nbsp;</td>";
    while ($row = mysqli_fetch_row($res_fleet)) {
      if ($row[1]) {
	$rid = get_coord ($row[3]);
	if ($row[1]>9)
	  $data .= "<td> ATT ";
	else
	  $data .= "<td> DEF ";
	if ($row[2]>0) {
	  $data .= "ETA $row[2] [$rid[0]:$rid[1]:$rid[2]]</td>";
	} else {
	  if ($row[1]>9)
	    $data .= $row[1]-10 . " h [$rid[0]:$rid[1]:$rid[2]]</td>";
	  else
	    $data .= $row[1] . " h [$rid[0]:$rid[1]:$rid[2]]</td>";
	}
      } else {
	// returning or home
	if ($row[2]>0) {
	  $data .= "<td> RET ETA $row[2]</td>";
	} else {
	  $data .= "<td> DEF</td>";
	}
      }
    }
    $data .= "</tr>\n";
  }

  $data .= "</table>";
  return $data;
}

function scan_target ($sid, $x, $y, $z, $num) {
  global $db, $Planetid, $myrow, $msg, $mytick;
  $ret_val = 0; 
 
  $target_id = get_id ($x, $y, $z);

  if (!$target_id) {
    $msg = "Target ($x, $y, $z) invalid<br>\n";
    return;
  }
  
  // preset
  $amps  = 0;
  $scans = 0;
  $blocks = 0;
  $blocked = 0;
  $noticed = 0;
  $target_roids = 1;
  $num = (int) $num;

  $q = "SELECT scan.num,(planet.metalroids+planet.crystalroids+planet.eoniumroids+planet.uniniroids) ".
     "FROM scan,planet WHERE scan.wave_id=6 AND scan.planet_id='$target_id' ".
     "AND scan.planet_id = planet.id";
  $result = mysqli_query($db, $q );

  if ($result && mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_row($result);
    $blocks = $row[0];
    $target_roids = $row[1];
  }

  $q = "SELECT num,wave_id FROM scan ".
     "WHERE (wave_id=1 OR wave_id='$sid') AND planet_id='$Planetid'";
  $result = mysqli_query($db, $q );

  if ($result && mysqli_num_rows($result)>0) {
    while ($row = mysqli_fetch_row($result)) {
      if ($row[1] == 1) {
	$amps =$row[0]; 
      } else {
	$scans=$row[0];
      }
    }

    // echo "Using $amps, max $scans ($num)<br>";

    if ($scans < $num) $num = $scans;
    $scans = $num; // save for later use
    if ($num < 1) return;

    $reached = 0;

    while ($num && !$reached) {
      $total_roids = $myrow["metalroids"]+$myrow["crystalroids"]+$myrow["eoniumroids"]+
	 $myrow["uniniroids"];

      $chance = 30. * (1 + $amps/$total_roids - $blocks/$target_roids);
      
      $chance = 1000 * $chance;
      if ( $chance >= 99990) $chance =  99990;
      if ( $chance <= 10) $chance = 10;
      $num--;

      mt_srand ((double) microtime() * 1000000);
      $rval = mt_rand(0, 100000);

      if ($rval < $chance ) {
	$reached++;
	// if ( $rval*2 > $chance ) {
	//  $noticed++;
	// }
        if ( ($rval + $sid * 5000) > $chance ) {
          $noticed++;
        }
      } else {
	$blocked++;
      }
    }

    $used_scans = $scans - $num;
    $q = "UPDATE scan SET num=num-'$used_scans' WHERE planet_id='$Planetid' ".
       "AND wave_id='$sid'";
    mysqli_query($db, $q );
    
    switch ($sid) {
      case 2: $sname="sector scan"; break;
      case 4: $sname="unit scan";break;
      case 5: $sname="pds scan";break;
      case 7: $sname="news scan";break;
      case 8: $sname="military scan";break;
    }
    if ($reached) {
      $ret_val = 1;
      $data = "";

      switch ($sid) {
      case 2: $data = sector_scan ($target_id); break;
      case 4: $data = unit_scan ($target_id); break;
      case 5: $data = pds_scan ($target_id); break;
      case 7: 
	if ($target_id==1) { 
	  $reached = 0;
	  $blocked = 1;
	} else { 
	  $data = news_scan ($target_id); 
	} 
	break;
      case 8: $data = mil_scan ($target_id); break;
      }
      if (ISSET($data) && $data != "") {
        if ($sid != 7) {
	  $qset = " journal SET planet_id='$Planetid',target_id='$target_id',".
            "date=NOW(),tick='$mytick',type='$sid',data='$data'"; // ,hidden=1

          $res = mysqli_query($db, "UPDATE $qset WHERE planet_id='$Planetid' ".
            "AND target_id='$target_id' AND type='$sid'" );

          if (!$res || mysqli_affected_rows($db)!=1) {
            $res = mysqli_query($db, "INSERT INTO $qset,hidden=1" );
          }
        } 
        echo "<div class=cent>$data</div>";
      }
    }
    if ($blocked) {
      $noticed = 0;
      $msg = "Scan Blocked!";
      $text = "Blocked $sname from ".
         coord_ref($myrow['x'],$myrow['y'], 
                   "$myrow[planetname] ($myrow[x]:$myrow[y]:$myrow[z])");
      insert_into_news ($target_id, 5, $text);
      if ($blocks) {
	mysqli_query($db, "UPDATE scan SET num=num-1 ".
                    "WHERE wave_id=6 AND planet_id='$target_id'");
      }
    }
    if ($noticed) {
      $msg = "Scan Noticed!";
      $text = "Noticed $sname from ".
         coord_ref($myrow['x'],$myrow['y'], 
                   "$myrow[planetname] ($myrow[x]:$myrow[y]:$myrow[z])");
      insert_into_news ($target_id, 5, $text);
    }
  }

  return $ret_val;
}

?>
