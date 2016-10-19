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

// require "planet_util.inc";
// require "fleet_util.php";

function set_has_news ($id) {
  global $db, $myrow, $Planetid;

  mysqli_query ($db, "UPDATE LOW_PRIORITY planet SET has_news=1 WHERE id='$id'");
  if ($id == $Planetid) $myrow["has_news"] = 1;
}

function insert_into_news ($id, $type, &$text) {
  global $db, $mytick;

  $q = "INSERT DELAYED INTO news set planet_id='$id',date=now(), type='$type',".
     "tick='$mytick', text='" . addslashes($text) . "'";

  // echo "insert_news ($id:$type): [$q]<br>";

  if (mysqli_query ($db, $q)) set_has_news ($id);
}

function send_donation_news ($id, $m=0, $c=0, $e=0) {
    
  $text = "Your received a donation of";

  if ($m != 0) $text .= " $m Metal";
  if ($e != 0 && $c != 0) {
    $text .= ", $c Crystal and $e Eonium.";
  } else {
    if ($e != 0 || $c != 0) {
      $text .= " and ";
      if ($c != 0) $text .= "$c Crystal.";
      else $text .= "$e Eonium.";
    } else {
      $text .= ".";
    }
  }   

  insert_into_news ($id, 2, $text);
}

function send_msg_fleet_recall ($target_id, $eta, $order) {
  global $db, $Planetid, $myrow;

  $tc = get_coord_name ($target_id);

  if ($order < 10)
    $text = "We recalled a fleet defending ";
  else
    $text = "We recalled a fleet attacking ";

  $text .= coord_ref($tc[0], $tc[1], "<b>$tc[planetname] ($tc[0]:$tc[1]:$tc[2])</b>") .
     ". It will be home in <b>$eta</b> ticks.";

  $text_other =  "A fleet from " .
     coord_ref($myrow['x'], $myrow['y'], "<b>$myrow[planetname] ($myrow[x]:$myrow[y]:$myrow[z])</b>") .
     " has been recalled."; 

  /* type == 9 -> recall */
  insert_into_news ($Planetid, 9, $text);
  insert_into_news ($target_id, 9, $text_other);
}

function send_msg_fleet_move ($target_id, $eta, $order, $flnum, $name="ship") {
  global $db, $Planetid, $myrow;

  $result = mysqli_query($db, "SELECT fleet_id FROM fleet WHERE planet_id='$Planetid' and num='$flnum'");
  $row = mysqli_fetch_row ($result);
  $num = (int) fetch_fleet_sum ($row[0]);
  $tc = get_coord_name ($target_id);

  if ($order < 10) {
    $do_what = "defend";
    $do_type = "friendly";
    $type = 7; /* Friends */
  } else {
    $do_what = "attack";
    $do_type = "hostile";
    $type = 8; /* Hostile */
  }

  if ($num != 1) {
    $text = "We sent <b>". (int) $num ."</b> ".$name."s to $do_what " .
       coord_ref($tc['x'], $tc['y'], "<b>$tc[planetname] ".
       "($tc[x]:$tc[y]:$tc[z])</b>") .
       ". They will arrive in <b>$eta</b> ticks.";

    $text_other = "Our sensors have discovered a jumpgate opening in ".
       "our sector.  The origin seems to be ".
       coord_ref($myrow['x'],$myrow['y'], "<b>$myrow[planetname] ".
       "($myrow[x]:$myrow[y]:$myrow[z])</b>") .
       ". Expect company of <b>". (int) $num ."</b> $do_type ".$name."s ".
       "in <b>$eta</b> ticks.";
   } else {
    $text = "We sent <b>1</b> ".$name." to $do_what " .
       coord_ref($tc['x'], $tc['y'], "<b>$tc[planetname] ".
       "($tc[x]:$tc[y]:$tc[z])</b>") .
       ". It will arrive in <b>$eta</b> ticks.";

    $text_other = "Our sensors have discovered a jumpgate opening in ".
       "our sector.  The origin seems to be ".
       coord_ref($myrow['x'],$myrow['y'], "<b>$myrow[planetname] ".
       "($myrow[x]:$myrow[y]:$myrow[z])</b>") .
       ". Expect company of <b>1</b> $do_type ".$name." ".
       "in <b>$eta</b> ticks.";
   }

  /* type == 6 -> Launch */
  insert_into_news ($Planetid, 6, $text);
  insert_into_news ($target_id, $type, $text_other);
}

?>
