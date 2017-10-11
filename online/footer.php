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

require_once "session.inc";
session_flush();

if (ISSET($Planetid) && ISSET($start_time)) {
  $end_time = getmicrotime();
  $diff_time = $end_time - $start_time ;
  if ( $Planetid == 1 ) {
    echo "Runtime: ". number_format($diff_time, 3) ." s<br>";
  }
}
?>

</body>
</html>
