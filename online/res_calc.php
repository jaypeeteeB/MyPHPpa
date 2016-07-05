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

function calc_per_roid ($num,$mod) {
  global $resource_min_per_roid, $resource_max_per_roid;
  global $havoc;

  // alte formel
  if ($havoc == 1)
    return (max($resource_max_per_roid + 1 - (int)$num, $resource_min_per_roid) * $num);

  // variable machen:
  if ($num<42) {
    return (($resource_max_per_roid + 1 - (int)$num)*$num);
  } else {
    return ((int) (sqrt($num)*(2000+$mod)));
  }

}

function calc_roid_resource ($myrow) {
  $sum=$myrow["metalroids"]+$myrow["crystalroids"]+$myrow["eoniumroids"];

  $res["metal"] = calc_per_roid ($myrow["metalroids"],$myrow["roid_modifier"]);
  $res["crystal"] = calc_per_roid ($myrow["crystalroids"],$myrow["roid_modifier"]);
  $res["eonium"] = calc_per_roid ($myrow["eoniumroids"],$myrow["roid_modifier"]);
  return ($res);
}

function calc_init_cost_new ($myrow, $n) {
  $b = $myrow["metalroids"]+$myrow["crystalroids"]+$myrow["eoniumroids"];
  $m = $n * (1000 + $b*250 + 250 * ($n-1) /2);
  return $m;
}

function return_max_init ($myrow, $m) {
  $b = $myrow["metalroids"]+$myrow["crystalroids"]+$myrow["eoniumroids"];

  $mm = 0;
  $n = 0;

  while ($mm < $m) {
    $n += 1;
    $mm = $n * (1000 + $b*250 + 250 * ($n-1) /2);
  }
  return ($n-1);   
}

function calc_init_cost ($myrow) {
  return (($myrow["metalroids"]+$myrow["crystalroids"]+$myrow["eoniumroids"])*250+1000);
}

function get_planet_income () {

  global $myrow;

  // ticker set modifier
  $income = array($myrow["planet_m"],$myrow["planet_c"],$myrow["planet_e"]);

  return ($income);
}

?>
