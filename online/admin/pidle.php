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

function idle_players () {
  global $db;

  $q = "select leader,id,score,x,y,z,UNIX_TIMESTAMP(user.last) - ".
    "UNIX_TIMESTAMP(user.signup) as delta,user.last ,user.signup ".
    " from planet,user where planet_id=id and uniniroids = 3 and ".
    "metalroids=0 and crystalroids=0 order by last,signup";
  $res = mysqli_query($db, $q );

  if (!$res || mysqli_num_rows($res) == 0)
    return;
  while ($row=mysqli_fetch_array($res)) {
    echo "<tr>".
    "<td>$row[0]</td>".
    "<td><a href=\"pdelete.php?submit=1&playerid=$row[1]\">$row[1]</a></td>".
    "<td>$row[2]</td>".
    "<td><a href=\"pinfo.php?submit=1&playerid=$row[1]\">$row[3]:$row[4]:$row[5]</a></td>".
    "<td>$row[6]</td>".
    "<td>$row[7]</td>".
    "<td>$row[8]</td>".
     "</tr>\n";
  }
}
?>

<center>
Find idle Players
<br>
<table border="1">
<tr>
<th>Leader</th><th>Id</th><th>Score</th><th>Coords</th>
<th>Delta</th><th>Last</th><th>Signup</th>
</tr>
<?php
idle_players();
echo "</table>\n";

require_once "../footer.php";
?>
