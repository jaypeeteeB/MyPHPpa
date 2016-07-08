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

$close_script="<SCRIPT LANGUAGE=\"javascript\">\n".
"<!--\n".
"// Begin\n".
"function wclose() {".
"  this.close();".
"}\n// END\n//-->\n</SCRIPT>\n";

$extra_header = "   <TITLE>Scan Window</TITLE>\n$close_script";

require "standard_pop.php";
require "planet_util.inc";
require "news_util.php";

require "scan_util_2.inc";

$msg = "";
$save_link = "";
if (ISSET($_REQUEST["scan"]) && ISSET($_REQUEST["number"]) 
    && $_REQUEST["x"] && $_REQUEST["y"] && $_REQUEST["z"]) {
  $scan= (int) $_REQUEST["scan"];
  $x = (int) $_REQUEST["x"];
  $y = (int) $_REQUEST["y"];
  $z = (int) $_REQUEST["z"];
  $number = (int) $_REQUEST["number"];

  echo "<br>\n";
  $number = ($number>1000?1000:$number);
  $reach = scan_target ($scan, $x, $y, $z, $number);
  echo "<center>";
  if ($reach && $scan != 7) $save_link = 
     "<a href=\"".$_SERVER['PHP_SELF']."?save=$scan&x=$x&y=$y&z=$z\">Save scan</a>";
} else if (ISSET($_REQUEST["save"]) && $_REQUEST["x"] && $_REQUEST["y"] && $_REQUEST["z"] ) {
  $save = $_REQUEST["save"];
  $x = (int) $_REQUEST["x"];
  $y = (int) $_REQUEST["y"];
  $z = (int) $_REQUEST["z"];
  $tid = get_id ($x, $y, $z);

  if ($tid) {

    $q = "SELECT id,data FROM journal WHERE planet_id='$Planetid' ".
       "AND target_id='$tid' AND type='$save'"; // AND hidden=1
    $result = mysqli_query ($db, $q );

    if ($result && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_row($result);
      echo "<br>\n$row[1]";
      mysqli_query ($db, "UPDATE journal SET hidden=0 WHERE id=$row[0]" );
    }
  }
} else {
  $msg = "Submission failure!";
}

if ($msg != "") {
  echo "<center><table width=\"650\" border=\"1\" cellpadding=\"10\">".
      "<tr><td><font color=\"red\"><b>$msg</b></font></td></tr></table>";
}

echo <<<EOF
<table width="650" border="0"><tr><td align="left">$save_link</td>
<td align="right"><a href="javascript:close()">Close this Window</a></td>
</tr></table>
EOF;

require "footer.php";
?>
