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

include_once "session.inc";

session_init();
session_kill();

// setcookie("Username","");
// setcookie("Password","");
// setcookie("Planetid","-1");
// setcookie("mysession","");

$Planetid = -1;
$Username = "";
$Password = "";
$Valid = 0;
$imgpath="img";
require "header.php";

$topscript="<script type=\"text/javascript\">\n".
  "<!--\n if(top!=self)\n top.location=self.location;\n".
  "//-->\n </script>\n";

my_header($topscript);
?>

<center>
<br>
<img src="img/logo.jpg" width="290" height="145">
<br><br>

There seems to be an ERROR - perhaps your session timed out (30 mins)<br>
Please try to <a href="index.php">Login</a> again<br>
</center>

<?php

require "footer.php";

?>
