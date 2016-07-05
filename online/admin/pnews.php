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

require_once "admhead.php";
require_once "admform.php";

function print_news_head ($date, $type, $tick) {

  switch ($type) {
  case 1: $img = "Bat"; $txt = "Military"; break;
  case 2: $img = "Don"; $txt = "Donation"; break;
  case 3: $img = "Sci"; $txt = "Science"; break;
  case 4: $img = "Bui"; $txt = "Building"; break;
  case 5: $img = "Wav"; $txt = "Wave Jam"; break;
  case 6: $img = "Lau"; $txt = "Launch"; break;
  case 7: $img = "Fri"; $txt = "Friendly"; break;
  case 8: $img = "Hos"; $txt = "Enemy"; break;
  case 9: $img = "Rec"; $txt = "Recalled"; break;
  case 10: $img = "Msg"; $txt = "Message"; break;
  case 11: $img = "Hid"; $txt = "Hidden"; break;
  }
  echo "<tr><td width=\"30\" height=\"30\">$img</td>".
       "<td width=\"610\">$date CET, MyT <b>$tick</b>, $txt</td></tr>";;
}

if (ISSET($submit) && ISSET($pleader) && $pleader !="") {
  $q = "SELECT id FROM planet  WHERE leader LIKE '$pleader'";
  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_row($result);
    $id = $row[0];
  } else {
    $id = $Planetid;
  }
} if (ISSET($submit) && ISSET($playerid) && $playerid !="") {
  $id = $playerid;
} else {
  $id = $Planetid;
}
?>

<center>

<table  width="640" border="1" cellpadding="2" >
<tr>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
  <td align="center" class="a">Enter target leader:</td>
  <td><input type="text" name="pleader" size="25"></td>
  <td colspan="2"><input type=submit value="  Search  " name=submit></td>
</form>
</tr>
</table>

<br>

<table  width="640" border="1" cellpadding="2" >
<tr class="a"><th align="center" colspan="2">Private News</th></tr>

<?php
$q = "SELECT date,type,text,tick from news WHERE planet_id='$id' ".
     "ORDER BY date DESC";

$result = mysqli_query ($db, $q );

if ($result && mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_row($result)) {
    print_news_head ($row[0], $row[1], $row[3]);
    echo "<tr><td width=\"30\">&nbsp;</td><td width=\"610\">$row[2]</td></tr>";
  }
} else {
  echo "<tr><td width=\"30\">&nbsp;</td><td width=\"610\">No News</td></tr>";
}
?>
</table>
</center>


<?php
require_once "../footer.php";
?>
