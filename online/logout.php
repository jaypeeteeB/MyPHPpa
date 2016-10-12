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

require "options.php";
require "dblogon.php";
require "logging.php";
include_once "session.inc";

session_init();
if (session_check()) {
  echo "error check session";
  // Header("Location: index.php");
  die;
 }

if ( $Planetid>0 ) {
  $result = mysqli_query($db, "UPDATE user SET uptime=".
             "SEC_TO_TIME(UNIX_TIMESTAMP(last) - UNIX_TIMESTAMP(login_date) + ".
             "TIME_TO_SEC(uptime)) ".
             "WHERE planet_id='$Planetid' AND (mode&0xF) = 2");
  $result = mysqli_query($db, "UPDATE planet SET mode=((mode & 0xF0) + 1) ".
		      "WHERE id='$Planetid' AND (mode&0xF) = 2" );
  do_log_me(2, 1,""); 
  // event:logout=2, class:login/out=1
}

session_kill();
// setcookie("Username","");
// setcookie("Password","");
// setcookie("mysession","");
// setcookie("Planetid","-1");

require "header.php";
my_header();
?>

<br>
<center>
Goodbye and have a nice day :-)
<p>
Go to <a href="index.php">login</a> page.


<?php
require "footer.php";
?>
