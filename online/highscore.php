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

  require_once "navigation.inc";
  echo "<div id=\"main\">\n";

/* top table is written now */
top_header($myrow);

titlebox("Hall Of Fame");

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

if (!ISSET($_GET["type"])){
  $rtype = 1;
} else if ($_GET["type"]=="galaxy") {
  $rtype = 2;
} else if ($_GET["type"]=="alliance") {
  $rtype = 3;
}

if ($rtype == 1) {

echo <<<EOF
<table width="650" border="1">
  <tr><th colspan="7" class="a">Winning planet of past rounds</tr>
  <tr><th width="40">Round</th>
      <th width="110">Leader</th>
      <th width="160">Planet</th>
      <th width="60">Coords</th>
      <th width="110">Score</th>
      <th width="50">Roids</th>
      <th width="70">Date</th>
  </tr>
EOF;

$q = "SELECT round,leader,planetname,coords,score,roids,".
     "date_format(date,'%e %b %y') FROM highscore ORDER BY round DESC";
$res = mysqli_query($db, $q );

if ($res && mysqli_num_rows($res)>0) {

  while( ($row = mysqli_fetch_row($res)) ) {

     echo "<tr><td align=\"right\">$row[0]</td>".
       "<td>$row[1]</td>".
       "<td>$row[2]</td>".
       "<td align=\"center\">$row[3]</td>".
       "<td align=\"right\">".pval($row[4])."</td>".
       "<td align=\"right\">$row[5]</td>".
       "<td align=\"right\">$row[6]</td></tr>";
  }
}

echo <<<EOF
<tr><td colspan="7">Sorry, old highscores have been deleted.</td></tr>
<tr><td colspan="7">Round 2:  Queen of the Universe
</td>
</tr>
</table>
EOF;

} else if ($rtype == 2) {

echo <<<EOF
<table width="650" border="1">
  <tr><th colspan="6" class="a">Winning galaxy of past rounds</tr>
  <tr><th width="40">Round</th>
      <th width="240">Galaxy</th>
      <th width="60">Coords</th>
      <th width="120">Score</th>
      <th width="70">Roids</th>
      <th width="70">Date</th>
  </tr>
EOF;

$q = "SELECT round,galname,coords,score,roids,".
     "date_format(date,'%e %b %y') FROM highscore_gal ORDER BY round DESC";
$res = mysqli_query($db, $q );

if ($res && mysqli_num_rows($res)>0) {

  while( ($row = mysqli_fetch_row($res)) ) {

     echo "<tr><td align=\"right\">$row[0]</td>".
       "<td>$row[1]</td>".
       "<td align=\"center\">$row[2]</td>".
       "<td align=\"right\">".pval($row[3])."</td>".
       "<td align=\"right\">$row[4]</td>".
       "<td align=\"right\">$row[5]</td></tr>";
  }
}

echo "</table>\n";
} else {
  // Alliance

echo <<<EOF
<table width="650" border="1">
  <tr><th colspan="8" class="a">Best performing Alliances of past rounds</tr>
  <tr>
   <th width="40">Round</th>
   <th width="40">Tag</th>
   <th width="120">HC Name</th>
   <th width="40">Members</th>
   <th width="120">Avg. Score</th>
   <th width="70">Avg. Roids</th>
   <th width="70">Date</th>
  </tr>
EOF;

$q = "SELECT round,tag,name,hcname,members,score,roids,".
     "date_format(date,'%e %b %y') as dat ".
     "FROM highscore_alliance ORDER BY round DESC";
$res = mysqli_query($db, $q );

if ($res && mysqli_num_rows($res)>0) {
  while( ($row = mysqli_fetch_array($res)) ) {
    $sc = pval ($row["score"]);
    echo <<<EOF
<tr>
 <td align="right">$row[round]</td>
 <td align="center">[$row[tag]]</td>
 <td>$row[hcname]</td>
 <td align="center">$row[members]</td>
 <td align="right">$sc</td>
 <td align="right">$row[roids]</td>
 <td align="right">$row[dat]</td>
</tr>
EOF;
  }
}
echo "</table>\n";
}

echo "</center>\n";
echo "</div>\n";
require "footer.php";
?>
