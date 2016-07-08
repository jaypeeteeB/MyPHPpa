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

require "standard.php";
require "planet_util.inc";

function print_coords ($x, $y, $z) {
  return "<A HREF=\"galaxy.php?submit=1&x=$x&y=$y\">$x:$y:$z</A>";
}

/* top table is written now */

top_header($myrow);
titlebox("Overview");
?>
<center>
<table width="650" border="1" cellpadding="5" >
<tr><th class="a">Message of the Day</th></tr>
<tr><td><?php include "motd.php" ?></td></tr>
</table>

<br>

<?php

$system_message="";

if ($Planetid == 1) {
  $system_message .= "[Running $round.]\n".
     "<br><br>\n";
}

/* protection */

if ( ($myrow["mode"] & 0xF0) != 0)  {
  $q = "SELECT first_tick + 72 - $mytick FROM user WHERE planet_id='$Planetid'";

  $prot_res = mysqli_query ($db, $q );
  $prot_row = mysqli_fetch_row ($prot_res);  
  $protects = $prot_row[0];

  $system_message .= "You are under protection for the next $protects ticks.<br>";
}

/* exiling */
$exres = mysqli_query ($db, "SELECT exile_id, date_format(exile_date,'%D %b %H:%i CEST') ".
		      "AS exile_date FROM galaxy ".
		      "WHERE x=$myrow[x] and y=$myrow[y] AND exile_id!=0" );
if ($exres && mysqli_num_rows($exres)>0) {
  $exrow =  mysqli_fetch_row($exres);
    $system_message .= "There is an <a href=\"affairs.php\"><b>exile vote</b></a> ".
       "running until $exrow[1]";
  if ($exrow[0] == $Planetid) {
    $system_message .= " against <b>YOU</b>.<br>";
  } else {
    $system_message .= ".<br>";
  }
}

/* research */ 
// $q = "SELECT rc_class.name, rc_class.type FROM rc,rc_class ".
//  "WHERE rc.planet_id='$Planetid' AND status=2 AND rc.rc_id=rc_class.id";

$q = "SELECT rc_class.name, rc_class.type,rc_build.build_ticks ".
  "FROM rc_build,rc_class ".
  "WHERE rc_build.planet_id='$Planetid' AND rc_build.rc_id=rc_class.id";

$res = mysqli_query ($db, $q );

if ($res && mysqli_num_rows ($res)>0) {
  while ($row = mysqli_fetch_row ($res)) {
    if ($row[1] == 1) {
      $system_message .= "Construction of <b>$row[0]</b>";
    } else {
      $system_message .= "Researching of <b>$row[0]</b>";
    }
    $system_message .= " in progress for $row[2] ticks.<br>";
  }
}

/* own fleet status */
$q = "SELECT target_id, num, ticks, type FROM fleet ".
     "WHERE planet_id='$Planetid' AND num>0 AND (ticks!=0 OR full_eta!=0) ".
     "ORDER by num";
$res = mysqli_query ($db, $q );

if ($res && mysqli_num_rows ($res)>0) {

  $system_message .= "<br>";
  while ($row = mysqli_fetch_row ($res)) {
    $rowt = get_coord_name ($row[0]);

    if ($row[1] ==  $number_of_fleets) {
      $fmsg = "Missile attack on $rowt[planetname] (".
	 print_coords($rowt['x'],$rowt['y'],$rowt['z']).") ETA $row[2] ticks";
    } else {
      $fmsg = "Fleet $row[1] ";

      $ftarget = "$rowt[planetname] (".
	 print_coords($rowt['x'],$rowt['y'],$rowt['z']).")";
    
      $type = $row[3];

      if ($row[2] != 0 ) {
	$fmsg .= " ETA $row[2] ticks";
	if ($type==0 || $type==10) {
	  $fmsg .= " returning";
	} else if ($type < 10) {
	  $fmsg .= " to defend $type ticks $ftarget";
	} else {
	  $fmsg .= " to attack ". ($type - 10) . " ticks $ftarget";
	}
      } else {
	if ($type < 10) {
	  $fmsg .= " defending ". ($type) ." ticks $ftarget";
	} else {
	  $fmsg .= " attacking ". ($type - 10) . " ticks $ftarget";
	}
      }
    }

    $system_message .= "$fmsg<br>";
  }
}
/* incoming fleets */
$q = "SELECT fleet_id, planet_id, type, ticks, num FROM fleet ".
     "WHERE target_id='$Planetid' AND (ticks!=0 OR full_eta!=0) ".
     "ORDER by type, ticks ASC";

$res = mysqli_query ($db, $q );

if ($res && mysqli_num_rows ($res)>0) {
  require "fleet_util.php";

  $system_message .= "<br>";
  while ($row = mysqli_fetch_row ($res)) {

     $fsum = fetch_fleet_sum ($row[0]);
     $fowner = get_coord_name ($row[1]);

     $fstr = "$fowner[4] of $fowner[3] (".
	print_coords($fowner[0],$fowner[1],$fowner[2]).")";

     if ($row[3] == 0) {
       /* here */

       if ($row[4] == $number_of_fleets) {
	 $system_message .= "<span class=\"red\"><b>Missile attack from $fstr</span><br>";
       } else {
	 if ($row[2] < 10) {
	   $system_message .= "<span class=\"green\"><b>$fstr ".
	      "defending with $fsum";
	 } else {
	   $system_message .= "<span class=\"red\"><b>$fstr ".
	      "attacking with $fsum";
	 }

	 if ($fsum > 1) {
	   $system_message .= " ships</b></span><br>";
	 } else {
	   $system_message .= " ship</b></span><br>";
	 }
       }
     } else {

       if ($row[4] == $number_of_fleets) {
	 $system_message .= "<span class=\"red\"><b>Incoming Missile ".
	    "attack from $fstr ETA $row[3]</span><br>";
       } else {
	 /* on the way */
	 if ($fsum > 1) {
	   $fstr = "$fsum ships from $fstr";
	 } else {
	   $fstr = "ship from $fstr";
	 }

	 $fstr = $fstr ." ETA $row[3]";

	 if ($row[2] < 10) {
	   $system_message .= "<span class=\"green\"><b>Incoming friendly ".
	      "$fstr</b></span><br>";
	 } else {
	   $system_message .= "<span class=\"red\"><b>Incoming hostile ".
	      "$fstr</b></span><br>";
	 }
       }
     }
  }
}


if ($system_message!="") {
?>
<table width="650" border="1" cellpadding="5" >
<tr><th class="a">System Message</th></tr>
<tr><td><?php echo $system_message ?></td></tr>
</table>
<br>
<?php
}
?>

<table width="650" border="1" cellpadding="5">
<tr><th class="a">Message from your Commander</th></tr>
<tr><td>
<?php

$result = mysqli_query ($db, "SELECT text,gc FROM galaxy ".
		       "WHERE x='$myrow[x]' AND y='$myrow[y]'");

if ($result && mysqli_num_rows($result)) {
  $grow = mysqli_fetch_row($result);

  if ($grow[0]) {
    // $text = ereg_replace ("\n", "<br>", $grow[0]);
    $pat = array("/\n/",
                 "/\[b\]/","/\[\/b\]/",
                 "/\[c\]/","/\[\/c\]/",
                 "/\[i\]/","/\[\/i\]/",
                 "/\[color=(red|green|blue)\]/","/\[\/color\]/"
                 );
    $rep = array("<br>",
                 "<b>","</b>",
                 "<center>","</center>",
                 "<em>","</em>",
                 "<span class=\\1>","</span>"
                 );
    $text = preg_replace ($pat, $rep, $grow[0]);
    echo "$text";
  } else {
    if ($grow[1] == 0) {
      echo "Your galaxy does not have a GC\n";
    } else {
      echo "Your Commander didnt set a message\n";
    }
  }
} else {
  echo "Your galaxy does not have a GC\n";
}
?>
</td></tr>
</table>

<?php

$q = "SELECT unit_class.name, sum(units.num) FROM fleet, units, unit_class ".
  "WHERE fleet.planet_id='$Planetid' AND fleet.fleet_id=units.id ".
  "AND unit_class.id=units.unit_id GROUP BY units.unit_id";

$unit_res = mysqli_query ($db, $q );

if ($unit_res && mysqli_num_rows ($unit_res) > 0) {

  echo "<br>\n";

  $total = 0;
  $table = "";
  $row_counter = 0;

  while ($unit_row = mysqli_fetch_row ($unit_res)) {
    if ( ($row_counter % 2) == 0) $table .= "<tr>";
    $table .= "<td width=\"25%\">$unit_row[0]</td>" .
              "<td width=\"15%\" align=\"right\">$unit_row[1]</td>";
    if ( ($row_counter % 2) == 1) $table .= "</tr>\n";
    else $table .= "<td width=\"20%\">&nbsp;</td>";
    $total += $unit_row[1];
    $row_counter++;
  }

  if ($total) {
    if ( ($row_counter % 2) == 1) 
      $table .= "<td width=\"25%\"></td><td width=\"15%\"></td></tr>";
    echo "<table width=\"650\" border=\"1\" cellpadding=\"5\" >".
      "<th class=\"a\" colspan=\"5\">" .
      "Ships ($total units total)</th></tr>\n$table";
    echo "</table>";
  }
}

$q = "SELECT unit_class.name, sum(pds.num) FROM pds, unit_class ".
  "WHERE pds.planet_id='$Planetid' ".
  "AND unit_class.id=pds.pds_id AND pds.num>0 and unit_class.class=5 ".
  "GROUP BY pds.pds_id";

$pds_res = mysqli_query ($db, $q );

if ($pds_res && mysqli_num_rows ($pds_res) > 0) {

  echo "<br>\n";

  $total = 0;
  $table = "";
  $row_counter = 0;

  while ($pds_row = mysqli_fetch_row ($pds_res)) {
    if ( ($row_counter % 2) == 0) $table .= "<tr>";
    $table .= "<td width=\"25%\">$pds_row[0]</td>" .
              "<td width=\"15%\" align=\"right\">$pds_row[1]</td>";
    if ( ($row_counter % 2) == 1) $table .= "</tr>\n";
    else $table .= "<td width=\"20%\">&nbsp;</td>";
    $total += $pds_row[1];
    $row_counter++;
  }

  if ($total) {
    if ( ($row_counter % 2) == 1) 
      $table .= "<td width=\"25%\"></td><td width=\"15%\"></td></tr>";
    echo "<table width=\"650\" border=\"1\" cellpadding=\"5\" >".
      "<th class=\"a\" colspan=\"5\">" .
      "Planetarian Defence System ($total units total)</th></tr>\n$table";
    echo "</table>";
  }
}


echo "<br>\n";

$total = $myrow["metalroids"] + $myrow["crystalroids"] + $myrow["eoniumroids"]  
         + $myrow["uniniroids"];

echo "<table width=\"650\" border=\"1\" cellpadding=\"5\" >".
      "<th class=\"a\" colspan=\"5\">" .
      "Asteroids ($total total)</th></tr>";

echo "<td width=\"25%\">Metal</td><td width=\"15%\" align=\"right\">".
      $myrow["metalroids"] . "</td><td width=\"20%\">&nbsp;</td>";
echo "<td width=\"25%\">Crystal</td><td width=\"15%\" align=\"right\">".
      $myrow["crystalroids"] . "</td></tr>\n";
echo "<td width=\"25%\">Eonium</td><td width=\"15%\" align=\"right\">".
      $myrow["eoniumroids"] . "</td><td width=\"20%\">&nbsp;</td>";
echo "<td width=\"25%\">Uninitiated</td><td width=\"15%\" align=\"right\">".
      $myrow["uniniroids"] . "</td></tr>\n";

echo "</table>\n</center>\n";

require "footer.php";
?>
