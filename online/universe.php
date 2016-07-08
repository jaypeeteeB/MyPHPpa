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

require "standard.php";
require "uni_report.php";

/* top table is written now */
top_header($myrow);

titlebox("Universe");

echo <<<EOF
<center>
<table border="0" width="650">
  <tr><td width="200">&nbsp;</td>
      <td width="10" align="center"><hr></td>
      <td align="center" width="100"><b><a href="$_SERVER[PHP_SELF]">Player</a></b></td>
      <td width="10" align="center"><hr></td>
      <td align="center" width="100"><b><a href="$_SERVER[PHP_SELF]?type=galaxy">Galaxies</a></b></td>
      <td width="10" align="center"><hr></td>
      <td align="center" width="100"><b><a href="$_SERVER[PHP_SELF]?type=alliance">Alliance</a></b></td>
      <td width="10" align="center"><hr></td>
      <td width="200">&nbsp;</td>
  </tr>
</table>
<br>
EOF;

if (!ISSET($_REQUEST["type"])) {
  $rtype = 1;
} else if ($_REQUEST["type"]=="galaxy") {
  $rtype = 2;
} else if ($_REQUEST["type"]=="alliance") {
  $rtype = 3;
}

$q = "SELECT COUNT(*) FROM planet WHERE score>='$myrow[score]' AND mode != 0";
$result = mysqli_query($db, $q);
$rank = mysqli_fetch_row ($result);
$rank = (int) $rank[0];

$mygalrank = 0;
$gal_rank = get_universe_galaxy_report ($mygalrank);

if (($rank > 40 && $rtype == 1) || 
    ($mygalrank > 0 && $rtype == 2)) {
  echo "<table width=\"650\" border=\"1\" cellpadding=\"5\">\n";

  if ($rank > 40 && $rtype == 1) {
    echo "<tr><td>You are currently ranked #$rank</td></tr>\n";
  }
  if ($mygalrank >0 && $rtype == 2) {  
    echo "<tr><td>Your galaxy is currently ranked #$mygalrank</td></tr>\n";
  }

  echo "</table>\n<br>\n";
}

if ($rtype == 1) {

  echo <<<EOF
<table width="650" border="1">
  <tr><th colspan="7" class="a">Top 40 Player Ranking</tr>
  <tr><th width="40">Rank</th>
      <th width="155">Leader</th>
      <th width="205">Planet</th>
      <th width="70">Coords</th>
      <th width="135">Score</th>
      <th width="45">Roids</th>
      <td style="visibility:hidden"></td></tr>
EOF;

  print_universe_player_report ($db);
  echo "</table>\n";
} else if ($rtype == 2) {

echo <<<EOF
<table width="650" border="1">
  <tr><th colspan="5" class="a">Galaxy Ranking</tr>
  <tr><th width="40">Rank</th>
      <th width="280">Galaxy</th>
      <th width="60">Coords</th>
      <th width="140">Score</th>
      <th width="40">Roids</th></tr>
EOF;

  print_universe_galaxy_report ($gal_rank);
  echo "</table>\n";
} else if ($rtype == 3) {

echo <<<EOF
<table width="650" border="1">
  <tr><th colspan="5" class="a">Alliance Ranking</tr>
  <tr><th width="40">Rank</th>
      <th width="60">Tag</th>
      <th width="200">HC Name</th>
      <th width="40">Members</th>
      <th width="120">Avarage Score</th></tr>
EOF;

  print_universe_alliance_report ();
  echo "</table>\n";
}

require "footer.php";
?>
