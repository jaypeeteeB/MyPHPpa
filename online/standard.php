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

require "auth_check.php";
include_once "options.php";
include_once "get_ip.php";

include_once "session.inc";
session_init();
if (session_check()) {
  echo "error check session";
  Header("Location: index.php");
  die;
 }

$player_ip=get_ip();
pre_auth($Username,$Password,$Planetid,$_COOKIE["Valid"]);

require "dblogon.php";

db_auth($db,$Username,$Password,$Planetid);

$result = mysqli_query($db, "SELECT tick FROM general"); 
$row = mysqli_fetch_row($result);
$mytick = $row[0];

require "header.php";
if (ISSET($extra_header)) {
  my_header($extra_header);
} else {
  my_header();
}

require "top.php";

$result = mysqli_query($db, "SELECT * FROM planet WHERE id='$Planetid'");
$myrow = mysqli_fetch_array($result);

mysqli_query($db, "UPDATE user set last=NOW(),last_tick='$mytick',".
	     "ip='$player_ip' ".
	     "WHERE planet_id='$Planetid'"); 

require "msgbox.php";

?>
