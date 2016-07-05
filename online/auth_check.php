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
// hier weiter auf session umstellen
function pre_auth($Username, $Password, $Planetid, $Valid, $location="index.php") {
  global $round;

  if ( !$Username || $Username=="" || 
       !$Password || $Password=="" || 
       !$Planetid || $Planetid<1 ||
       !$Valid || $Valid != md5($round)) {

    session_kill();
    Header("Location: " . $location);
    die;
  }
}

function db_auth($db,$Username,$Password,$Planetid) {
  global $mysettings;

  $result = mysqli_query($db, "SELECT user.last,user.settings ".
                        "FROM user, planet ".
			"WHERE  user.login='$Username' ".
			"AND md5(user.password)='$Password' ".
			"AND user.planet_id='$Planetid' ".
			"AND user.planet_id = planet.id ".
			"AND (planet.mode&0xF) = 2  ".
			"AND now() <  user.last + INTERVAL 30 MINUTE");

  if (mysqli_num_rows($result) != 1) {
    mysqli_query($db, "UPDATE user SET uptime=".
           "SEC_TO_TIME(UNIX_TIMESTAMP(last) - UNIX_TIMESTAMP(login_date) + ".
           "TIME_TO_SEC(uptime)) ".
           "WHERE planet_id='$Planetid' AND (mode&0xF) = 2" );

    mysqli_query($db, "UPDATE planet SET mode=((mode & 0xF0) + 1) ".
		"WHERE id='$Planetid' AND (mode&0xF) = 2" );
    
    // setcookie("Username","");
    // setcookie("Password","");
    // setcookie("Planetid","-1");
    session_kill();
    Header("Location: error.php");
    die;
  } else {
    $row = mysqli_fetch_row($result);
    $mysettings = $row[1];
  } 
}

function gen_cookie () {

  mt_srand ((double) microtime() * 1000000);
  $randval = mt_rand();
        
  return md5("$randval");
}

