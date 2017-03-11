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
require "fleet_util.php";
require "news_util.php";
require "logging.php";

function set_hostile ($id, $dir) {
  global $db, $Planetid;

  $coords = get_coord ($id);

  if ($dir > 0)
    $q = "UPDATE planet SET gal_hostile=gal_hostile+1 ". 
      "WHERE x='$coords[x]' AND y='$coords[y]' ".
      "AND id!='$id'";
  else
    $q = "UPDATE planet SET gal_hostile=gal_hostile-1 ".
      "WHERE x='$coords[x]' AND y='$coords[y]'".
      "AND id!='$id'";

  $result = mysqli_query ($db, $q );

  if ($dir > 0) {
    $q = "UPDATE planet SET has_hostile=has_hostile+1 ". 
      "WHERE id='$id'";
    do_log_me (4,2,"Attacking: [$id]");
    do_log_id ($id,4,3,"Attacker: [$Planetid]");
  } else 
    $q = "UPDATE planet SET has_hostile=has_hostile-1 ".
      "WHERE id='$id'";

  $result = mysqli_query ($db, $q );
}

function set_friendly ($id, $dir) {
  global $db, $Planetid;

  if ($dir > 0) {
    do_log_me (4,4,"Defending: [$id]");
    do_log_id ($id,4,5,"Defender: [$Planetid]");

    $q = "UPDATE planet SET has_friendly=has_friendly+1 ". 
      "WHERE id='$id'";
  } else
    $q = "UPDATE planet SET has_friendly=has_friendly-1 ".
      "WHERE id='$id'";

  $result = mysqli_query ($db, $q );
}

function check_self_attack ($id, $type) {
  global $db, $Planetid;

  $t = ($type<10? ">9" : "<10");

  $q = "SELECT fleet_id FROM fleet WHERE planet_id='$Planetid' ".
       "AND target_id='$id' AND type ".$t;
  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result)>0) 
    return 1;
  else
    return 0;
}

function get_attac_fleet ($id) {
  global $db;

  $q = "SELECT SUM(FLOOR(units.num*(unit_class.metal+unit_class.crystal)/10)) ".
    "AS Total FROM fleet, units, unit_class ".
    "WHERE fleet.target_id='$id' AND fleet.type>10 ".
    "AND fleet.fleet_id=units.id AND units.unit_id=unit_class.id";

  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result)>0) {
    $row = mysqli_fetch_row ($result);
    return $row[0];
  } else
    return 0;
}

function get_num_units ($fleet_id, $unit_id) {
  global $db;

  $res = mysqli_query ($db, "SELECT SUM(num) from units ".
		      "WHERE id=$fleet_id AND unit_id=$unit_id");

  if ($res && mysqli_num_rows($res) >0) {
    $r = mysqli_fetch_row($res);
    return $r[0];
  }
  return 0;
}

function check_valid ($id,$type) {
  global $myrow, $Planetid;
  global $noob_prot, $high_prot, $havoc;

  $mode = get_status($id);

  if ($mode & 0xF0) 
    return "Target is under n00b protection!";

  switch ($mode) {
  case -1: return "Target is invalid!"; break;
  case 0: return "Target is banned!"; break;
  case 3: return "Target is in Sleep protection."; break;
  case 4: return "Target is in Vacation."; break;
  case 5: return "Target is in Vacation."; break;
  default: break;
  }

  if (check_self_attack ($id, $type) == 1) {
    return "You can't attack and defend against yourself!";
  }

  if ($type<=10)
    return "";

  // attacking
  $target_score = get_score($id);
  $fleet_score = get_attac_fleet ($id);

  if ($fleet_score > 0) { 
    do_log_id ($id,4,1,"Group $target_score | me $myrow[score] <- $fleet_score perc=". floor(100*($target_score / $fleet_score)) ."");

    if ($havoc == 0 && $fleet_score > ( 2.5 * $target_score)) {
      return "Due to high traffic space control denies access to that sector.";
    } 
  }

  if ( $high_prot!=0 && ($target_score / $high_prot) > $myrow["score"] ) 
    return " Target is too big - want to kill Your fleet ?";

  if ( $noob_prot!=0 && ($target_score * $noob_prot) < $myrow["score"] ) 
    return " Target is too small - get real enemies!";

  return "";
}

function transfer_ship ($type, $num, $from, $to) {

  global $db, $Planetid;
  global $missile_id;

  $num = (int) $num;
  $num = ($num > 0 ? $num : 0);
  if ($from == $to) return "";
  //if ($type != 255 && $num == 0) return "Empty Order: $type / $num<br>\n";

  if ($from == 255) $from = 0;
  if ($to == 255) $to = 0;

  $result = mysqli_query ($db, "SELECT num, fleet_id FROM fleet where target_id=0 ".
			 "AND ticks=0 AND planet_id='$Planetid' ".
			 "AND (num=$from or num=$to)" );
  if (!$result || mysqli_num_rows($result) != 2) {
    return "Due to military security constraints fleets must reside in home ".
      "sector to transfer ships<br>\n";
  }

  while ($row = mysqli_fetch_row($result)) 
    $fleet[$row[0]] = $row[1];
  
  if ($type == 255) {
    /* all ships */
    $q = "UPDATE units SET id='$fleet[$to]' WHERE id='$fleet[$from]' ".
       "AND unit_id!=$missile_id";

    $result = mysqli_query ($db, $q );
    if (!$result) 
      return "Fleet movement failed<br>\n";
  } else {
    //
    // Hier lock start
    mysqli_query ($db, "LOCK TABLES units WRITE" );

    $q = "SELECT SUM(num) FROM units ".
       "WHERE id='$fleet[$from]' AND unit_id='$type'";

    $result = mysqli_query($db, $q );

    if ($result && mysqli_num_rows($result)==1) {
      $row = mysqli_fetch_row($result);
      if ($row[0] > 0) {

        if ($num == 0) $num = $row[0];

	$q = "DELETE FROM units where id='$fleet[$from]' AND unit_id='$type'";
	$result = mysqli_query($db, $q );

	if ($row[0] > $num) {
	  $rest = $row[0] - $num;
	  $q = "INSERT INTO units SET id='$fleet[$from]',".
	     "num='$rest',unit_id='$type'";
	  $result = mysqli_query($db, $q );
	  $row[0] = $num;
	}
	$q = "INSERT INTO units SET id='$fleet[$to]',".
	   "num='$row[0]',unit_id='$type'";
	$result = mysqli_query($db, $q );
      }
    }
    // Hier lock end
    //
    mysqli_query ($db, "UNLOCK TABLES" );
  }

  return "";
}

function print_target_row($flnum, $x, $y, $z, $pname, $type, $ticks) {
  global $myrow, $ship_in_fleet;

  echo "<tr><td>Fleet $flnum</td>\n<td>";
  if ($type < 10) {
    if ($type == 0 && $ticks) echo "Return";
    else echo "Defend";
  } else {
    echo "Attack";
  }
  echo "</td>\n<td>$pname ($x:$y:$z)";
  if ($ticks != 0) {
    echo "<br>[ETA $ticks ticks] ";
    if ($type==0 || $type==10) {
      echo " Returning";
     } else if ($type < 10) {
      echo " Defend $type ticks";
    } else {
      echo " Attack ". ($type - 10) . " ticks";
    }
  } else {
    if ($type < 10) {
      if ($pname == $myrow["planetname"] && $type == 0) {
	echo " At Home";
      } else {
	echo " Defending ". ($type) ." ticks";
      }
    } else {
      echo " Attacking ". ($type - 10) . " ticks";
    }
  }

  echo "</td><td>\n";

  if ($ship_in_fleet && $ship_in_fleet[$flnum]) {
    echo "<select name=\"fleet_$flnum\"><option value=0>No change</option>".
      "<option value=255>Return</option>\n".
      "<option value=11>Attack 1 tick</option>".
      "<option value=12>Attack 2 tick</option>".
      "<option value=13>Attack 3 tick</option>\n".
      "<option value=1>Defend 1 tick</option>".
      "<option value=2>Defend 2 tick</option>".
      "<option value=3>Defend 3 tick</option>\n".
      "<option value=4>Defend 4 tick</option>".
      "<option value=5>Defend 5 tick</option>".
      "<option value=6>Defend 6 tick</option></select>\n";
    echo "</td><td align=\"center\">\n".
      "<input type=\"text\" name=\"fleet_" . 
      $flnum . "_x\" size=\"3\" maxlength=\"3\">".
      "&nbsp;<input type=\"text\" name=\"fleet_" . 
      $flnum . "_y\" size=\"2\" maxlength=\"2\">".
      "&nbsp;<input type=\"text\" name=\"fleet_" . 
      $flnum . "_z\" size=\"2\" maxlength=\"2\">".
      "</td></tr>\n";
  } else {
    echo "&nbsp;</td><td height=25 align=\"center\">&nbsp;</td></tr>\n";
  }
}

function send_fleet ($flnum, $order, $x, $y, $z) {

  global $db, $Planetid, $myrow, $havoc;
  $msg = "";

  if ($x==$myrow["x"] && $y==$myrow["y"] && $z==$myrow["z"]) {
    return "Target planet banned ;-)";
  }

  $q = "SELECT sum(units.num) FROM units,fleet WHERE fleet.planet_id='$Planetid' ".
       "AND fleet.num='$flnum' AND fleet.fleet_id=units.id";
  $result = mysqli_query($db, $q );
  if ($result  && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_row ($result);
    if ($row[0]<1)
      return "You dont have ships in your fleet"; 
  } else {
    return "No such fleet";
  }

  if ($order == 255) {

    $result = mysqli_query($db, "SELECT target_id,ticks,type,full_eta FROM fleet ".
			  "WHERE planet_id='$Planetid' AND num='$flnum' ".
			  "AND target_id!=0" );

    if ($result && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_row ($result);
    
      if ($row[2]<10) {
	set_friendly($row[0],-1);
        do_log_me (4,6,"Recall Def: [$row[0]]");
        do_log_id ($row[0],4,7,"Recall Friendly: [$Planetid]");
      } else {
	set_hostile($row[0],-1);
        do_log_me (4,8,"Recall Att: [$row[0]]");
        do_log_id ($row[0],4,9,"Recall Hostile: [$Planetid]");
      }
      /* $result = mysqli_query ($db, $q );
      */

      $eta = $row[3] - $row[1];
      $result = mysqli_query($db, "UPDATE fleet set target_id=0,type=0, ".
			    "ticks='$eta',full_eta=0 ".
			    "WHERE planet_id='$Planetid' ".
			    "AND num='$flnum'" );

      send_msg_fleet_recall ($row[0], $eta, $row[2]);
    }

    return $msg;
  } else {
    $result = mysqli_query($db, "SELECT target_id, ticks, full_eta FROM fleet ".
			  "WHERE planet_id='$Planetid' AND num='$flnum'" );
    $row[0] = -1;
    if ($result) 
      $row = mysqli_fetch_row ($result);

    if ($row[0] || $row[1] || $row[2] ) {
      return "Fleet $flnum is not at home (ETA $row[1])";
    } else {

    if ($order >= 10 && $x == $myrow["x"] && $y == $myrow["y"] && $havoc == 0 ) {
    	return "You cannot attack in our own Galaxy";
    }

    $target_id = get_id ($x, $y, $z);
      
      if (!$target_id)
	return "Target coords ($x, $y, $z) are invalid!!";
      
      $msg = check_valid ($target_id,$order);
      if ($msg != "") 
	return $msg;

      $fuel = get_target_fuel ($flnum, $x, $y, $z);

      if ($myrow["eonium"] < $fuel)
	return "Not enough Fuel ($fuel > $myrow[eonium]) ".
	  "for Target ($x, $y, $z)[$order]";

      $eta = get_target_eta ($flnum, $x, $y, $z);

      $q = "UPDATE fleet set target_id='$target_id',ticks='$eta', ".
	 "type='$order',full_eta='$eta' ".
	 "WHERE num=$flnum AND planet_id='$Planetid'";

      $result = mysqli_query ($db, $q );
      
      $myrow["eonium"] -= $fuel;
      $result = mysqli_query ($db, "UPDATE planet SET eonium='$myrow[eonium]' ".
				 "WHERE id='$Planetid'" );
      
      if ($order<10) {
	set_friendly($target_id,1);
      } else {
	set_hostile($target_id,1);
      }
	
      send_msg_fleet_move ($target_id, $eta, $order, $flnum);
      
    }
  }

  return $msg;
}

function send_missile ($num, $x, $y, $z) {

  global $db, $Planetid, $myrow, $havoc;
  global $missile_id, $number_of_fleets;

  $msg = "";

  $q = "SELECT target_id,fleet_id FROM fleet ".
     "WHERE planet_id=$Planetid AND num=$number_of_fleets";

  $res = mysqli_query ($db, $q );
  $row = mysqli_fetch_row($res);
  if ($row[0] != 0) {
    return "Your Missile turrets still track current launch";
  } else {
    $fleet_id = $row[1];
  }

  if ($x==$myrow['x'] && $y==$myrow['y'] && $z==$myrow['z']) {
    return "Target planet banned ;-)";
  }

  $q = "SELECT sum(units.num) FROM units,fleet ".
       "WHERE units.id=fleet.fleet_id AND fleet.num=0 ".
       "AND units.unit_id=$missile_id AND fleet.planet_id=$Planetid";
  $res = mysqli_query ($db, $q );

  if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_row($res);
    $base_missile = $row[0];

    if ($base_missile==0)
      return "You dont have any Missiles in base.";
  } else 
    return "You dont have any Missiles in base.";

  if ($x == $myrow["x"] && $y == $myrow["y"] && $havoc == 0 ) {
      return "You cannot attack in our own Galaxy";
  }

  if ($base_missile<$num || $num=="" || $num==0)
    $num = $base_missile;

  // check for launchers
  $q = "SELECT num FROM pds WHERE planet_id=$Planetid and pds_id=25";
  $res = mysqli_query ($db, $q );
  if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_row($res);

    if ($row[0]==0)   return "You dont have any Missile Launchers.";
    if ($row[0]<$num) $num = $row[0];
  } else 
    return "You dont have any Missile Launchers.";

  // target checks
  $target_id = get_id ($x, $y, $z);
  if (!$target_id) return "Target coords ($x, $y, $z) are invalid!";
      
  $msg = check_valid ($target_id,11); // order is fixed
  if ($msg != "") return $msg;

  // base fleet id
  $res = mysqli_query ($db, "SELECT fleet_id FROM fleet ".
		      "WHERE planet_id=$Planetid and num=0" );
  $row = mysqli_fetch_row ($res);
  $base_id = $row[0];

  // Hier lock start
  mysqli_query ($db, "LOCK TABLES units WRITE" );

  // safety: also nochmal base number
  $res = mysqli_query ($db, "SELECT sum(num) FROM units ".
		      "WHERE id=$base_id AND unit_id=$missile_id " );
  $row = mysqli_fetch_row ($res);
  $bn = $row[0];

  // weg mit den alten aus der base
  mysqli_query ($db, "DELETE FROM units WHERE id=$base_id AND unit_id=$missile_id" );

  if ($bn != $num) {
    mysqli_query ($db, "INSERT INTO units VALUES($base_id,$missile_id,".
		 ($bn - $num).")" );
  }
  mysqli_query ($db, "INSERT INTO units VALUES ($fleet_id, $missile_id, $num)" );

  // Hier lock end
  //
  mysqli_query ($db, "UNLOCK TABLES" );

  $fuel = get_target_fuel ($fleet_id, $x, $y, $z);

  if ($myrow["eonium"] < $fuel) {
    mysqli_query ($db, "UPDATE units SET id=$base_id ".
		 "WHERE id=$fleet_id" );

    return "Not enough Fuel ($fuel > $myrow[eonium]) ".
      "for Target ($x, $y, $z)[$order]";
  }

  $eta = get_target_eta ($fleet_id, $x, $y, $z);


  mysqli_query ($db, "UPDATE fleet SET target_id=$target_id,full_eta=$eta,".
	       "ticks=$eta,type=11 WHERE fleet_id=$fleet_id" );

  $myrow["eonium"] -= $fuel;
  $result = mysqli_query ($db, "UPDATE planet SET eonium='$myrow[eonium]' ".
			 "WHERE id='$Planetid'" );
      
  set_hostile($target_id,1);
  
  send_msg_fleet_move ($target_id, $eta, 11, 4, "Missile");

  return $msg;
}

$msg = "";

/* missiles are special */
$result = mysqli_query ($db, "SELECT id from unit_class WHERE class=6" );
if ($result && mysqli_num_rows($result) > 0) {
  $row = mysqli_fetch_row($result);
  $missile_id = $row[0];
} else {
  $missile_id = 0;
  // error
}

/* generate array of ships */
$q = "SELECT units.unit_id, uc.name FROM fleet, units, ".
     "unit_class AS uc WHERE fleet.planet_id='$Planetid' ".
     "AND fleet.fleet_id=units.id AND units.unit_id=uc.id ".
     "AND units.unit_id!=$missile_id ".
     "AND fleet.num<$number_of_fleets ".
     "GROUP BY units.unit_id";

$result = mysqli_query ($db, $q );

if ($result && mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_row($result)) {
    $ships[$row[0]] = $row[1];
  }
}
$ships[255] = "All Ships";

if (ISSET($_POST["transfer"])) {
  if (ISSET($_POST["ship_move_0"]))
    $msg .= 
      transfer_ship ($_POST["ship_move_0"], $_POST["ship_number_0"], $_POST["ship_from_0"], $_POST["ship_to_0"]);
  if (ISSET($_POST["ship_move_1"]))
    $msg .= 
      transfer_ship ($_POST["ship_move_1"], $_POST["ship_number_1"], $_POST["ship_from_1"], $_POST["ship_to_1"]);
  if (ISSET($_POST["ship_move_2"]))
    $msg .= 
      transfer_ship ($_POST["ship_move_2"], $_POST["ship_number_2"], $_POST["ship_from_2"], $_POST["ship_to_2"]);
  if (ISSET($_POST["ship_move_3"])) 
    $msg .= 
      transfer_ship ($_POST["ship_move_3"], $_POST["ship_number_3"], $_POST["ship_from_3"], $_POST["ship_to_3"]);
}

/* hack so "Recall" from galstatus (GET) works */
$fleet_post = array();
if (ISSET($_POST["execute"]))
  $fleet_post = $_POST;
if (ISSET($_GET["execute"]))
  $fleet_post = $_GET;

if (ISSET($fleet_post["execute"])) {
  // msg .= "[". $_POST["fleet_1_x"].",". $_POST["fleet_1_y"]." ,".$_POST["fleet_1_z"]."]";
  if (ISSET($fleet_post["fleet_1"])&& $fleet_post["fleet_1"] != 0 ) 
    $msg .= send_fleet (1, $fleet_post["fleet_1"], $fleet_post["fleet_1_x"], $fleet_post["fleet_1_y"] ,$fleet_post["fleet_1_z"]);
  if (ISSET($fleet_post["fleet_2"])&& $fleet_post["fleet_2"] != 0 ) 
    $msg .= send_fleet (2, $fleet_post["fleet_2"], $fleet_post["fleet_2_x"], $fleet_post["fleet_2_y"] ,$fleet_post["fleet_2_z"]);
  if (ISSET($fleet_post["fleet_3"])&& $fleet_post["fleet_3"] != 0 ) 
    $msg .= send_fleet (3, $fleet_post["fleet_3"], $fleet_post["fleet_3_x"], $fleet_post["fleet_3_y"] ,$fleet_post["fleet_3_z"]);
}

if (ISSET($_POST["launch"])) {
  $msg .= send_missile ($_POST["miss_num"], $_POST["miss_x"], $_POST["miss_y"], $_POST["miss_z"]);
}

function rtime () {
  global $start_time, $Planetid;

  if ($Planetid != 1) return;

  $end_time = getmicrotime();
  $diff_time = $end_time - $start_time ;
  echo "Runtime: ". number_format($diff_time, 3) ." s<br>";
}

require_once "navigation.inc";

echo "<div id=\"main\">\n";
/* top table is written now */
top_header($myrow);

titlebox("Military", $msg);

?>

<center>
<table class="std" border="1" width="650">
<tr><th colspan="5" class="a">Fleet Status</th></tr>
<tr><th width="170">Ship Type</th>
<?php

$width = 480 / $number_of_fleets;
echo "<th width=\"$width\">Base</th>\n";

for ($i=1; $i<$number_of_fleets; $i++) {
  echo "<th width=\"$width\">Fleet $i</th>\n";
}
echo "</tr>";

// preset variable
for ($i=0; $i<$number_of_fleets; $i++) {
  $ship_in_fleet[$i] = 0;
  $fleet_on_the_way[$i] = 0;
}

$q = "SELECT units.unit_id, fleet.num, sum(units.num), ".
  "fleet.ticks+fleet.type+fleet.full_eta FROM fleet, units ".
  "WHERE fleet.planet_id='$Planetid' AND fleet.fleet_id=units.id ".
  "GROUP BY units.unit_id,fleet.num";

$res = mysqli_query($db, $q );
// preselect first row
$row = mysqli_fetch_row ($res);

$ship_opt = "";
while (list ($num, $name) = each ($ships)) {

  if ($num < 255) {
    $ship_opt .= "<option value=\"$num\">$name</option>\n"; 
    echo "<tr><td>$name</td>";
    for ($i=0; $i<$number_of_fleets; $i++) {

      echo "<td align=\"center\">\n";

      if ($num == $row[0] && $i == $row[1]) {
	if ($row[2]>0) {
	  // we have ships of this type in this fleet
	  $ship_in_fleet[$i] += $row[2];

	  echo "$row[2]";

	  // fleet is out
	  $fleet_on_the_way[$i] += $row[3];
	} else {
	  echo "&nbsp;";
	}

	// fetch next
	$row = mysqli_fetch_row ($res);
      } else {
	  echo "&nbsp;";
      }
      echo "</td>\n";
    }
    echo "</tr>";
  } else {
    $ship_opt .= "<option selected value=\"$num\">$name</option>\n"; 
  }
}

for ($i=1; $i<$number_of_fleets; $i++) {
  $info[$i]["eta"] = get_eta($i);
  $info[$i]["fuel"] = get_fuel($i);
}

echo "<tr><td>Galaxy travel time<BR>Galaxy Eon cost</td><td>&nbsp;</td>\n";

for ($i=1; $i<$number_of_fleets; $i++) {
  if ($ship_in_fleet && $ship_in_fleet[$i] && !$fleet_on_the_way[$i]) {
    echo "<td align=\"right\">". $info[$i]["eta"][0] . " ticks<br>". 
      $info[$i]["fuel"][0] ."</td>\n";
  } else {
    echo "<td>&nbsp;</td>\n";
  }

}
echo "</tr><tr><td>Cluster travel time<BR>Cluster Eon cost</td><td>&nbsp;</td>";
for ($i=1; $i<$number_of_fleets; $i++) {
  if ($ship_in_fleet && $ship_in_fleet[$i] && !$fleet_on_the_way[$i]) {
    echo "<td align=\"right\">". $info[$i]["eta"][1] . " ticks<br>". 
      $info[$i]["fuel"][1] ."</td>\n";
  } else {
    echo "<td>&nbsp;</td>";
  }

}
echo "</tr><tr><td>Universe travel time<BR>Universe Eon cost</td><td>&nbsp;</td>";

for ($i=1; $i<$number_of_fleets; $i++) {
  if ($ship_in_fleet && $ship_in_fleet[$i] && !$fleet_on_the_way[$i]) {
    echo "<td align=\"right\">". $info[$i]["eta"][2] . " ticks<br>". 
      $info[$i]["fuel"][2] ."</td>\n";
  } else {
    echo "<td>&nbsp;</td>\n";
  }
}
echo "</tr>\n";

?>
</table>

<br>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table class="std" border="1" width="650">
<tr><th colspan="4" class="a">Ship Movement</th></tr>
<tr><th width="195">Ship Type</th>
    <th width="165">Number</th>
    <th width="165">From fleet</th>
    <th width="165">To fleet</th>
</tr>
<?php
$fleet_opt = "<option value=\"255\">Base</option>\n";

for ($i=1; $i<$number_of_fleets; $i++) {
  $fleet_opt .= "<option value=\"$i\">Fleet $i</option>\n";
}

for ($i=0; $i<4; $i++) {
  echo "<tr><td align=\"center\"><select name=\"ship_move_$i\">$ship_opt</select></td>\n";
  echo "<td align=\"center\"><input type=\"text\" name=\"ship_number_$i\" size=\"8\" maxlength=\"8\"></td>\n";
  echo "<td align=\"center\"><select name=\"ship_from_$i\">$fleet_opt</select></td>\n";
  echo "<td align=\"center\"><select name=\"ship_to_$i\">$fleet_opt</select></td></tr>\n";
}
?>

<tr><td colspan="4" align="center">
<input type=submit value=" Transfer Ships " name="transfer"></td></tr>
<tr><td colspan="4" align="center">
Ships can only be moved between fleets if they reside in the same sector<br>
Hint: to move all ships of a type dont fill in any number</td></tr>
</table>
</form>

<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table class="std" border="1" width="650">
<tr><th colspan="5" class="a">Fleet Movement</th></tr>
<tr><th width="53">Fleet</th>
    <th width="53">Mission</th>
    <th width="289">Location</th>
    <th width="100">New Order</th>
    <th width="155">Target</th>
</tr>
<?php
$q = "SELECT target_id, num, ticks, type FROM fleet WHERE planet_id=$Planetid ".
     "AND num IN (1,2,3) ORDER by num"; // ORDER BY num
$result = mysqli_query ($db, $q );

while ($row = mysqli_fetch_row($result)) {
  if ($row[0] == 0) {
    /* at home or on the way */
    print_target_row($row[1], $myrow["x"], $myrow["y"], $myrow["z"],
                     $myrow["planetname"], $row[3], $row[2]);
  } else {
    $rowt = get_coord_name ($row[0]);
    print_target_row($row[1], $rowt["x"], $rowt["y"], $rowt["z"],
                     $rowt["planetname"], $row[3], $row[2]);
  }
}
?>
<tr><td colspan="5" align="center">
  <input type=submit value=" Execute Orders " name="execute"></td></tr>
</table>
</form>

<?php

if ($missile_id!=0) {
  echo <<<EOF
<form method="post" action="$_SERVER[PHP_SELF]">
<table class="std" border="1" width="650">
<tr><th colspan="5" class="a">Missile Attack</th></tr>
<tr><th width="55">Group</th>
    <th width="55">Mission</th>
    <th width="355">Location</th>
    <th width="55">Number</th>
    <th width="120">Target</th>
</tr>
EOF;

  $q = "SELECT sum(units.num) FROM units,fleet ".
       "WHERE units.id=fleet.fleet_id AND fleet.num=0 ".
       "AND units.unit_id=$missile_id AND fleet.planet_id=$Planetid";

  $res = mysqli_query ($db, $q );

  if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_row($res);
    $base_missile = $row[0];

    if ($base_missile>0) {
      $eta = get_eta ($number_of_fleets);

      echo "<tr><td></td><td>Wait</td><td>On hold, ".
        "ETA ($eta[0],$eta[1],$eta[2]) ticks".
        "</td><td align=center>".
	"$base_missile</td><td></td></tr>\n";
    }
  }

  $q = "SELECT target_id, ticks, fleet_id ".
       "FROM fleet WHERE planet_id=$Planetid ".
       "AND num=$number_of_fleets AND target_id!=0";

  $res = mysqli_query ($db, $q );

  if ($res && mysqli_num_rows($res) > 0) {

    $row = mysqli_fetch_row($res);

    // find num units, target_xy_z
    $rowt = get_coord_name ($row[0]);
    $numm = get_num_units ($row[2], $missile_id);

    echo "<tr><td>Group 1</td><td>Attack</td>\n".
      "<td>$rowt[planetname] ($rowt[x]:$rowt[y]:$rowt[z])".
      " [ETA $row[1] ticks] </td><td align=center>".
      "$numm</td><td></td></tr>\n";
  } else {
    if ($base_missile>0) {
      echo "<tr><td></td><td>Launch</td><td></td>\n".
        "<td align=\"center\"><input type=\"text\" name=\"miss_num\"".
        "size=\"6\" maxlength=\"8\"></td><td align=\"center\">\n".
        "<input type=\"text\" name=\"miss_x\" size=\"4\" maxlength=\"3\">".
        "&nbsp;<input type=\"text\" name=\"miss_y\" size=\"3\" maxlength=\"2\">".
        "&nbsp;<input type=\"text\" name=\"miss_z\" size=\"3\" maxlength=\"2\">".
        "</td></tr>\n";

      echo "<tr><td colspan=\"5\" align=\"center\">".
        "<input type=submit value=\" Launch Missiles \" name=\"launch\"></td></tr>\n";
    }
  }
  echo "</table>\n</form>\n";

}
?>

</center>
</div>

<?php
require "footer.php";
?>
