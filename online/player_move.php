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

require "newcoords.php";

function player_move($sx,$sy,$sz,$tx=0,$ty=0,$tz=0) {
  global $db;

  $res = mysqli_query($db, "SELECT id FROM planet ".
                     "WHERE x=$sx AND y=$sy AND z=$sz");
  if ($res && mysqli_num_rows($res)==1) {
    $rid = mysqli_fetch_row($res);
    echo "Found $rid[0]<br>";
    return player_move_id($rid[0], $sx, $sy, $sz, $tx, $ty, $tz);
  } else {
    echo "Failed to find id $sx, $sy, $sz, $tx, $ty, $tz<br>";
    return 0;
  }
}

function player_move_id ($id, $sx, $sy, $sz, $tx=0, $ty=0, $tz=0) {
  global $db;

  if (!$id || $id==1) {
    echo "cant move admin $id $sx, $sy, $sz, $tx, $ty, $tz<br>";
    return 0;
  }

  $res = mysqli_query($db, "SELECT id FROM galaxy WHERE gc=$id AND ".
                     "x=$sx AND y=$sy");

  if ($res && mysqli_num_rows($res)==1) {
    $q = "UPDATE galaxy set gc=0, members=members-1, name='Far Far Away', ".
	 "text=NULL, pic=NULL WHERE x=$sx AND y=$sy"; 
  } else {
    $q = "UPDATE galaxy set members=members-1 WHERE x=$sx AND y=$sy";
  }
  $res = mysqli_query($db, $q);
  mysqli_query($db, "UPDATE planet set vote=0 WHERE vote='$id'");
  
  $q = "UPDATE planet set x=0,y=0,z=0,vote=0 WHERE id='$id'";
  mysqli_query($db, $q);

  return insert_player ($id, $tx, $ty, $tz);
}

function insert_player ($id, $tx=0,$ty=0,$tz=0) {
  global $db;

  if ($tx==0 || $ty==0 || $tz==0) {
    get_new_coords($tx, $ty, $tz);
  }

  mysqli_query ($db, "UPDATE planet SET x=$tx,y=$ty,z=$tz ".
               "WHERE id=$id");
  mysqli_query ($db, "UPDATE galaxy SET members=members+1 ".
               "WHERE x=$tx AND y=$ty");

  return $id;
}

?>
