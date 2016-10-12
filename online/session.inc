<?php
/*
 * MyPHPpa
 * Copyright (C) 2005, 2007 Jens Beyer
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

/*
 * Module to handle PHP-style Sessions
 */

include_once "get_ip.php";

/*
 * Should go into global options
 */

$session_lifetime = 1800;

function session_init() 
{
  global $session_lifetime;
  session_cache_expire($session_lifetime);
  session_start ();
}

function session_create($ip, $login, $pw_hash, $planetid, $imgpath="")
{
    //    setcookie("Username",$login);
    //    setcookie("Password",md5($password));
    //    setcookie("Planetid",$myrow[0]);

  //global $Planetid;
/*
  if (isset ($_SESSION["Planetid"]) && 
      isset ($_SESSION["Password"]) &&
      isset ($_SESSION["Username"])) 
    return 1;
*/
  $_SESSION["Username"] = $login;
  $_SESSION["Password"] = $pw_hash;
  $_SESSION["Planetid"] = $planetid;
  $_SESSION["ImgPath"] = $imgpath;
  $_SESSION["IP"] = $ip;
  $_SESSION["TimeStamp"] = getmicrotime();
  $_SESSION["UserAgent"] = getenv("HTTP_USER_AGENT");

  // TODO
  // generate a session_hash
  // return the session_hash
  // or put it in db
  return 0;
}

function _session_agent_check()
{
  if (!isset($_SESSION["UserAgent"]))
    return 1;

  if ($_SESSION["UserAgent"] != getenv("HTTP_USER_AGENT"))
    return 2;

  return 0;
}

function _session_ip_check($ip)
{
  if (!isset($_SESSION["IP"]))
    return 1;
 
  if ($ip != $_SESSION["IP"])
    return 2;

  return 0;
}

function session_check($ip=0)
{
  global $Planetid, $Username, $Password, $imgpath;

  if (!isset ($_SESSION["Planetid"]) || 
      !isset ($_SESSION["Password"]) ||
      !isset ($_SESSION["Username"])) 
    return 1;

  if (_session_agent_check())
    return 2;

  if ($ip && _session_ip_check($ip))
    return 3;
  
  $Planetid = $_SESSION["Planetid"];
  $Username = $_SESSION["Username"];
  $Password = $_SESSION["Password"];
  $imgpath = $_SESSION["ImgPath"];

  // get session_hash from db
  // compare it

  return 0;
}

function session_kill()
{
  global $Planetid;
  session_cache_expire(0);
  session_destroy();

  $Planetid = -1;
}

function session_flush()
{
  session_write_close();
}

?>
