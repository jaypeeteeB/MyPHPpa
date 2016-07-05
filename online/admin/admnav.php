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

//require_once "admhead.php";
require_once "../auth_check.php";

require_once "../options.php";
include_once "../get_ip.php";

include_once "../session.inc";
session_init();
if (session_check(get_ip())) {
  echo "error check session admnav";
//  Header("Location: ../index.php");
  die;
 }

// Hack
$Valid =  md5($round);

#echo ">>$Username $Password $Planetid $Valid " .  md5($round) ."<<";
#die;

pre_auth($Username,$Password,$Planetid,$Valid, "../index.php");

require_once "../dblogon.php";

db_auth($db,$Username,$Password,$Planetid);

if ($Planetid>2) {
  Header("Location: ../overview.php");
  die;
}

require_once "../header.php";

if (ISSET($extra_header)) {
  my_header($extra_header,0);
} else {
  my_header("",0);
}

mysqli_query($db, "UPDATE user set last=NOW(),last_tick='$mytick'".
	     "ip='$_SERVER[REMOTE_ADDR]' ".
	     "WHERE planet_id='$Planetid'"); 

if ($Planetid==1) {
  ?>
 <center>
    <table border="1" width="640">
    <tr>
    <th colspan="5"> Administration </th>
    <tr>
    <td width="20%"><a href="pinfo.php" target="admmain">Player Info</a></td>
    <td width="20%"><a href="plog.php" target="admmain">Player Log</a></td>
    <td width="20%"><a href="pban.php" target="admmain">Ban Player</a></td>
    <td width="20%"><a href="pmail.php" target="admmain">Player Mail</a></td>
    <td width="20%"><a href="pnews.php" target="admmain">Planet News</a></td>
    </tr>
    <tr>
    <td width="20%"><a href="apol.php" target="admmain">Politics</a></td>
    <td width="20%"><a href="aalist.php" target="admmain">Alliances</a></td>
    <td width="20%"><a href="amem.php" target="admmain">A Members</a></td>
    <td width="20%"><a href="afor.php" target="admmain">A Forum</a></td>
    <td width="20%"><a href="ptop.php" target="admmain">Player Top</a></td>
    </tr>
    <tr>
    <td width="20%"><a href="pdelete.php" target="admmain">Delete Player</a></td>
    <td width="20%"><a href="ipban.php" target="admmain">Ban IP</a></td>
    <td width="20%"><a href="pidle.php" target="admmain">Idle New</a></td>
    <td width="20%"><a href="pidle2.php" target="admmain">Idle old</a></td>
    <td width="20%"><a href="pmove.php" target="admmain">Player move</a></td>
    <tr>
    </tr>
    <td width="20%"><a href="scan.php" target="admmain">Scans</a></td>
    <td width="20%"><a href="units.php" target="admmain">Units</a></td>
    <td width="20%"><a href="rc.php" target="admmain">Res/Con</a></td>
    <td width="20%"><a href="high.php" target="admmain">Set Highscore</a></td>
    <td width="20%"><a href="opt.php" target="admmain">Optimize</a></td>
    </tr>
    <tr>
    <td width="20%"><a href="galpic.php" target="admmain">Galpic</a></td>
    <td width="20%"><a href="pshuffle.php" target="admmain">Shuffle</a></td>
    <td width="20%"><a href="freset.php" target="admmain">Reset All</a></td>
    <td width="20%"><a href="havoc.php" target="admmain">Havoc</a></td>
    <td width="20%"><a href="expand.php" target="admmain">Expand</a></td>
    </tr>
  
    </table>
    </center>
    <hr>
    <?php
    } else {
  ?>
 <center>
    <table border="1" width="640">
    <tr>
    <th colspan="5"> Administration </th>
    <tr>
    <td width="20%"><a href="pinfo.php" target="admmain">Player Info</a></td>
    <td width="20%"><a href="plog.php" target="admmain">Player Log</a></td>
    <td width="20%"><a href="pidle.php" target="admmain">Idle New</a></td>
    <td width="20%"><a href="pidle2.php" target="admmain">Idle old</a></td>
    <td width="20%"><a href="ipban.php" target="admmain">Ban IP</a></td>
    </tr>
    <tr>
    <td width="20%"><a href="apol.php" target="admmain">Politics</a></td>
    <td width="20%"><a href="aalist.php" target="admmain">Alliances</a></td>
    <td width="20%"><a href="amem.php" target="admmain">A Members</a></td>
    <td width="20%"><a href="afor.php" target="admmain">A Forum</a></td>
    <td width="20%"><a href="ptop.php" target="admmain">Player Top</a></td>
    </tr>

    </table>
    </center>
    <hr>

    <?php
    }
echo "</center></body>";
require_once "../footerf.php";
?>
