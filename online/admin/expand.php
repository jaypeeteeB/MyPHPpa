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

function expand_universe () {
  global $db, $universe_size, $cluster_size;

  echo "Expanding Universe...Up to $universe_size:$cluster_size<br>";

  for ($i=1; $i<=$universe_size;$i++) {
    for ($j=1; $j<=$cluster_size;$j++) {

       $result = mysqli_query ($db, "SELECT id FROM galaxy ".
	"WHERE x='$i' AND y='$j'" );

       if ($result) {
	 if (mysqli_num_rows($result) == 1) {
	   echo "gal $i:$j exist<br>";
	 } else {
           echo "creating gal $i:$j<br>";
           $result = mysqli_query ($db, "INSERT INTO galaxy set x='$i',y='$j'" );
         }
       }
    }
  }
}

?>

Expand Universe
<br>

<?php
expand_universe();

require_once "../footer.php";
?>
