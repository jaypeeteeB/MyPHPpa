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

function get_eta ($fleet_num) {
  global $db, $Planetid, $myrow;

  $q = "SELECT MAX(uc.speed) FROM fleet,units,unit_class AS uc ".
     "WHERE fleet.planet_id='$Planetid' AND units.id=fleet.fleet_id ".
     "AND fleet.num='$fleet_num' AND uc.id=units.unit_id";

  $result = mysqli_query($db, $q );
  $row = mysqli_fetch_row($result);
  $eta = $row[0];

  // ticker sets modifier
  $modifier = $myrow["speed_modifier"];
  
  $eta = $eta + 5 - $modifier;
  return array($eta, $eta+1, $eta+4);
}

function get_target_eta ($fleet_num, $x, $y, $z) {
  global $db, $Planetid, $myrow;

  $eta = get_eta ($fleet_num);
  if ( (int) $x == (int) $myrow["x"] ) {
    if ( (int) $y == (int) $myrow["y"] ) {
      return $eta[0];
    } else {
      return $eta[1];
    }
  } else {
    return $eta[2];
  }
}

function get_fuel ($fleet_num) {
  global $db, $Planetid;

  $q = "SELECT SUM(uc.fuel * units.num) FROM fleet,units,unit_class AS uc ".
     "WHERE fleet.planet_id='$Planetid' AND units.id=fleet.fleet_id ".
     "AND fleet.num='$fleet_num' AND uc.id=units.unit_id";
  
  $fuel = 0;

  $result = mysqli_query($db, $q );
  if ($result && mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_row($result);
    if ($row && $row[0])
       $fuel = $row[0];
  }
  return array ( (int) ($fuel/5), (int)($fuel/2), $fuel);
}

function get_target_fuel ($fleet_num, $x, $y, $z) {
  global $db, $Planetid, $myrow;

  $fuel = get_fuel ($fleet_num);

  if ( (int) $x == (int) $myrow["x"] ) {
    if ( (int) $y == (int) $myrow["y"] ) {
      return $fuel[0];
    } else {
      return $fuel[1];
    }
  } else {
    return $fuel[2];
  }

}

function fetch_fleet_sum ($fleet_id)
{
  global $db;

  $q = "SELECT SUM(units.num) FROM units, unit_class AS uc ".
     "WHERE  units.unit_id=uc.id AND uc.class!=3 AND units.id='$fleet_id'";

  $result = mysqli_query($db,  $q );
  if ($result) {
    $mynum = mysqli_fetch_row ($result);
    return $mynum[0];
  } else {
    return 0;
  }

}

?>
