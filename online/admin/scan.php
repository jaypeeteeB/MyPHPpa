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

if (ISSET($scans) && ISSET($id)) {

  $table = "scan_class";

  $values ="id='$id'";
  if (ISSET($name)) $values .= ", name='$name'";
  if (ISSET($metal)) $values .= ", metal='$metal'";
  if (ISSET($crystal)) $values .= ", crystal='$crystal'";
  if (ISSET($eonium)) $values .= ", eonium='$eonium'";
  if (ISSET($ticks)) $values .= ", build_ticks='$ticks'";
  if (ISSET($rc_id)) $values .= ", rc_id='$rc_id'";
  if (ISSET($description)) $values .= ", description='$description'";

  submit_values ($id, $values, $table);
}

?>

<center>
<table border="1" width="740">
<tr><th colspan="8">Scans</th></tr>
<tr><th width="40">Id</th>
    <th width="100">Name</th>

    <th width="40">Cost M</th>
    <th width="40">Cost C</th>
    <th width="40">Cost E</th>

    <th width="40">Ticks</th>
    <th width="40">RC needed</th>

    <th width="400">Description</th>
</tr>
<?php
$result = mysqli_query($db, "SELECT id,name,metal,crystal,eonium,build_ticks,rc_id,description ".
		      "FROM scan_class ORDER BY id");

if ($result && mysqli_num_rows($result) > 0) {
  while ($myres = mysqli_fetch_row($result)) {
    print_admin_row ($myres, 8);
  }
}
?>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<tr>
<?php
admin_form_field ("text","id",3,3);
admin_form_field ("text","name",10,30);
admin_form_field ("text","metal",5,8);
admin_form_field ("text","crystal",5,8);
admin_form_field ("text","eonium",5,8);
admin_form_field ("text","ticks",5,3);
admin_form_field ("text","rc_id",5,3);
admin_form_field ("textarea","description",3,3,40,1);
?>
</tr>
<tr><td colspan="8" align="center">
<input type=submit value=" Scans " name="scans">
&nbsp;&nbsp;<input type=reset value="  Reset  ">
</td></tr>
</form>
</table>

</center>

<?php
require_once "../footer.php";
?>
