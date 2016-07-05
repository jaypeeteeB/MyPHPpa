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

function t() {
  global $start_time;
  $end_time = getmicrotime();
  $diff_time = $end_time - $start_time ;
  echo "[". number_format($diff_time, 3) ." s]";
   $start_time = $end_time;
}

echo "<br>";
echo "Optimizing<br>";

$res = mysqli_query ($db, "SHOW tables");

while ($row=mysqli_fetch_row($res)) {
  echo "Table: $row[0] ...";
  mysqli_query ($db, "OPTIMIZE TABLE $row[0]" );
  t ();
  echo "<br>";
}
echo "";

require_once "../footer.php";
?>
