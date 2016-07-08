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

function pval ($val) {
  return number_format($val, 0, ",", ".");
}

$mysettings=0;
// require_once "../alliance_func.inc";
require_once "../forum.inc";


if (ISSET($_REQUEST["fthread"]) && $Planetid<=2) {
  $res = mysqli_query($db, "SELECT gal_id FROM politics ".
        "WHERE id='$fthread'" );
  $row = mysqli_fetch_row($res);
  $galid = $row[0];

  $fthread = $_REQUEST["fthread"];
}

echo "<center>\n";
if (!ISSET($galid) && ISSET($_REQUEST["galid"]) && $Planetid<=2)
  $galid = $_REQUEST["galid"];


if (ISSET($galid) && $Planetid<=2) {

  // buggy. need $myrow[x] / [y] to be from gal.

  $fstyle = 1; 
  forum_init ($fstyle, $galid);

  forum_submit ($fstyle, $galid, $fthread);

  if (ISSET($fthread)) {
      forum_show_thread ($fstyle, $galid, $fthread);
  } else {
      forum_list_thread ($fstyle, $galid);
  }


} else {
  $q = "SELECT x,y,name,id,members FROM galaxy ".
       "WHERE members>0 ORDER by x,y";
  $res = mysqli_query ($db, $q );
  $n = mysqli_num_rows($res);
  if ($res && $n>0) {
    echo <<<EOF
<table width="650" border="1">
<tr class="a"><th colspan="3">Galaxy Politics ($n Gals)</th></tr>
<tr class="a"><th width="80">Coords</th>
   <th class="a" width="80">Members</th><th class="a">Name</th></tr>
EOF;
    while ($row=mysqli_fetch_row($res)) {
      echo "<tr><td align=center>($row[0]:$row[1])</td>".
           "<td align=center>$row[4]</td><td>".
           "<a href=".$_SERVER['PHP_SELF']."?galid=$row[3]>$row[2]</a></td></tr>\n";
    }
    echo "</table>\n";
  } else {
    echo "No data found.";
  }
}

echo "</center>\n";

require_once "../footer.php";
?>
