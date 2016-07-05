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

function titlebox ($title, $msg="") {

  $line = "<td width=\"40%\"><hr></td>";
  echo "<center><table width=\"650\" border=\"0\" cellpadding=\"10\">".
    "<tr>$line<td width=\"20%\" style=\"font-size:16px\" align=\"center\">".
    "<b>$title</b></td>$line</tr></table></center>";
  
  if ($msg != "") {
    echo "<center><table width=\"650\" border=\"1\" cellpadding=\"10\">".
      "<tr><td><font color=\"red\"><b>$msg</b></font></td></tr></table>".
      "</center><br>";
  }
}

?>
