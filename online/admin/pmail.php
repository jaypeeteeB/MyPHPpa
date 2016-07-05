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

function print_mail ($r) {
  
  $id = $r["id"];
  $text = ereg_replace ("<", "&lt;", $r["text"]);

  echo "<br>".
"<table width=640 border=1 cellpadding=4>".
"<tr>".
"<th class=x width=325>From: $r[psplanetname] ($r[psx]:$r[psy]:$r[psz]), $r[psleader]</th>".
"<th class=x width=325>To: $r[prplanetname] ($r[prx]:$r[pry]:$r[prz]), $r[prleader]</th></tr>".
"<tr>".
"<td width=400><b>Suject: </b>$r[subject]</th>".
"<td align=right>$r[date] CEST</th></tr>".
"<tr><td colspan=2>$text</td></tr>".
"</table>";

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
<?php

$result = mysqli_query ($db, "SELECT leader FROM planet WHERE id='$id'" );
if ($result && mysqli_num_rows($result) > 0) {
  $row=mysqli_fetch_array($result);
  $pleader = $row[0];
} else {
  $pleader = "MySQL error";
}
?>

<table width="640" border="1" cellpadding="5">
<tr class="a"><th align="center">Global Message Options</th></tr>
<tr><td align="center">Mail folder from: <?php echo $pleader ?></td></tr>
</table>

<?php

$q = "SELECT mail.id AS id, mail.date AS date, mail.subject AS subject, ".
     "mail.text AS text, ps.planetname AS psplanetname, ps.leader AS psleader, ".
     "ps.x AS psx, ps.y AS psy, ps.z AS psz, ".
     "pr.planetname AS prplanetname, pr.leader AS prleader, ".
     "pr.x AS prx, pr.y AS pry, pr.z AS prz ".
     "FROM mail, planet AS ps, planet AS pr ".
     "WHERE (mail.planet_id='$id' OR mail.sender_id='$id') ".
     "AND ps.id=mail.sender_id AND pr.id=mail.planet_id ".
     "ORDER BY mail.date DESC";

$result = mysqli_query ($db, $q );

if ($result && mysqli_num_rows($result) > 0) {
  while ($row=mysqli_fetch_array($result)) {
    print_mail ($row);
  }
}
?>

</center>

<?php
require_once "../footer.php";
?>
