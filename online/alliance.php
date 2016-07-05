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

require "popup_header.inc";

require "standard.php";
require "planet_util.inc";
require "news_util.php";
include_once "alliance_func.inc";

$msg = "";

$all = get_alliance ();

if (ISSET($_POST["ncreate"]) && $myrow["alliance_id"] == 0) 
  $msg .= create_alliance ($_POST["nhc"], $_POST["ntag"], $_POST["nname"]);

if (ISSET($_POST["ojoin"]) && ISSET($_POST["osecret"]) && $_POST["osecret"]!="") 
  $msg .= join_alliance ($_POST["osecret"]);

if (ISSET($_POST["oquit"]))
  $msg .= leave_alliance();

if (ISSET($_POST["osec"]))
  $msg .= change_secret ();

if (ISSET($_POST["odel"])) 
  $msg .= delete_alliance();

if (ISSET($_POST["oela"]) && ISSET($_POST["offa"]))
  $msg .= elect_offa($_POST["offa"]);

/* top table is written now */
top_header($myrow);

titlebox("Alliance", $msg);

echo "<center>\n";

if (!$all || $myrow["alliance_id"] == 0) {

  create_menu();
  echo "<br>\n";
  join_menu();

} else {

  print_alliance_status ($all);

  if ($all && $all["offa"] == $myrow["id"]) {
    off_menu ();
  } 
  if ($all && $all["hc"] == $myrow["id"]) {
    hc_menu ();
  } 

}
?>
</center>

<?php
require "footer.php";
?>
