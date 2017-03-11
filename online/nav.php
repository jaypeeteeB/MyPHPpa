<?php

/*
 * MyPHPpa
 * Copyright (C) 2016 Jens Beyer
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

require "auth_check.php";

require "options.php";

include_once "session.inc";
session_init();
if (session_check()) {
  echo "error check session";
  Header("Location: index.php");
  die;
 }
pre_auth($Username,$Password,$Planetid,$_COOKIE["Valid"]);

require "dblogon.php";
db_auth($db,$Username,$Password,$Planetid);

// currently not used and disabled
require_once "mobile.inc";

echo "<LINK rel=stylesheet type=\"text/css\" href=\"mpb.css\">";
/* 
 * styling and local data caching
 * old style....
if (ISSET($imgpath) && $imgpath != "") {
  echo "<html>\n<head>\n";
  if ($mobile_detect) {
    echo "   <LINK rel=stylesheet type=\"text/css\" href=\"mobile.css\">";
  } else {
    if ($mysettings&32)
      echo "<LINK rel=stylesheet type=\"text/css\" href=\"npb.css\">";
    else
      echo "<LINK rel=stylesheet type=\"text/css\" href=\"mpb.css\">";
  }
} else {
  echo "<html>\n<head>\n";
  if ($mysettings&32)
    echo "<LINK rel=stylesheet type=\"text/css\" href=\"npa.css\">";
  else
    echo "<LINK rel=stylesheet type=\"text/css\" href=\"mpa.css\">";
}
*/

echo <<<EOF
</head>
<body class="nav">
EOF;


require_once "navigation.inc";

?>

</body>
</html>

