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

function check_ip($ip) {
  global $db;

  $q = "SELECT * FROM iptables WHERE ip = '$ip'";

  $res = mysqli_query ($db, $q );
  if ($res && mysqli_num_rows($res) > 0) {
    $q = "INSERT INTO news set planet_id=1,date=now(),type=10,text='IP check matched: $ip'";
     mysqli_query ($db, $q );
    return 0;
  }

  return 1;
}

?>
