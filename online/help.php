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
require "header.php";
require "msgbox.php";

$imgpath="true";
my_header("",0,0);
titlebox("Help");
?>

<center>
<br>
<table border="0" width="650">
<tr><td align="center">I didnt write a lot manual page till now, but you might want to have a look at:</td></tr>
<tr><td align="center"><a href="help_general.php">General rules</a></td></tr>
<tr><td align="center"><a href="help_goal.php">Game goals</a></td></tr>
<tr><td align="center"><a href="help_stat.php">Military stats</a></td></tr>
<tr><td align="center"><a href="help_rc.php">Research tree</a></td></tr>
<tr><td align="center"><a href="help_startup.php">How to start successfully</a></td></tr>
<tr><td align="center"><a href="help_story.php">How did you get in here? The story!</a></td></tr>
<tr><td align="center"><br><a href="index.php">Back to login</a></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td align="center"><span class="red">To signup, login and play this game you need to enable cookies in your browser!</span></td></tr>
</table>
</center>
<?php
require "footer.php";
?>
