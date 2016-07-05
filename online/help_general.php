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

require "header.php";
my_header("",0,0);

require "msgbox.php";

titlebox("General");
?>
<center>
<table border=0 width="650">
<tr class="a"><th colspan=2>Rules</th></tr>
<tr><td><br></td></tr>
<tr><td valign="top">1.</td><td>This is a private fun game. <br>
This means: I make the rules
how and whenever I want. If You dont have fun leave the game.</td></tr>
<tr><td valign="top">2.</td><td>If I dont have fun anymore the game stops.</td></tr>
<tr><td valign="top">3.</td><td>Socalled multies arent allowed - account sharing is interpreted as multiing, as is account switching/hopping.</td></tr>
<tr><td valign="top">4.</td><td>Socalled bots, scripting and farming or donating roids, ships or salvage is forbidden.</td></tr>
<tr><td valign="top">5.</td><td>Spamming the Forum or via Mail isnt allowed.</td></tr>
<tr><td valign="top">6.</td><td>If you and friends or whatever play over
the same IP (LAN), you should send the Mod (1:1:2) or Admin (1:1:1) an ingame 
message  - multiple accounts from one IP as are those coming through 
web-anonymiser may be banned otherwise.</td></tr>
<tr><td valign="top">7.</td><td>Using offensive or insulting 
names/pictures/posts/mails/whatever may lead to direct deletion.</td></tr>
<tr><td colspan=2>&nbsp;</td></tr>
<tr><td colspan=2>As far as possible standard language is english.</td></tr>
<tr><td colspan=2>&nbsp;</td></tr>
<tr><td colspan=2><b>By logging into this game You accept these terms.</td></tr>
<tr><td colspan=2>&nbsp;</td></tr>
<tr><td colspan=2 align=left>Khan, 1. Nov 2002</td></tr>
<tr><td colspan=2 align=left><em>Last update: 1. July 2016</em></td></tr>
</table>

</center>

<?php
require "footer.php";
?>
