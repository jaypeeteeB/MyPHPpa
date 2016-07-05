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

require_once "../newcoords.php";

echo "<br><b<Universe shuffle</b><br>";

if ($submit) {
echo "resetting Galaxies..<br>";

$q = "UPDATE galaxy set gc=0, members=0, name='Far Far Away', ".
     "text=NULL, pic=NULL, metal=0, crystal=0, eonium=0 WHERE id>1";
$res = mysqli_query($db, $q );

echo "resetting Players..<br>";

$res = mysqli_query($db, "ALTER TABLE planet DROP index x_y_z" );
echo "Index dropped<br>";

$q = "UPDATE planet set x=0,y=0,z=0,vote=0 WHERE id>2";
$res = mysqli_query($db, $q );

$q = "SELECT id FROM planet WHERE id>2";
$res = mysqli_query($db, $q );

$num = mysqli_num_rows($res);
$cluster_needed = (int) ceil(($num - ($cluster_size-1)*$gal_size)/($cluster_size*$gal_size))+1;

while ($rid = mysqli_fetch_row($res)) {

   $x = 0; $y = 0; $z = 0;
   get_new_coords($x,$y,$z,$cluster_needed);

   mysqli_query ($db, "UPDATE planet SET x=$x,y=$y,z=$z ".
                "WHERE id=$rid[0]" );

   echo "$rid[0] -> $x:$y:$z<br>";

   mysqli_query ($db, "UPDATE galaxy SET members=members+1 ".
                "WHERE x=$x AND y=$y" );
}

$res = mysqli_query($db, "ALTER TABLE planet ADD unique x_y_z (x,y,z)");
echo "Index generated<br>";

$q = "SELECT id FROM politics WHERE gal_id!=0 and gal_id<1024";
$res = mysqli_query($db, $q );
while ($rid = mysqli_fetch_row($res)) {
  mysqli_query($db, "DELETE FROM poltext WHERE thread_id=$rid[0]" );
}

$q = "DELETE FROM politics WHERE gal_id!=0";
$res = mysqli_query($db, $q );

echo "done<br>";
} else {
?>
<center>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table width="650" border="1" cellpadding="10">
<tr><th>You really want to <b>shuffle</b> the universe ?
</th></tr>
<tr><td align="center"><input type=submit value="DO IT" name="submit"></td></tr>
</table>
</form>
</center>
<?php
}
?>

