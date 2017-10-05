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

$imgpath="true";

require "header.php";
my_header("",0,0);

require "msgbox.php";

function fetch_name ($id) {
  global $db;

  $res = mysqli_query ($db, "SELECT name,type,block_id FROM rc_class WHERE id='$id'"); 
  if ($res) 
    $row = mysqli_fetch_row($res);

  return $row;
}

function rcell ($id) {
  if ($id==-1) {
    echo "<td></td>";
    return;
  }

  list($name,$type,$block) = fetch_name($id);
  if ($block != 0) 
    list($disable) = fetch_name($block);

  switch ($type) {
  case 1: echo "<td class=\"con\">"; break;
  case 0: echo "<td class=\"res\">"; break;
  }
  echo $name;

  if ($block != 0)
    echo "<br><b><span class=\"red\">Disables: $disable</span></b>";
  echo "</td>";
}

function rrow ($a_id) {

  echo "<tr>";
  reset ($a_id);
  $id = current($a_id);
  do {
    rcell ($id);
  } while ($id = next($a_id));
  echo "</tr>";
}

require_once "session.inc";
session_init();
$need_navigation=0;
if (!session_check())
  $need_navigation=1;

if($need_navigation == 1) {
  require_once "navigation.inc";
  echo "<div id=\"main\">\n";
}

titlebox("Res / Con");
?>
<center>
<table  class="std_nb" border="0" width="650" style="font-size: 12px;">
<tr class="a"><th colspan="7">Research paths</th></tr>
<tr class="b">
  <th width="108">War</th>
  <th width="108">Science</th>
  <th width="108" colspan="2">PDS</th>
  <th width="108">Waves</th>
  <th width="108">Traveltime</th>
  <th width="108">Mining</th>
</tr>

<?php
rrow (array(100,120,40,-1,60,30,1));
rrow (array(101,121,41,-1,61,31,2));
rrow (array(102,122,42,-1,62,32,3));
rrow (array(103,123,43,-1,63,33,4));
rrow (array(104,124,44,-1,64,34,5));
rrow (array(105,125,45,49,65,35,6));
rrow (array(106,126,46,50,66,36,7));
rrow (array(107,127,47,51,67,37,8));
rrow (array(108,128,48,52,68,-1,9));
rrow (array(109,129,-1,-1,69,-1,10));
rrow (array(110,130,-1,-1,70,-1,11));
rrow (array(-1,131,-1,-1,71,-1,12));
rrow (array(-1,-1,-1,-1,-1,-1,13));
rrow (array(-1,-1,-1,-1,-1,-1,14));
rrow (array(-1,-1,-1,-1,-1,-1,15));
rrow (array(-1,-1,-1,-1,-1,18,16));
rrow (array(-1,-1,-1,-1,-1,19,17));
rrow (array(-1,-1,-1,-1,-1,20,-1));
rrow (array(-1,-1,-1,-1,-1,21,-1));

?>
<tr><td>&nbsp;</td></tr>
<tr><td colspan="2" class="con">Construction</td>
    <td colspan="4" rowspan="2">Besides the first 3 Waves only "Constructions" leads to new Ships/Scans/PDS/Mining Facility or Traveltime reduction </td>
</tr>
<tr><td colspan="2" class="res">Research</td></tr>
<tr><td colspan="6">Since some of u asked: <b>Only Constructions 
in <em>WAR</em> or <em>SCIENCE</em> leads to new shiptypes</b><br>
To Scan new Asteroids you have to research till 
<b><em>Resource Signatures</em></b></td></tr>
</table>

</center>

<?php
if($need_navigation == 1)
  echo "</div>\n";

require "footer.php";
?>
