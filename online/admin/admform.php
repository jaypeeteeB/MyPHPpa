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

function print_admin_row ($row,$num) {
  echo "<tr>\n";
  for ($i=0; $i<$num; $i++) {
    echo "<td>" . $row[$i] ."</td>\n";
  }
  echo "</tr>\n";
}

function admin_form_field ($type, $name, $size=8, $maxsize=8, $cols=30, $rows=1) {

  if ($type=="text") {
    echo "<td><input type=\"text\" name=\"$name\" size=\"$size\" maxlength=\"$maxsize\"></td>\n";
  } else if ($type == "textarea") {
    echo "<td><textarea name=\"$name\" cols=\"$cols\" rows=\"$rows\"></textarea></td>\n";
  }
}

function submit_values($id, $values, $table) {
  global $db;

  $q = "select * from $table WHERE id = '$id'"; 
  $result = mysqli_query ($db, $q );
  
  if (mysqli_num_rows($result) > 0) {
    $q = "UPDATE $table set $values WHERE id='$id'";
  } else {
    $q = "INSERT INTO $table set $values";
  }
 
  echo "$q<br>";
  $result = mysqli_query ($db, $q );

  if (!$result) {
    echo "<font color=\"red\">Update/insert into $table ".
      "($name) failed</font><br><br>\n";
  }
}

?>
