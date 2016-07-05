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

require "popup_header.inc";

$extra_header .= "\n<SCRIPT LANGUAGE=\"javascript\">\n".
"<!--\n".
"// Begin\n".
"function popupScrollWindow(_name, _page, _height, _width) {\n".
"  var window_options = \"toolbar=no, menubar=no, location=no\";\n".
"  window_options += \", scrollbars=yes, resize=yes\";\n".
"  window_options += \",height=\" + _height;\n".
"  window_options += \",width=\" + _width;\n".
"  window_options += \",top=\" + (screen.height - _height)/2;\n".
"  window_options += \",left=\" + (screen.width - _width)/2;\n".
"  var OpenWin = open(_page, _name, window_options);\n".
"}\n// END\n//-->\n</SCRIPT>";

require "standard.php";
/* top table is written now */

top_header($myrow);

require "gal_report.inc";

$target_x = $myrow["x"];
$target_y = $myrow["y"];

if (ISSET($_POST["submit"]) || ISSET($_POST["plus"]) || ISSET($_POST["minus"])) {
  if (ISSET($_POST["x"]) && ISSET($_POST["y"])) {
    $x = min(max(1,$_POST["x"]),$universe_size);
    $y = min(max(1,$_POST["y"]),$cluster_size);

    if(ISSET($_POST["plus"])) {
      if ($y == $cluster_size) {
	$y = 1;
	if ($x == $universe_size) {
	  $x = 1;
	} else {
	  $x = $x + 1;
	}
      } else {
	$y = $y + 1;
      }
    } else if (ISSET($_POST["minus"])) {
      if ($y == 1) {
	$y = $cluster_size;
	if ($x == 1) {
	  $x = $universe_size;
	} else {
	  $x = $x - 1;
	}
      } else {
	$y = $y - 1;
      }
    }
      
    $target_x = $x;
    $target_y = $y;
  }
}


titlebox("Galaxy");

$result = mysqli_query ($db, "SELECT id,name,pic,gc FROM galaxy ".
		       "WHERE x='$target_x' AND y='$target_y'" );

if ($result && mysqli_num_rows($result)) {
  $grow = mysqli_fetch_row($result);
  $gname = $grow[1] . " ($target_x:$target_y)";
  $gpic = $grow[2]; 
  $gcid = $grow[3];
} else {
  $gname = "Far Far Away";
  $gcid = 0;
  $gpic = 0;
}

$result = mysqli_query ($db, "SELECT SUM(score) FROM planet ".
		       "WHERE x='$target_x' AND y='$target_y' ".
                       "AND mode!=0" );

if ($result && mysqli_num_rows($result)) {
  $prow = mysqli_fetch_row($result);
  $gname .= " Score:&nbsp;". pval($prow[0]);
}

?>

<center>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<input type=submit value="&nbsp;<-&nbsp;" name="minus">&nbsp;&nbsp;
<input type="text" name="x" size="3" value="<?php echo $target_x?>">&nbsp;
<input type="text" name="y" size="3" value="<?php echo $target_y?>">&nbsp;
&nbsp;<input type=submit value="&nbsp;->&nbsp;" name="plus">&nbsp;
<input type=submit value="Search" name="submit">
</form>
<br>

<?php
if ($gpic) {
  echo "<hr width=\"630\">\n";
  if ($mysettings & 4 && !ISSET($_REQUEST["override_settings"])) {
    echo "\n<a href=\"". $_SERVER['PHP_SELF'] ."?submit=1&x=$target_x&y=$target_y&override_settings=1\">".
      "View gal picture</a>"; 
  } else {
    echo "\n<img src=\"$gpic\">"; 
  }
  echo "<hr width=\"630\">\n<br>\n";
}
?>

<table width="650" border="1">
  <tr><td style="visibility:hidden"></td><th width="630" colspan="5" class="a"><?php echo $gname ?></th></tr>
  <tr><td style="visibility:hidden"></td>
      <th width="35">Id</th>
      <th width="220">Planet</th>
      <th width="180">Leader</th>
      <th width="140">Score</th>
      <th width="40">Roids</th></tr>
<?php

print_gal_report ($target_x, $target_y);

echo "</table>\n</center>\n";

require "footer.php";
?>
