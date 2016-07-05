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

function pval ($val) {
  return number_format($val, 0, ",", ".");
}

$q = "SELECT x,y,z, score, FLOOR((metal+crystal+eonium)*0.09) AS rc, ".
     "FLOOR(score+(metal+crystal+eonium)*0.09) AS sc ".
     "FROM planet WHERE mode!=0 ORDER BY sc DESC LIMIT 10";

$result = mysqli_query ($db, $q );

echo "<center><table border=1 width=650>\n";
echo "<tr><th>Coords</th><th>Score</th><th>ResScore</th><th>Total Score</th></tr>\n";
while ($row=mysqli_fetch_array($result)) {
  echo "<tr><td align=center>$row[x]:$row[y]:$row[z]</td><td align=center>". 
       pval($row["score"]) ."</td><td align=center>". pval($row["rc"]).
       "</td><td align=center>". pval($row["sc"]) ."</td></tr>\n";
}
echo "</table></center>\n";

echo "No Fixes...<br>";

require_once "../footer.php";
?>
