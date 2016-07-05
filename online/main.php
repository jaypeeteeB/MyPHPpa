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
include_once "session.inc";

session_init();
if (session_check(get_ip())) {
  echo "error check session";
  Header("Location: index.php");
  die;
 }


// if ( !$Username || $Username=="" || 
//      !$Password || $Password=="" || 
//      !$Planetid || $Planetid==0 ||
//      !$Valid || $Valid!=md5($round)) {
//
//   setcookie("Username","");
//   setcookie("Password","");
//   setcookie("Planetid","-1");
//   Header("Location: index.php");
//   die;
// }

require "dblogon.php";

// $result = mysqli_query($db, "SELECT login,settings FROM user ".
//                      "WHERE login='$Username' AND md5(password)='$Password' ".
//		      "AND planet_id='$Planetid'");
$result = mysqli_query($db, "SELECT login,settings FROM user ".
                      "WHERE planet_id='$Planetid'");

if (!$result || mysqli_num_rows($result) != 1) {

  session_kill();
  //   setcookie("Username","");
  //   setcookie("Password","");
  //   setcookie("Planetid","-1");
  echo "No such user error";
  Header("Location: error.php");

  die;
}

require "headerf.php";

?>

<FRAMESET COLS="150,*">
  <FRAME SRC="navigation.php" NAME="navigation" noresize>
  <FRAME SRC="overview.php" NAME="main">
  <NOFRAMES>
    <A HREF="navigation.php">Navigation</A><br>
    <A HREF="overview.php">Overview</A>
  </NOFRAMES>
</FRAMESET>

<?php
require "footerf.php";
?>
