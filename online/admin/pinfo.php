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

require_once "../popup_header.inc";
require_once "admhead.php";
require_once "admform.php";
require_once "../res_calc.php";

function pval ($val) {
  return number_format($val, 0, ",", ".");
}

function print_list_row ($row) {

  echo "<tr>";
  echo "<td>$row[login]</td>";
  echo "<td>$row[password]</td>";
  echo "<td>$row[ip]</td>";
  echo "<td><a href=\"". $_SERVER['PHP_SELF'] ."?submit=1&playerid=$row[id]\">$row[leader]</a></td>";
  if ($row["mode"]==0) {
    echo "<td><strike>$row[planetname]</strike>";
  } else {
    echo "<td>$row[planetname]";
    switch ($row["mode"]) {
	case 242:	echo "*"; break;
	case 2:	echo "*"; break;
	case 4: echo "#"; break;
    }
  }
  echo "</td>";
  echo "<td>$row[x]:$row[y]:$row[z]</td>";
  echo "<td>$row[id]</td></tr>";
}

if (ISSET($_REQUEST["submit"]) && ISSET($_REQUEST["playerid"]) && $_REQUEST["playerid"] !="") {
  $playerid = $_REQUEST["playerid"];
  $q = "SELECT leader FROM planet  WHERE id='$playerid'";
  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_row($result);
    $id = $playerid;
    $pleader = $row[0];
  } else {
    $id = -1;
  }
} else {
   $id = -1;
}
?>

<center>

<table  width="700" border="1" cellpadding="2" >
<tr>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
  <td align="center" class="a">Enter target leader:</td>
  <td><input type="text" name="playerid" size="25"></td>
  <td colspan="2"><input type=submit value="  Search  " name=submit></td>
</form>
</tr>
</table>

<br>

<?php

if ($id <0) {

  if (ISSET($order) && $order!="") {
    if ($order == "coords") $order = "x,y,z";

    $q = "SELECT login, password, ip, leader, planetname, x, y, z, id, mode ".
         "FROM user, planet WHERE planet_id=id order by $order";

  } else  {
    $q = "SELECT login, password, ip, leader, planetname, x, y, z, id, mode ".
         "FROM user, planet WHERE planet_id=id order by x,y,z";
  }
  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) > 0) {
    echo "<table width=\"700\" border=\"1\" cellpadding=\"2\">".
      "<tr class=\"a\"><th align=\"center\" colspan=\"7\">Global Message Options</th></tr>";
    echo "<tr><th><a href=\"".$_SERVER['PHP_SELF']."?order=login\">login</a></th>".
	"<th><a href=\"".$_SERVER['PHP_SELF']."?order=password\">password</a></th>".
	"<th><a href=\"".$_SERVER['PHP_SELF']."?order=ip\">ip</a></th>".
	"<th><a href=\"".$_SERVER['PHP_SELF']."?order=leader\">leader</a></th>".
    	"<th><a href=\"".$_SERVER['PHP_SELF']."?order=planetname\">planetname</a></th>".
	"<th><a href=\"".$_SERVER['PHP_SELF']."?order=coords\">coords</a></th>".
	"<th><a href=\"".$_SERVER['PHP_SELF']."?order=id\">id</a></th></tr>";

    while ($row=mysqli_fetch_array($result)) {
      print_list_row ($row);
    }

    echo "</table>";
  }

} else {
  $q = "SELECT login, email, leader, planetname, x, y, z,id, ".
       "user.last as ulast, last_sleep, mode, score, ip, ".
       "first_tick, signup, password, ".
       "metalroids,crystalroids,eoniumroids,uniniroids, ".
       "planet_m,planet_c,planet_e, roid_modifier, ".
       "user.delete_date as deldate, ".
       "user.last_post as upost, user.error as uerr, ".
       "(user.delete_date > user.last) as del,alliance_id, ".
       "imgpath as uimg, metal, crystal, eonium, login_date, ".
       "SEC_TO_TIME(UNIX_TIMESTAMP(user.last) - UNIX_TIMESTAMP(login_date)) AS upnow, ".
       "uptime ".
       "FROM user, planet WHERE planet_id='$id' AND id=planet_id";

  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) > 0) {
    $row=mysqli_fetch_array($result);

    echo "<table width=\"650\" border=\"1\" cellpadding=\"2\">".
      "<tr class=\"a\"><th align=\"center\" colspan=\"2\">".
      "Player Info</th></tr>";
    echo "<tr><td>Login</td><td>$row[login]</td></tr>";
    echo "<tr><td>Password</td><td>$row[password]</td></tr>";
    echo "<tr><td>Email</td><td>$row[email]</td></tr>";
    echo "<tr><td>Leader</td><td>$row[leader]</td></tr>";
    echo "<tr><td>Planetname</td><td>$row[planetname]</td></tr>";
    echo "<tr><td>Coords</td><td>$row[x]:$row[y]:$row[z]</td></tr>";
    echo "<tr><td>Alliance</td><td>";
    if ($row["alliance_id"]==0) echo "None";
    else {
      $r = mysqli_query ($db, "SELECT tag FROM alliance WHERE id=$row[alliance_id]");
      $ro=mysqli_fetch_array($r);
      echo "[<a href=\"aalist.php?allid=$row[alliance_id]\">$ro[0]</a>]";
    }
    echo "</td></tr>";
    echo "<tr><td>Id</td><td>$row[id]</td></tr>";
    echo "<tr><td>Signup</td><td>$row[signup] ($row[first_tick])</td></tr>";
    echo "<tr><td>Last action</td><td>$row[ulast]</td></tr>";
    echo "<tr><td>Image path</td><td>$row[uimg]</td></tr>";
    echo "<tr><td>Last Post</td><td>$row[upost] ($row[uerr] errors)</td></tr>";
    echo "<tr><td>Mode</td>";
    if($row["del"] == 1) {
       echo "<td>deleted: $row[deldate] + 12 hours</td>";
    } else {
       switch ($row["mode"] & 0xF) {
        case 0: echo "<td>banned</td>"; break;
        case 1: echo "<td>offline</td>"; break;
        case 2: echo "<td>online</td>"; break;
        case 3: echo "<td>sleeping</td>"; break;
        case 4: echo "<td>vacation</td>"; break;
       }
    }
    echo "<tr><td>Last login</td><td>$row[login_date]</td></tr>";
    if (($row["mode"] & 0xF) != 2) {
      echo "<tr><td>Uptime</td><td>$row[uptime]</td></tr>";
    } else {
      echo "<tr><td>Uptime</td><td>$row[uptime] + $row[upnow]";
      
      echo "</td></tr>";
    }
    echo "<tr><td>Last sleep</td><td>$row[last_sleep]</td></tr>";
    echo "<tr><td>Score</td><td>$row[score]</td></tr>";
    echo "<tr><td>Roids</td><td>$row[metalroids] : $row[crystalroids]".
      " : $row[eoniumroids] : $row[uniniroids]</td></tr>";

    $inc = get_planet_income();
    echo "<tr><td>Income</td><td>".
      (calc_per_roid ($row["metalroids"],$row["roid_modifier"]) + $inc[0]) . " M : ".
      (calc_per_roid ($row["crystalroids"],$row["roid_modifier"]) + $inc[1]) . " C : ".
      (calc_per_roid ($row["eoniumroids"],$row["roid_modifier"]) +$inc[2]) . " E</td></tr>";
    echo "<tr><td>Resources</td><td>". pval($row["metal"]) . " M , ".
         pval($row["crystal"]) . " C, ". pval($row["eonium"]) . " E</td></tr>";
    echo "<tr><td>IP</td><td>$row[ip]</td></tr>";
    if ($row["ip"] != "")
      echo "<tr><td>Hostname</td><td>".gethostbyaddr($row["ip"])."</td></tr>";
    else
      echo "<tr><td>Hostname</td><td></td></tr>";
    echo "<tr><td colspan=2 align=center>".
      "<a href=\"javascript:popupWindow('New_Message',".
         "'../nsend_message.php?send_to=$row[id]',340,700)\">Message</a> | ".
      "<a href=\"pnews.php?submit=1&playerid=$row[id]\">News</a> | ".
      "<a href=\"pmail.php?submit=1&playerid=$row[id]\">Mail</a> | ".
      "<a href=\"pdelete.php?submit=1&playerid=$row[id]\">Delete</a> | ";
    if ($row["mode"] == 0) {
      echo "<a href=\"punban.php?submit=1&playerid=$row[id]\">UnBan</a> | ";
    } else {
      echo "<a href=\"pban.php?submit=1&playerid=$row[id]\">Ban</a> | ";
    }
    echo "<a href=\"ipban.php?submit=1&ip=$row[ip]&comment=$row[leader]\">IPBan</a> | ".
      "<a href=\"plog.php?submit=1&playerid=$row[id]\">Log</a> | ".
      "</td></tr>";
    echo "</table>";
  }
}

?>

</center>

<?php
require_once "../footer.php";
?>

