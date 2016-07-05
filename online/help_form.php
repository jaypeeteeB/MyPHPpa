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
require "dblogon.php";

require "header.php";
my_header("",0,0);

require "msgbox.php";

titlebox("Formulas");
?>
<center>
<table border=1 width="650" cellpadding="3">
<tr class="a"><th colspan=3>A collection of formulas used in the game</th></tr>
<tr class="b"><th width="100">Name</th>
              <th width="150">Formula</th>
              <th>Description</th></tr>

<tr><td>Score</td>
<td>
<pre style="font-size:9pt"> score = sum (resources) / 100
         + sum(res_cost_units) / 10
         + sum(res_cost_pds) / 10
         + sum(res_cost_scan) / 10
         + sum(res_cost_research_done) / 10
         + sum(res_cost_construct_done) / 10
         + sum(init_roids) * 1500
</pre>
</td>
<td>All spent resources counts as one 10th towards your score (ofc not destroyed ships, used amps etc)
</td></tr>

<tr><td>Resource</td>
<td>
<pre style="font-size:9pt">if (num_of_res_roid < 42) {
  res_per_type = (351 - num_of_res_roid)
                    * num_of_res_roid;
} else {
  res_per_type = sqrt(num_of_res_roid) 
                   * (2000 + rc_modifier);
}
res_per_type = res_per_type + planet_income;
</pre></td>
<td>This gives you the resources you get based on the number of roids you have of a certain type.
</td></tr>

<tr><td>Resource (havoc)</td>
<td>
<pre style="font-size:9pt">
res_per_type = max(351 - num_of_res_roid, 150)) 
                 *  num_of_res_roid);
res_per_type = res_per_type + planet_income;
</pre>
</td>
<td>This gives you the resources you get based on the number of roids you have
<b>only during havoc</b>
</td></tr>

<tr><td>Roid scan</td>
<td>
<pre style="font-size:9pt">
if (total_roids > 200) {
  chance = 30. * (amps/(total_roids*2));
} else if ($total_roids > 0) {
  chance = 30. * (1 + amps/(total_roids*3));
} else {
  chance = 31. * (1 + amps/2);
}
</pre>
max chance =  99.99%<br>
min chance = 0.01 %
</td>
<td>This gives you the chance to find an asteroids based on the number of wave amplifiers and the number of roids you already have.
</td></tr>

<tr><td>General scan</td>
<td>
<pre style="font-size:9pt">
chance = 30. * (1 + amps/total_roids 
                 - reflector/target_roids);
rval = random (0, 100);
scan_fact = 2; // sector
            4; // unit
            5; // pds
            7; // news
            8; // military

if (rval < chance ) {
  reached = TRUE;
  if ( rval+5*scan_fact > chance ) {
    noticed = TRUE;
  }
} else {
  blocked = TRUE;
}
</pre>
</td>
<td>This gives you the chance to successfull scan a target.
</td></tr>

<tr><td>Protection</td>
<td>
<pre style="font-size:9pt">
if ( target_score * <?php echo $noob_prot; ?> < my_score)
  "Target too small";
// if ( target_score / <?php echo $high_prot; ?> > my_score)
//   "Target too big";
</pre>
</td>
<td>You may only attack targets within this range of your score.
</td></tr>

<tr><td>Traffic control</td>
<td>
<pre style="font-size:9pt">
if ( havoc == 0 && 
     attacker_fleet_score > 
       ( 2.5 * target_score) ) {
  return "Denied access";
}
</pre>
</td>
<td>This limits the attacking forces onto a single target.
</td></tr>

<tr><td>Salvage</td>
<td>
<pre  style="font-size:9pt">
max_salvalge = 0.25 * res (all_ships_lost);

your_salvage = (res (your_fleet_score) /
       res (all_def_fleet_score))
     * max_salvalge; 

if (your_salvage > 2 * your_losses) {
  your_salvage = 2 * your_losses;
}
</pre>
</td>
<td>
The salvage you gain during defense. As a side effect you get nothing if you loose nothing.
</td></tr>

<tr><td>Battle</td>
<td colspan="2">
If you want to go in depth on this have a look at the 
<a href="battlecalc/index">Battlecalc</a>.
</td></tr>

<tr><td>Unused Acc</td>
<td colspan="2">Unused accounts with less then 4 roids are deleted after
12 hours idletime.
</td></tr>
<tr><td>Deleted Acc</td>
<td colspan="2">Acounts marked for deletion are deleted after
12 hours. Deletion is removed if you login during this time.
</td></tr>
<tr><td>Banned Acc</td>
<td colspan="2">Banned accounts are deleted after 36 hours.
</td></tr>


</table>
<br>
<table border=0 width="650">
<tr><td align=left>Khan, 1. Nov 2002</td></tr>
</table>

</center>

<?php
require "footer.php";
?>
