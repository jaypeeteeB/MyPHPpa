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

require_once "../alliance_func.inc";
require_once "../forum.inc";

echo "<center>\n";

if (ISSET($fthread) && $Planetid<=2) {
  $res = mysqli_query($db, "SELECT gal_id-1024 FROM politics ".
        "WHERE id='$fthread'");
  $row = mysqli_fetch_row($res);
  $allid = $row[0];
}

if (ISSET($allid) && $Planetid<=2) {

  $myrow["alliance_id"] = $allid;
  $myrow["status"] = 0;
  $all = get_alliance ();

  echo "<a href=\"aalist.php?allid=$allid\">Status</a>&nbsp;|&nbsp".
       "<a href=\"amem.php?allid=$allid\">Members</a>&nbsp;|&nbsp".
       "<a href=\"afor.php?allid=$allid\">Forum</a><br><br>\n";

  $fid = $all["id"] + 1024;
  $fstyle = 2; // alliance
  forum_init ($fstyle, $fid);

  forum_submit ($fstyle, $fid, $fthread);

  if (ISSET($fthread)) {
      forum_show_thread ($fstyle, $fid, $fthread);
  } else {
      forum_list_thread ($fstyle, $fid);
  }


} else {
  $ref = "$_SERVER['PHP_SELF']?allid=";
  list_alliances_admin($ref);
}

echo "</center>\n";

require_once "../footer.php";
?>
