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

if ((ISSET($research) || ISSET($construction) || ISSET($rc)) && ISSET($name) ) {

  $table = "rc_class";

  $values = "id='$id',name='$name'";
  if (ISSET($description)) $values .= ",description='$description'";
  if (ISSET($metal)) $values .= ",metal='$metal'";
  if (ISSET($crystal)) $values .= ",crystal='$crystal'";
  if (ISSET($eonium)) $values .= ",eonium='$eonium'";
  if (ISSET($build_ticks)) $values .= ",build_ticks='$build_ticks'";
  if (ISSET($construct_id)) $values .= ",construct_id='$construct_id'";
  if (ISSET($research_id)) $values .= ",research_id='$research_id'";
  if (ISSET($rc_id)) $values .= ",rc_id='$rc_id'";
  if (ISSET($block_id)) $values .= ",block_id='$block_id'";
  if (ISSET($type)) $values .= ",type='$type'";

  submit_values ($id, $values, $table);
}
?>
<center>
<table border="1" width="1005">
<tr><th colspan="10">Research/Construction</th></tr>
<tr><th width="30">Id</th>
    <th width="150">Name</th>
    <th width="500">Description</th>
    <th width="50">Cost M</th>
    <th width="50">Cost C</th>
    <th width="50">Cost E</th>
    <th width="50">Ticks</th>
    <th width="60">RC needed</th>
    <th width="60">RC blocked</th>
    <th width="30">Type</th>
</tr>
<?php

$result = mysqli_query($db, "SELECT * FROM rc_class ORDER BY id");
if (mysqli_num_rows($result) > 0) {
  while ($myres = mysqli_fetch_row($result)) {
    print_admin_row ($myres, 10);
  }
}
?>

<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<tr>
<?php
admin_form_field ("text","id",3,3);
admin_form_field ("text","name",12,30);
admin_form_field ("textarea","description");
admin_form_field ("text","metal",5,8);
admin_form_field ("text","crystal",5,8);
admin_form_field ("text","eonium",5,8);
admin_form_field ("text","build_ticks",4,4);
admin_form_field ("text","rc_id",4,4);
admin_form_field ("text","block_id",4,4);
admin_form_field ("text","type",4,1);
?>
</tr>
<tr><td colspan="10" align="center">
<input type=submit value="   RC   " name="rc">
&nbsp;&nbsp;<input type=reset value="  Reset  ">
</td></tr>
</form>
</table>
</center>

<?php
require_once "../footer.php";
?>
