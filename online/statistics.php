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
require "planet_util.inc";

/* top table is written now */

top_header($myrow);
titlebox("Statistics");
?>
<center>

<?php

$q = "SELECT name,SUM(num) FROM unit_class, units ".
"WHERE units.unit_id=unit_class.id GROUP BY units.unit_id";

$unit_res = mysqli_query ($db, $q );

if ($unit_res && mysqli_num_rows ($unit_res) > 0) {

  echo "<br>\n";

  $total = 0;
  $table = "";
  $row_counter = 0;

  while ($unit_row = mysqli_fetch_row ($unit_res)) {
    if ( ($row_counter % 2) == 0) $table .= "<tr>";
    $table .= "<td width=\"20%\">$unit_row[0]</td>" .
              "<td width=\"20%\" align=\"right\">".pval($unit_row[1])."</td>";
    if ( ($row_counter % 2) == 1) $table .= "</tr>\n";
    else $table .= "<td width=\"20%\">&nbsp;</td>";
    $total += $unit_row[1];
    $row_counter++;
  }

  if ($total) {
    if ( ($row_counter % 2) == 1) 
      $table .= "<td width=\"40%\" colspan=\"2\"></td></tr>";
    echo "<table width=\"650\" border=\"1\" cellpadding=\"5\" >".
      "<th class=\"a\" colspan=\"5\">" .
      "Ships (".pval($total)." units total)</th></tr>\n$table";
    echo "</table>";
  }
}

$q = "SELECT name,SUM(num) FROM unit_class, pds ".
     "WHERE pds.pds_id=unit_class.id AND unit_class.class=5 ".
     "GROUP BY pds.pds_id";

$pds_res = mysqli_query ($db, $q );

if ($pds_res && mysqli_num_rows ($pds_res) > 0) {

  echo "<br>\n";

  $total = 0;
  $table = "";
  $row_counter = 0;

  while ($pds_row = mysqli_fetch_row ($pds_res)) {
    if ( ($row_counter % 2) == 0) $table .= "<tr>";
    $table .= "<td width=\"20%\">$pds_row[0]</td>" .
              "<td width=\"20%\" align=\"right\">".pval($pds_row[1])."</td>";
    if ( ($row_counter % 2) == 1) $table .= "</tr>\n";
    else $table .= "<td width=\"20%\">&nbsp;</td>";
    $total += $pds_row[1];
    $row_counter++;
  }

  if ($total) {
    if ( ($row_counter % 2) == 1) 
      $table .= "<td width=\"40%\" colspan=\"2\"></td></tr>";
    echo "<table width=\"650\" border=\"1\" cellpadding=\"5\" >".
      "<th class=\"a\" colspan=\"5\">" .
      "Planetarian Defence System (".pval($total)." units total)</th></tr>\n$table";
    echo "</table>";
  }
}


$q = "SELECT SUM(metalroids),SUM(crystalroids),SUM(eoniumroids),".
     "SUM(uniniroids) FROM planet";

$roid_res = mysqli_query ($db, $q );

if ($roid_res && mysqli_num_rows ($roid_res) > 0) {

  echo "<br>\n";
  $roid_row = mysqli_fetch_row ($roid_res);

  $total = $roid_row[0] + $roid_row[1] + $roid_row[2]  
         + $roid_row[3];

  echo "<table width=\"650\" border=\"1\" cellpadding=\"5\" >".
      "<th class=\"a\" colspan=\"5\">" .
      "Asteroids (".pval($total)." total)</th></tr>";

  echo "<tr>";
  echo "<td width=\"20%\">Metal</td><td width=\"20%\" align=\"right\">".
      pval($roid_row[0]) . "</td><td width=\"20%\">&nbsp;</td>";
  echo "<td width=\"20%\">Crystal</td><td width=\"20%\" align=\"right\">".
      pval($roid_row[1]) . "</td></tr>\n";
  echo "<td width=\"20%\">Eonium</td><td width=\"20%\" align=\"right\">".
      pval($roid_row[2]) . "</td><td width=\"20%\">&nbsp;</td>";
  echo "<td width=\"20%\">Uninitiated</td><td width=\"20%\" align=\"right\">".
      pval($roid_row[3]) . "</td></tr>\n";

  echo "</table>\n";
}

$q = "SELECT SUM(metal),SUM(crystal),SUM(eonium) ".
     "FROM planet";

$res_res = mysqli_query ($db, $q );

if ($res_res && mysqli_num_rows ($res_res) > 0) {

  echo "<br>\n";
  $res_row = mysqli_fetch_row ($res_res);

  $total = $res_row[0] + $res_row[1] + $res_row[2];

  echo "<table width=\"650\" border=\"1\" cellpadding=\"5\" >".
      "<th class=\"a\" colspan=\"5\">" .
      "Resources (".pval($total)." total)</th></tr>";

  echo "<tr>";
  echo "<td width=\"20%\">Metal</td><td width=\"20%\" align=\"right\">".
      pval($res_row[0]) . "</td><td width=\"20%\">&nbsp;</td>";
  echo "<td width=\"20%\">Crystal</td><td width=\"20%\" align=\"right\">".
      pval($res_row[1]) . "</td></tr>\n";
  echo "<td width=\"20%\">Eonium</td><td width=\"20%\" align=\"right\">".
      pval($res_row[2]) . "</td><td width=\"20%\">&nbsp;</td>";
  echo "<td width=\"40%\" colspan=\"2\"></td></tr>\n";

  echo "</table>\n";
}

$attacks = 0;
$defends = 0;

$q = "SELECT count(*) FROM logging WHERE class=4 AND type=2 ".
     "AND NOW() - INTERVAL 1 HOUR  < stamp";

$res = mysqli_query ($db, $q );
if ($res && mysqli_num_rows ($res) > 0) {
  $row = mysqli_fetch_row ($res);
  $attacks = $row[0];
}

$q = "SELECT count(*) FROM logging WHERE class=4 AND type=4 ".
     "AND NOW() - INTERVAL 1 HOUR  < stamp";

$res = mysqli_query ($db, $q );
if ($res && mysqli_num_rows ($res) > 0) {
  $row = mysqli_fetch_row ($res);
  $defends = $row[0];
}

if ($defends!=0 || $attacks !=0) {
  echo "<br>\n";
  echo "<table width=\"650\" border=\"1\" cellpadding=\"5\" >".
      "<th class=\"a\" colspan=\"5\">" .
      "Fleet Movements (".($attacks+$defends)." total) in last hour</th></tr>";

  echo "<tr>";
  echo "<td width=\"20%\">Attacks</td><td width=\"20%\" align=\"right\">".
      $attacks . "</td><td width=\"20%\">&nbsp;</td>";
  echo "<td width=\"20%\">Defences</td><td width=\"20%\" align=\"right\">".
      $defends . "</td></tr>\n";
  
  echo "</table>\n";
}

echo "</center>\n";

require "footer.php";
?>
