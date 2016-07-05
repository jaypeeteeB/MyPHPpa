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

require_once "../planet_util.inc";
require_once "../player_move.php";

function clean_galaxy ($sx, $sy) {
  echo "You have to clean up the gal ($sx, $sy) by hand!";
}

echo "<br><b>Player move</b><br>";

if ($submit) {
  if ( $sx && $sy && $sz ) {
    $res = player_move ($sx, $sy, $sz, $tx, $ty, $tz);
    if($res) {
      $res = get_coord_name ($res);
      echo "Moved $res[4] of $res[3] ($sx,$sy,$sz) to $res[0]:$res[1]:$res[2]";
    } else { 
      echo "Move failed!!";
    }
  } elseif ($sx && $sy) {
    $res = mysqli_query ($db, "SELECT z FROM planet WHERE x=$sx AND y=$sy" );
    if ($res && mysqli_num_rows($res)>0) {
      while($row=mysqli_fetch_row($res)) {
        $resx = player_move ($sx, $sy, $row[0]);
        if($resx) {
          $new = get_coord_name ($resx);
          echo "Moved $new[4] of $new[3] ($sx,$sy,$sz) to $new[0]:$new[1]:$new[2]";
        } else {
          echo "Move failed!!";
        }
      }
      clean_galaxy ($sx, $sy);
    }
  }
} else {
?>
<center>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table width="650" border="1" cellpadding="10">
<tr><th colspan="2">Wich coords do you want to move where ?</th></tr>
<tr><td align="center">From:&nbsp;
<input type="text" name="sx" size="3" >&nbsp;
<input type="text" name="sy" size="3" >&nbsp;
<input type="text" name="sz" size="3" >
</td><td align="center">To:&nbsp;
<input type="text" name="tx" size="3" value="0">&nbsp;
<input type="text" name="ty" size="3" value="0">&nbsp;
<input type="text" name="tz" size="3" value="0">
</td></tr>
<tr><td align="center" colspan="2">
<input type=submit value="DO IT" name="submit"></td></tr>
</table>
</form>
</center>
<?php
}
?>

