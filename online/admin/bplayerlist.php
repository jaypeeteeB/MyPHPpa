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

function print_td($row) {
  echo "<tr>";

  foreach ($row as $value) 
    echo "<td>".$value."</td>";

  echo "</tr>\n";
}

function query_table ($query) {
  global $db;

  $res = mysqli_query($db, $query );

  if (!$res || mysqli_num_rows($res) == 0)
    return;

  // query col heads
  $head = mysqli_fetch_assoc($res);
  echo "<table border=\"1\">\n";

  echo "<tr>";
  foreach (array_keys($head) as $key)
    echo "<th>".$key."</th>";  
  echo "</tr>";

  mysqli_data_seek($res, 0);

  while ($row=mysqli_fetch_row($res)) {
    print_td ($row);
  }

  echo "</table>\n";
}

function list_banned_player () {

  $q = "SELECT id, leader,planetname,x,y,z FROM planet WHERE mode=0";

  query_table ( $q );
}


echo "<center>\n<br>\n";

echo "List banned players\n<br>\n";
list_banned_player();

echo "</center>\n";

require_once "../footer.php";
?>
