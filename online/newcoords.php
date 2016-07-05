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


function get_new_coords (&$x, &$y, &$z, $open_cluster=1) {
  global $db;
  global $universe_size, $cluster_size, $gal_size;
  // $open_cluster=1;

  $lower_cluster=1;
  $upper_cluster=$lower_cluster+$open_cluster-1;
  while ($upper_cluster > $universe_size) $upper_cluster--;
  if ($upper_cluster <= 0) return 0;

  do {
    $result = mysqli_query ($db, "SELECT x,y FROM galaxy WHERE x>=$lower_cluster ".
			   "AND x<=$upper_cluster AND members<$gal_size ".
			   "AND !(x=1 and y=1) GROUP BY x, y");
    if (!$result) return 1;

    $cnt = mysqli_num_rows($result);

    if ($cnt == 0) {
      $lower_cluster = $upper_cluster+1;
      if ($lower_cluster > $universe_size) return 1;

      $upper_cluster=$lower_cluster+$open_cluster-1;

      while ($upper_cluster > $universe_size) $upper_cluster--;
      if ($upper_cluster < $lower_cluster) return 1;
    }
  } while($cnt == 0);
  
  if ($cnt != 1) {
    mt_srand ((double) microtime() * 1000000);
    $rval = (int) mt_rand(1, $cnt);
  } else {
    $rval = 1;
  }

  // etwas unelegant aber .. skipping bis zum n-ten
  while ($rval>1) {
    $row = mysqli_fetch_row($result);
    $rval--;
  }
  $row = mysqli_fetch_row($result);

  $x = $row[0];
  $y = $row[1];

  $result = mysqli_query ($db, "SELECT z FROM planet WHERE x='$x' and y='$y' ".
			 "ORDER  by z DESC LIMIT 1");
  $row = mysqli_fetch_row($result);
  $z = $row[0] + 1;

  return 0;
}

?>
