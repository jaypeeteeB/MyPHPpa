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

if (ISSET($rid)) {
  $q = "UPDATE galaxy set pic='' WHERE id=$rid";
  $res = mysqli_query ($db, $q );
  echo "Pic of [$rid] deleted<br>\n";
}
 
  $q = "SELECT x, y, gc, pic, id FROM galaxy where pic!=''";
  $res = mysqli_query($db, $q );

  if ($res && mysqli_num_rows($res)>0) {
    echo "<center><table border=\"1\">\n";
  
    while ($row=mysqli_fetch_row($res)) {
      echo "<tr><td>($row[0],$row[1])</td>\n";
      echo "<td><a href=\"$_SERVER['PHP_SELF']?rid=$row[4]\">Remove</a></td>\n";
      echo "<td><img src=\"$row[3]\" height=\"200\"></td></tr>\n";
    }
    echo "</table>\n";
  }

require_once "../footer.php";
?>
