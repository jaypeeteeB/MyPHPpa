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

function get_ip($type=0) {

  if ($type)
    return $_SERVER['REMOTE_ADDR'];

  $ip = getenv("HTTP_CLIENT_IP");
  if (!$ip) {
    $ip = getenv("HTTP_X_FORWARDED_FOR"); 
    if (!$ip) { 
      $ip = getenv("REMOTE_ADDR"); 
    }
  }
  if (!$ip || $ip == "unknown")
    $ip = $_SERVER['REMOTE_ADDR'];

  return $ip;
}

function get_type($type=0) {

  return $_SERVER['HTTP_USER_AGENT'];

}

?>
