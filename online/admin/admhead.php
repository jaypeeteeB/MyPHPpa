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

require_once "../auth_check.php";
require_once "../options.php";
include_once "../get_ip.php";

include_once "../session.inc";
session_init();
$ret=0;
if (($ret=session_check(get_ip()))) {
  var_dump($_SESSION);
  echo "error check session admhead: $ret (". get_ip() .")" ;
//  Header("Location: ../index.php");
  die;
 }

// Hack
$Valid =  md5($round);

pre_auth($Username,$Password,$Planetid,$Valid);

require_once "../dblogon.php";

db_auth($db,$Username,$Password,$Planetid);

if ($Planetid>2) {
  Header("Location: ../overview.php");
  die;
}

require_once "../header.php";

if (ISSET($extra_header)) {
  my_header($extra_header,0,0);
} else {
  my_header("",0,0);
}

mysqli_query($db, "UPDATE user set last=NOW(),last_tick='$mytick'".
	     "ip='$_SERVER[REMOTE_ADDR]' ".
	     "WHERE planet_id='$Planetid'"); 

$pathInfo = pathinfo($_SERVER['PHP_SELF']);
$base_path = $pathInfo['basename'];

?>
