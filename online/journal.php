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
require_once "planet_util.inc";

if (ISSET($_GET["clear"]) && $_GET["clear"] != "") {
  mysqli_query ($db, "DELETE LOW_PRIORITY FROM journal ".
    "WHERE planet_id='$Planetid'" );
}

if (ISSET($_GET["hide"]) && $_GET["hide"] != 0) {
  $hide = (int) $_GET["hide"];
  mysqli_query ($db, "DELETE LOW_PRIORITY FROM journal ".
    "WHERE planet_id='$Planetid' AND target_id='$hide'" );
}

/* top table is written now */
top_header($myrow);

$msg = "";
if (ISSET($_GET["clear"]) && $_GET["clear"] != "") {
  $msg = "Journal set to clear\n<br>\n";
}

titlebox("Journal", $msg);
?>
<center>

<?php
if (ISSET($_REQUEST["tid"]) && $_REQUEST["tid"]>0) {
  $tid = $_REQUEST["tid"];
  $who = get_coord_name ($tid);

  echo <<<EOF
<div class=cent>
<div class=st>
<table width="650" border="1" cellpadding="2">
<tr><th colspan="4" class="a">Private Journal - $who[leader] 
of $who[planetname] ($who[x]:$who[y]:$who[z])</th></tr>
<tr>
  <td width=15%>&nbsp;</td>
  <td align=center border=0 width=35%>
    <a href="$_SERVER[PHP_SELF]">Overview</a></td>
  <td align=center order=0 width=35%>
     <a href="$_SERVER[PHP_SELF]?hide=$tid">Clear</a></td>
  <td align=center border=0 width=15%>
     <a href="galaxy.php?submit=1&x=$who[x]&y=$who[y]">
$who[x]:$who[y]</a></td></tr>
</table>
EOF;

  $q = "SELECT type,tick,date,data FROM journal WHERE planet_id='$Planetid' ".
       "AND target_id='$tid' AND hidden=0";

  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_row($result)) {
      echo "<br>\n$row[3]\n";
    }
  } else {
    echo "<table width=650><tr><td>No data available.</td></tr></table>\n";
  }
  echo "</div></div>\n";
} else {

  echo <<<EOF
<div class="st">
<table  width="650" border="1" cellpadding="2" >
<tr><th class="a" colspan="5">Private Journal Overview</th></tr>
<tr><td align=center colspan="5">
<a href="$_SERVER[PHP_SELF]?clear=1">Clear all entries</a></td></tr>
<tr><td style="visibility:hidden;border:0px"></td></tr>
EOF;
  echo "<tr><th class=\"a\" width=60><a href=\"".$_SERVER['PHP_SELF']."?coords=";
  if (ISSET($_GET["coords"]) && $_GET["coords"]==1) echo "2";
  else echo "1";
  echo "\">Coords</a></th>\n<th class=\"a\">Target</th>\n".
       "<th class=\"a\" width=50>Count</th>\n".
       "<th class=\"a\" width=60>Type</th>".
       "<th class=\"a\" width=100><a href=\"".$_SERVER['PHP_SELF']."?date=";
  if (ISSET($_GET["date"]) && $_GET["date"]==1) echo "2";
  else echo "1";
  echo "\">Last</a></th>\n";

  $q = "SELECT target_id, count(*), sum(type), ".
     "date_format(max(date),'%D %b %H:%i'), max(date) as d ".
     "FROM journal WHERE planet_id='$Planetid' ".
     "AND hidden=0 GROUP BY target_id";

  if (ISSET($_GET["date"])) {
    $q .= " ORDER BY d ";
    if ($_GET["date"]==1) $q .= "DESC";
    else $q .= "ASC";
  } else if (ISSET($_GET["coords"])) {
    $q = "SELECT target_id, count(*), sum(type), ".
       "date_format(max(journal.date),'%D %b %H:%i'), ".
       "max(journal.date) as d ".
       "FROM journal, planet WHERE planet_id='$Planetid' ".
       "AND planet.id=target_id ".
       "AND hidden=0 GROUP BY target_id ORDER BY ";
     if ($_GET["coords"]==1) $q .= "x ASC, y ASC, z ASC";
     else $q .= "x DESC, y DESC, z DESC";
  }

  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_row($result)) {

      $who = get_coord_name ($row[0]);

      echo "<tr><td align=center>".
        "<a href=\"galaxy.php?submit=1&x=$who[x]&y=$who[y]\">".
        "$who[x]:$who[y]:$who[z]</a></td>".
        "<td><a href=\"".$_SERVER['PHP_SELF']."?tid=$row[0]\">".
        "$who[leader] of $who[planetname]</a></td>".
        "<td align=center>$row[1]</td>";

      $type = 0;
      $i = $row[2];
      if (((int)($i/2))*2 != $i) {
        $i -= 5;
        $type |= 4;
      }
      if ($i >6) {
        $i -= 8;
        $type |= 8;
      }
      if ($i >2) {
        $i -= 4;
        $type |= 2;
      }
      if ($i > 0) {
        $i -= 2;
        $type |= 1;
      }
      echo "<td>";
      if ($type & 1) echo "S&nbsp;";
      if ($type & 2) echo "U&nbsp;";
      if ($type & 4) echo "P&nbsp;";
      if ($type & 8) echo "M&nbsp;";
      echo "</td><td>$row[3]</td></tr>";
    }
  } else {
    echo "<tr><td colspan=5>No Entries</td></tr>";
  }
  echo "</table>\n</div>\n";
}
?>

</center>
<?php
require "footer.php";
?>
