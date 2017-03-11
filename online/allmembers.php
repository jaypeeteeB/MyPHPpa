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
require "news_util.php";
include_once "alliance_func.inc";

$msg = "";

$all = get_alliance ();

if ($all && ISSET($_GET["otrust"]) && 0 != (int)$_GET["otrust"])
  $msg .= trust_member($otrust);

if ($all && ISSET($_GET["okick"]) && 0 != (int)$_GET["okick"] && $_GET["okick"] != $Planetid)
  $msg .= kick_alliance($_GET["okick"]);

require_once "navigation.inc";

echo "<div id=\"main\">\n";
/* top table is written now */
top_header($myrow);

titlebox("Alliance", $msg);

echo "<center>\n";

if (!$all || $myrow["alliance_id"] == 0) {

  echo "<table border=\"1\" width=\"650\" cellpadding=\"2\">\n".
       "<tr><td><span class=\"red\">You arent Member of an alliance</span>".
      "</td></tr></table>\n";

} else {

  if ($all["status"])
    list_alliance_members($all);
  else
    untrusted_msg();
}

?>
</center>
</div>

<?php
require "footer.php";
?>
