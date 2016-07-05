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

$close_script="<SCRIPT LANGUAGE=\"text/javascript\">\n".
"<!--\n".
"// Begin\n".
"function wclose() {".
"  this.close();".
"}\n// END\n//-->\n</SCRIPT>\n";


if (ISSET($_REQUEST["reply"])) {
     $extra_header = "   <TITLE>Reply Message</TITLE>\n$close_script";
} else if (ISSET($_REQUEST["forward"])) {
     $extra_header = "   <TITLE>Forward Message</TITLE>\n$close_script";
} else if (ISSET($_REQUEST["cluster"])) {
  if (ISSET($_REQUEST["gal"])) {
    $extra_header = "   <TITLE>Galaxy Message</TITLE>\n$close_script";
  } else {
    $extra_header = "   <TITLE>Cluster Message</TITLE>\n$close_script";
  }
} else if (ISSET($_REQUEST["hc"])) {
  $extra_header = "   <TITLE>HC Alliance Message</TITLE>\n$close_script";
} else if (ISSET($_REQUEST["alc"])) {
  $extra_header = "   <TITLE>Alliance Member Message</TITLE>\n$close_script";
} else {
     $extra_header = "   <TITLE>New Message</TITLE>\n$close_script";
}
require "standard_pop.php";

if (ISSET($_REQUEST["submit"])) {
  require "post_func.inc";
  check_post();
}

require "planet_util.inc";

$moc=0;
if (ISSET($_REQUEST["cluster"])) {
  $q = "SELECT moc FROM galaxy WHERE x=$myrow[x] AND y=$myrow[y] AND moc=$Planetid";
  $res = mysqli_query ($db, $q );
  if (mysqli_num_rows($res) != 1 && $Planetid!=1) {
    $moc=0;
  } else {
    $moc=1;
  }
}

$msg = "";
include "nreal_send_message.php";

echo "<b>Messages</b>\n<br>\n<center>\n";

if ($msg != "") {
  echo $msg;
} else {
  echo "<br>";
}

send_message_form(500, $moc);
?>

<table width="500" border="0"><tr><td align="right">
<a href="javascript:close()">Close this Window</a></td></tr></table>

<?php

require "footer.php";
?>
