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

if (ISSET($_REQUEST["submit"])) echo "Found submit<br>";
if (ISSET($_REQUEST["playerid"])) echo "Playerid ".$_REQUEST["playerid"]."<br>";
if (ISSET($_REQUEST["verification"])) echo "Verification: ".$_REQUEST["verification"]."<br>";

if (ISSET($_REQUEST["submit"]) && ISSET($_REQUEST["ip"]) ) {

  if (ISSET($_REQUEST["verification"]) && $_REQUEST["verification"]==$_REQUEST["ip"]) {
      $q = "INSERT INTO iptables set ip='".$_REQUEST["ip"]."',comment='$comment'";
      mysqli_query ($db, $q );
      echo "<center>IP banned</center>";
  } else {
      echo <<<EOF
<center>
<table  width="640" border="1" cellpadding="2" >
<tr><td>Really ban this IP?</td><td><b>$ip</b></td></tr>
<tr><td align="center"><a href="$_SERVER[PHP_SELF]?submit=1&ip=$ip&verification=$ip">Yes</a></td></tr>
</table>

EOF;
  }
} else {
  echo <<<EOF
<center>
<table  width="640" border="1" cellpadding="2" >
<tr>
<form method="post" action="$_SERVER[PHP_SELF]">
  <td align="center" bgcolor="#c0c0c0">Enter target id:</td>
  <td align="center"><input type="text" name="ip" size="25"></td></tr>
<tr><td colspan=2><input type="text" name="comment" size="80"></td></tr>
<tr><td colspan="2" align="center"><input type=submit value="  Ban IP  " name=submit></td>
</form>
</tr>
</table>
</center>
EOF;
}

require_once "../footer.php";
?>
