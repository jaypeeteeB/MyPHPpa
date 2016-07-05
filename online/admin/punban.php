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

require_once "admhead.php";
require_once "admform.php";
require_once "../logging.php";

if (ISSET($submit)) echo "Found submit<br>";
if (ISSET($playerid)) echo "Playerid $playerid<br>";
if (ISSET($verification)) echo "Verification: $verification<br>";

if (ISSET($submit) && ISSET($playerid) && $playerid != 1) {

  if (ISSET($verification) && $verification==$playerid) {
      $q = "UPDATE planet set mode=1 WHERE id='$playerid'";
      mysqli_query ($db, $q );
      echo "<center>Planet <b>unbanned</b></center>";
      do_log_id ($playerid,2,6,"");
  } else {
    $q = "SELECT leader,planetname,x,y,z FROM planet WHERE id='$playerid'";
    $result = mysqli_query ($db, $q );
    if ($result && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_row($result);
      echo <<<EOF
<center>
<form method="post" action="$_SERVER['PHP_SELF']">
<table  width="640" border="1" cellpadding="2" >
<tr><td>
   Really unban this player?&nbsp;
   $row[0] of $row[1] ($row[2]:$row[3]:$row[4])</td></tr>
<tr><td align="center">
  <input type="hidden" name="playerid" value="$playerid">
  <input type="hidden" name="verification" value="$playerid">
  <input type="submit" name="submit" value="       Ban       "></td></tr>
</table>
</form>

EOF;
    } else {
      echo "<center> No such Planet </center>";
    }
  }
} else {
  echo <<<EOF
<center>
<table  width="640" border="1" cellpadding="2" >
<tr>
<form method="post" action="$_SERVER['PHP_SELF']">
  <td align="center" bgcolor="#c0c0c0">Enter target id:</td>
  <td><input type="text" name="playerid" size="25"></td>
  <td colspan="2"><input type=submit value="  Search  " name=submit></td>
</form>
</tr>
</table>
</center>
EOF;
}

require_once "../footer.php";
?>
