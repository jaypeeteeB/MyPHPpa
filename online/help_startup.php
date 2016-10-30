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

$imgpath = "true";
require "header.php";
my_header("",0,0);

require "msgbox.php";

titlebox("Startup");
?>
<center>
<table border=0 width="650">
<tr><td>Nov. 2002 by Olrik<br>
</td></tr>
</table>

<table border=0 width="650">
<tr class="a"><th>10 steps to get your planet started
</th></tr>
<tr><td>
<br>
<b>First initiate at least 2 roids</b> (look at #9)
<br>
<br>
<b>1)</b> goto the <b>"Construction"</b> screen and build the <b>"Mining Center"</b><br>
<b>2)</b> goto the <b>"Research"</b> screen to research the <b>"Energy Patterns"</b><br>
<b>3)</b> goto the <b>"Resource"</b> screen to initiate <b>3 Crystal roids</b><br>
<br>
<em>You have to wait a few ticks</em><br>
<br>
<b>4)</b> construction screen again to build the <b>"Scope Amplifier"</b><br>
<b>5)</b> research screen to research the <b>"Crystal Extraction"</b><br>
<br>
<em>wait a few ticks again</em><br>
<br>
<b>6)</b> construction screen to build the <b>"Crystal Refinery"</b><br>
<b>7)</b> research screen to research the <b>"Resource Signatures"</b><br>
<br>
<em>the <b>"Resource Signatures"</b> is prolly the most important research in 
them as you need it to build <b>"Asteroid Scans"</b> when your <b>"News"</b>
show you that the <b>"Resource Signatures"</b> is finished you should</em><br>
<br>
<b>8)</b> goto the <b>"Waves"</b> screen to make new <b>"Wave Amplifier"</b> 
and <b>"Asteroid Scan"</b> to gain new Asteroids (to gain more resources).
build about 3 <b>"Wave Amplifier"</b> (to make your scans more succesfull) 
for every "Asteroid Scan".<br>
<br>
<em>after 6 ticks your first <b>"Asteroid Scan"</b> are ready and you should 
launch them.</em><br>
<br>
<b>9)</b> goto the resource screen to initiate your roids 
(initiate = make them producing new resources)<br>
<br>
<em>You should initiate the first 14 roids (short form of Asteroids) in the 
following way:<br>
<br>
1 C, 2 C, 3 C (the 3 roids you have at the start), 4 E, 5 C, 6 E, 7 C, 
8 C, 9 E, 10 C, 11 E, 12 C, 13 C, 14 C.
(M = Metal roid, C = Crystal roid, E = Eonium roid).<br>
<br>
the 15th up to the 50th roid should go into M roids.</em><br>
<br>
<b>10)</b> now you should make the decision what you want to 
research/construct first.<br>
10a) bigger ships (beyond DE class) is usually not worth it as you 
wont have the resources to build them in bigger numbers.<br>
10b) ETA researches are very important at the start if you playing with 
your m8s or and alliance coz they lower your eta so you can send defense 
faster.<br>
10c) PDS research is the most important if your playing solo. a good pds 
mix (neurons and ions are the most important) guarantees you a night 
with alot of sleep and no incomings.<br>
10d) the resources research are always worth it but at the start the 
other const/res are more important.<br>
<br>
<br>
<b>Finally:</b> initiate up to 100-200 roids and start attacking 
(with astropods) after that go gain new roids.
</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td align=left>Nov. 2002 by Olrik</td></tr>
</table>

</center>

<?php
require "footer.php";
?>
