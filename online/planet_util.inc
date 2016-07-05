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

function get_id ($x, $y, $z) {
  global $db;

  $q = "SELECT id FROM planet WHERE x='$x' AND y='$y' AND z='$z'";
  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_row ($result);
    return $row[0];
  } 

  return 0;
}

function get_coord ($id) {
  global $db;
  
  $result = mysqli_query ($db, "SELECT x,y,z FROM planet WHERE id='$id'" );

  if ($result && mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_array ($result);
    return $row;
  } 
  return 0;
}

function get_coord_name ($id) {
  global $db;
  
  $result = mysqli_query ($db, "SELECT x,y,z,planetname,leader FROM planet WHERE id='$id'" );

  if ($result && mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_array ($result);
    return $row;
  } 
  return 0;
}

function coord_ref($x, $y, $text) {
  return "<a href=galaxy.php?submit=search&x=$x&y=$y>$text</a>";
}

function get_status ($id) {
  global $db;

  $result = mysqli_query ($db, "SELECT mode FROM planet WHERE id='$id'" );
  
  if ($result && mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_array ($result);
    return $row[0];
  }
  return -1;
}

function get_score ($id) {
  global $db;

  $result = mysqli_query ($db, "SELECT score FROM planet WHERE id='$id'" );
  
  if ($result && mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_array ($result);
    return $row[0];
  }
  return -1;
}

function get_galaxy_id($x, $y) {
  global $db;

  $result = mysqli_query ($db, "SELECT id FROM galaxy WHERE x='$x' AND y='$y'" );
  if ($result && mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_array ($result);
    return $row[0];
  }
  return 0;
}

?>
