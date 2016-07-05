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

?>

Choose from the menu above
<br>

<?php

$q = "SELECT count(*) FROM user";
$res = mysqli_query($db, $q );
$rowu = mysqli_fetch_row($res);

$q = "SELECT count(*) FROM planet";
$res = mysqli_query($db, $q );
$rowp = mysqli_fetch_row($res);

$gdate = date("D, d M Y H:i:s T");

echo "Currently $rowu[0] users on $rowp[0] planets [$gdate]<br>";

$q = "SELECT count(*) FROM planet WHERE mode=0";
$res = mysqli_query($db, $q );
$rowm = mysqli_fetch_row($res);

echo "Banned: $rowm[0] planets<br>";

$q = "SELECT count(*) FROM news";
$res = mysqli_query($db, $q );
$row = mysqli_fetch_row($res);

echo "News: $row[0] entries<br>";

$q = "SELECT count(*) FROM journal";
$res = mysqli_query($db, $q );
$row = mysqli_fetch_row($res);

echo "Journal: $row[0] entries<br>";

$q = "SELECT count(*) FROM logging";
$res = mysqli_query($db, $q );
$row = mysqli_fetch_row($res);

echo "logging: $row[0] entries<br>";

require_once "../footer.php";
?>
