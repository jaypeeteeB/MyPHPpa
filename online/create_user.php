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

function welcome_mail ($pid) {
}

function create_user ($pid) {

  global $db, $number_of_fleets, $start_roids;
  global $start_resource;

  
  /*
   * Research and Construction
   */

  /* 
   * Perhaps add Manually id 0 with status 3
   */

  $q = "INSERT INTO rc (planet_id, rc_id, status) ".
     "SELECT '$pid', id, NOT rc_id FROM rc_class";

  $result = mysqli_query ($db, $q );
  if (!$result) echo "Error in Create_user $pid Research";

  $q = "UPDATE rc SET status=3 WHERE planet_id='$pid' AND rc_id=0 ";
  $result = mysqli_query ($db, $q );
  if (!$result) echo "Error in Create_user $pid Research (0)";

  
  /* 
   * Fleets
   */

  for ($i=0; $i<=$number_of_fleets; $i++) {
    $q = "INSERT INTO fleet set planet_id='$pid',num='$i'";

    $result = mysqli_query ($db, $q );
    if (!$result) echo "Error in Create_user $pid Fleets";
  }

  /*
   * Initial resources
   */
  $q = "UPDATE planet set uniniroids='$start_roids', ".
     "metal='$start_resource[metal]', crystal='$start_resource[crystal]',".
     "eonium='$start_resource[eonium]',planet_m='$start_resource[planet_m]', ".
     "planet_c='$start_resource[planet_c]',planet_e='$start_resource[planet_e]' ".
     "WHERE id='$pid'";

  $result = mysqli_query ($db, $q );
  if (!$result) echo "Error in Create_user $pid Initial resources";

  /*
   * Initial scans
   */
  $q = "INSERT INTO scan (planet_id,wave_id,num) ".
     "SELECT '$pid',id,0 FROM scan_class AS sc WHERE rc_id=0";
  $result = mysqli_query ($db, $q );
  if (!$result) echo "Error in Create_user $pid Initial scans";

  
}

?>
