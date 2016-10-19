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
require "logging.php";
require "res_calc.php";

$msg = "";
if (ISSET($_POST["submit"])) {

  $metal = (int) $_POST["metal"];
  $crystal = (int) $_POST["crystal"];
  $eon = (int) $_POST["eon"];

  if ($metal < 0 || $crystal < 0 || $eon < 0) {
    $msg .= "You want to destroy roids ?";
  } else {

  $q = "SELECT planet_id FROM fleet WHERE target_id='$Planetid' and type>9";
  $res = mysqli_query($db, $q );
  if ($res && mysqli_num_rows($res) >0) {
    $tid = mysqli_fetch_row($res);
    do_log_me (5, 1, "($metal, $crystal, $eon) - attacker: [" . $tid[0] . "]");
  } else {
    do_log_me (5, 2, "($metal, $crystal, $eon)");
  }

  $tcost=0;
  $troid=0;
  $m = $myrow["metal"];
  $metal = min($metal, $myrow["uniniroids"]);

  if ($metal && $metal>0) {
    $cost = calc_init_cost_new ($myrow, $metal);

    if ($cost > $m) {
      $metal = return_max_init ($myrow, $m);
      $cost = calc_init_cost_new ($myrow, $metal);
    }
    $tcost += $cost;
    $myrow["metal"] -= $cost;
    $myrow["metalroids"] += $metal;
    $myrow["uniniroids"] -= $metal;
    $troid += $metal;
  }

  $m = $myrow["metal"];
  $crystal = min($crystal, $myrow["uniniroids"]);
  if ($crystal && $crystal>0) {
    $cost = calc_init_cost_new ($myrow, $crystal);

    if ($cost > $m) {
      $crystal = return_max_init ($myrow, $m);
      $cost = calc_init_cost_new ($myrow, $crystal);
    }

    $tcost += $cost;
    $myrow["metal"] -= $cost;
    $myrow["crystalroids"] += $crystal;
    $myrow["uniniroids"] -= $crystal;
    $troid += $crystal;
  }

  $m = $myrow["metal"];
  $eon = min ($eon, $myrow["uniniroids"]);
  if ($eon && $eon>0) {
    $cost = calc_init_cost_new ($myrow, $eon);

    if ($cost > $m) {
      $eon = return_max_init ($myrow, $m);
      $cost = calc_init_cost_new ($myrow, $eon);
    }

    $tcost += $cost;
    $myrow["metal"] -= $cost;
    $myrow["eoniumroids"] += $eon;
    $myrow["uniniroids"] -= $eon;
    $troid += $eon;
  }

  $score = ($metal+$crystal+$eon)*$score_per_roid;

  $myrow["score"] += $score;
  $result = mysqli_query($db, "UPDATE planet SET metalroids=metalroids+'$metal', ".
                        "crystalroids=crystalroids+'$crystal', ".
                        "eoniumroids=eoniumroids+'$eon', ".
                        "uniniroids=uniniroids-'$troid', ".
                        "metal=metal-'$tcost', ".
                        "score=score+'$score' ".
                        "WHERE id='$Planetid' ".
                        "AND uniniroids>=$myrow[uniniroids]+$troid ".
                        "AND metal>=$tcost" );
  } /* idiot catching */
}

$resources = calc_roid_resource ($myrow);

if (ISSET($_POST["submit"])) {
  if ($metal>0)  $msg .= "Initialized $metal Metal roids<br>\n";
  if ($crystal>0) $msg .= "Initialized $crystal Crystal roids<br>\n";
  if ($eon>0) $msg .= "Initialized $eon Eonium roids<br>\n";
  if (!$metal && !$crystal && !$eon) {
     $msg = "You dont have enough Metal to initialize Asteroids<br>\n";
  }
  if ($tcost>0) $msg .= "For a total of $tcost Metal\n";
} else {

  if (ISSET($_POST["donate"])) {
    $md = (int) $_POST["metal_donate"];    
    $cd = (int) $_POST["crystal_donate"];    
    $ed = (int) $_POST["eonium_donate"];    

    if ($md>0 && $md <= $myrow["metal"]) {
       $myrow["metal"] -= $md; 
    } else $md = 0;
    if ($cd>0 && $cd <= $myrow["crystal"]) {
       $myrow["crystal"] -= $cd; 
    } else $cd = 0;
    if ($ed>0 && $ed <= $myrow["eonium"]) {
       $myrow["eonium"] -= $ed; 
    } else $ed = 0;
    $q = "UPDATE planet SET metal=metal-$md, crystal=crystal-$cd,".
       "eonium=eonium-$ed WHERE id='$Planetid' ".
       "AND metal>=$md AND crystal>=$cd AND eonium>=$ed";
    $mre = mysqli_query ($db, $q );
    if ($mre && mysqli_affected_rows($db)==1) {
      $q = "UPDATE galaxy SET metal=metal+$md, crystal=crystal+$cd,".
         "eonium=eonium+$ed WHERE x='$myrow[x]' AND y='$myrow[y]'";
      mysqli_query ($db, $q );
    }
    if ($md==0 && $cd==0 && $ed==0) $msg .= "You do not have enough resources";
  }
  if (ISSET($_POST["trade"])) {
    $tn = (int) ($_POST["trade_num"] * 1.05);
    $tg = (int) ($_POST["trade_num"]);
    
    $trade_from = (int) ($_POST["trade_from"]); 
    $trade_to = (int) ($_POST["trade_to"]);

    if ($trade_from == $trade_to) {
      $msg .= "You dont want to do that.";
    }
    if ($tn > 0) {
       $q = "SELECT metal, crystal, eonium FROM galaxy ".
         "WHERE x='$myrow[x]' AND y='$myrow[y]'";
       $res = mysqli_query ($db, $q );
       if ($res) {
         $grow = mysqli_fetch_array($res);

         if ($tn > $myrow[$trade_from]) {
            $tn = (int) ($trade_num);
            $tg = (int) ($trade_num * 0.95);
         }

         if ($tn > $myrow[$trade_from] || $tg > $grow[$trade_to]) {
           $msg .= "Sorry, not enough resources";
         } else {
           $myrow[$trade_from] -= $tn;
           $myrow[$trade_to] += $tg;
           $q = "UPDATE galaxy SET ".$trade_from."=".$trade_from."+'$tn', ".
               $trade_to."=".$trade_to."-'$tg' ".
               "WHERE x='$myrow[x]' AND y='$myrow[y]' ".
               "AND ".$trade_to.">='$tg'";
           $mre = mysqli_query ($db, $q );
           if ($mre && mysqli_affected_rows($db)==1) {
             $q = "UPDATE planet SET ".$trade_from."=".$trade_from."-'$tn', ".
                $trade_to."=".$trade_to."+'$tg' WHERE id='$Planetid'";
             mysqli_query ($db, $q );

             $msg .= "Trade done. 5% tax deducted.";
           }
         }
       }
    } else {
      if ($tn !=0)
        $msg .= "Trying to cheat, eh?";
    }
  }
}
if (ISSET($_POST["galfund"])) {

require "news_util.php";

  $md = (int) $_POST["metal"];
  $cd = (int) $_POST["crystal"];
  $ed = (int) $_POST["eonium"];

  if ($havoc == 0 && ($md != 0 || $cd != 0 || $ed != 0) && $target!=0) {

    $q = "SELECT id, metal,crystal,eonium FROM galaxy ".
         "WHERE x='$myrow[x]' AND y='$myrow[y]' AND moc='$Planetid' ".
         "AND donation_date + INTERVAL 24 HOUR < NOW()";
    $res = mysqli_query($db, $q );
    
    if (mysqli_num_rows($res) == 1 ) {
         $grow = mysqli_fetch_row($res);

         // first update against bots
         mysqli_query($db, "UPDATE galaxy SET donation_date=NOW() ".
                     "WHERE x='$myrow[x]' AND y='$myrow[y]'" );

        // recheck score
        $q = "SELECT id,x,y,z,leader,planetname,score FROM planet ".
             "WHERE x=$myrow[x] AND y=$myrow[y] ORDER BY score ASC LIMIT 2";
        $res = mysqli_query($db, $q );
        $valid = 0;
        while (!$valid && $row = mysqli_fetch_row($res)) {
          if ($row[0] == $target) $valid = 1;
        }

        if ($valid != 0) {
          $md = (int) ($md * 2 > $grow[1]?$grow[1]/2:$md);
          $cd = (int) ($cd * 2 > $grow[2]?$grow[2]/2:$cd);
          $ed = (int) ($ed * 2 > $grow[3]?$grow[3]/2:$ed);

          mysqli_query($db, "UPDATE galaxy SET metal=metal-$md,crystal=crystal-$cd,".
                      "eonium=eonium-$ed WHERE id=$grow[0]" );
          mysqli_query($db, "UPDATE planet SET metal=metal+$md,crystal=crystal+$cd,".
                      "eonium=eonium+$ed WHERE id='$target'" );
          $text = "Donated $md Metal, $cd Crystal and $ed Eonium to ".
                  "$row[4] of $row[5] ($row[1]:$row[2]:$row[3]).";
          $msg .= $text;
          insert_into_news ($Planetid, 2, $text);
          send_donation_news ($target, $md, $cd, $ed);

          if ($target == $Planetid) {
            $myrow["metal"] += $md;
            $myrow["crystal"] += $cd;
            $myrow["eonium"] += $ed;
          }
        } else {
          mysqli_query($db, "UPDATE galaxy SET donation_date=NOW()-INTERVAL 24 HOUR ".
                      "WHERE x='$myrow[x]' AND y='$myrow[y]'" );
          $msg .= "Target score too high!";
        }
    } else {
      $msg .= "You are not allowed to do this. Reporting incidence.";
    }
  }
}

/* top table is written now */
top_header($myrow);

titlebox("Resource",$msg);
?>

<center>
<table width="450" border="1">
  <tr><th colspan="4" class="a">Planet Income</th></tr>
  <tr><th width="150">Type</th><th width="100">Number</th>
      <th width="100">Planet</th><th width="100">Total</th></tr>

<?php

  $inc = get_planet_income();

  echo "<tr><td>Metal</td><td align=\"center\">".$myrow["metalroids"].
       "</td><td align=\"center\">$inc[0]</td><td align=\"center\">".
       ($resources["metal"]+$inc[0])."</td></tr>\n";
  echo "<tr><td>Crystal</td><td align=\"center\">".$myrow["crystalroids"].
       "</td><td align=\"center\">$inc[1]</td><td align=\"center\">".
       ($resources["crystal"]+$inc[1])."</td></tr>\n";
  echo "<tr><td>Eonium</td><td align=\"center\">".$myrow["eoniumroids"].
       "</td><td align=\"center\">$inc[2]</td><td align=\"center\">".
       ($resources["eonium"]+$inc[2])."</td></tr>\n";
?>
  <tr><td>Unitialized</td><td align="center"><?php echo $myrow["uniniroids"]?></td>
      <td colspan=2>&nbsp;</td></tr>
</table>

<br>
<?php 
if ($myrow["uniniroids"] >0) {
  $cost = calc_init_cost ($myrow);
?>
  <form method="post" action="<?php echo $_SERVER['PHP_SELF']?>">
  <table width="450" border="1">

  <tr><th colspan="2" align="center" class="a">
  <?php echo $myrow["uniniroids"] ?> Uninitiated Asteroids</th></td></tr>

  <tr><th colspan="2" align="center">Cost for next Initialization: <?php echo $cost ?> Metal</th></tr>
  <tr><th width="230">Type</th><th width="220">Number</th></tr>

  <tr>
    <td>Metal</td>
    <td align="center"><input type="text" name="metal" size="8"></td>
  </tr>
  <tr>
    <td>Crystal</td>
    <td align="center"><input type="text" name="crystal" size="8"></td>
  </tr>
  <tr>
    <td>Eonium</td>
    <td align="center"><input type="text" name="eon" size="8"></td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <input type=submit value="Initialize" name="submit">
    &nbsp;<input type=reset value=" Reset "></td>
  </tr>

</table>
</form>
<?php
} else {
  $cost = calc_init_cost ($myrow);

  echo <<<EOF
  <table width="450" border="1">
  <tr><th colspan="2" align="center">Cost for next Initialization: $cost Metal</th></tr>
  </table>
EOF;
}

  $q = "SELECT metal, crystal, eonium FROM galaxy ".
       "WHERE x='$myrow[x]' AND y='$myrow[y]'";
  $res = mysqli_query ($db, $q );
  if ($res) {
    $grow = mysqli_fetch_row($res);

    echo<<<EOF
<br>
<form method="post" action="$_SERVER[PHP_SELF]">
<table width="450" border="1">
  <tr><th colspan="4" align="center" class="a">Donate to Galaxy Fund</th></tr>
  <tr><th width="150">Resource</th><th width="100">Fund</th>
    <th width="100">Yours</th><th width="100">Amount</th></tr>
  <tr><td>Metal</td><td align="right">$grow[0]</td>
    <td align="right">$myrow[metal]</td>
    <td align="center"><input type="text" name="metal_donate" size="15"></td>
  </tr>
  <tr><td>Crystal</td><td align="right">$grow[1]</td>
    <td align="right">$myrow[crystal]</td>
    <td align="center"><input type="text" name="crystal_donate" size="15"></td>
  </tr>
  <tr><td>Eonium</td><td align="right">$grow[2]</td>
    <td align="right">$myrow[eonium]</td>
    <td align="center"><input type="text" name="eonium_donate" size="15"></td>
  </tr>
  <tr><td colspan="4" align="center">
    <input type=submit value="Donate" name="donate">&nbsp;
    <input type=reset value="  Reset "></td>
  </tr>
</table>
</form>

<br>
<form method="post" action="$_SERVER[PHP_SELF]">
<table width="450" border="1">
<tr><th align="center" class="a">Trade with the 
Galaxy Fund</th></tr>
<tr><td align="center" valign="center">Trade 
&nbsp;<input type="text" name="trade_num" size="12">&nbsp;&nbsp;
<select name="trade_from"><option value="metal">Metal</option>
<option value="crystal">Crystal</option>
<option value="eonium">Eonium</option></select>&nbsp;&nbsp;for&nbsp;&nbsp;
<select name="trade_to"><option value="metal">Metal</option>
<option value="crystal">Crystal</option>
<option value="eonium">Eonium</option></select>&nbsp;&nbsp;
<input type=submit value=" Trade " name="trade">
</td></tr>
</table>
</form>
EOF;
    
//  }

  if ($havoc == 0 && $mytick > 0) {
    $q = "SELECT moc, donation_date + INTERVAL 24 HOUR < NOW(), ".
         "date_format(donation_date + INTERVAL 24 HOUR, ".
         "\"%D %M %H:%m:%s\") AS nextd FROM galaxy ".
         "WHERE x='$myrow[x]' AND y='$myrow[y]' AND moc='$Planetid'";
    $res = mysqli_query($db, $q );

    if (mysqli_num_rows($res) == 1) {
      $row = mysqli_fetch_row($res);

        echo <<<EOF
<br>
<form method="post" action="$_SERVER[PHP_SELF]">
<table width="450" border="1">
  <tr><th class="a" colspan="3">Galaxy fund administration</th></tr>
  <tr><td colspan=3>Donation is possible <b>once per 24 hours</b> to one 
of the two lowest galaxy members by score. The amount is limited 
to 50% of Fund of specified type(s).</td></tr>
EOF;

      if ($row[1] == 0) {
        // next time to donate
        echo <<<EOF
  <tr><td colspan=3>Next possible donation date is <b>$row[2]</b>.</td></tr>
  </table>
EOF;
      } else {
        $q = "SELECT id,x,y,z,leader,planetname,score FROM planet ".
             "WHERE x=$myrow[x] AND y=$myrow[y] ORDER BY score ASC LIMIT 2";
        $res = mysqli_query($db, $q );
        while ($row = mysqli_fetch_row($res)) {
          $sel_who .= "<option value=\"$row[0]\">$row[4] of $row[5] ".
            "($row[1]:$row[2]:$row[3])</option>";
        }
        echo <<<EOF
  <tr><th>Planet</th><th>Amount</th><th>Type</th></tr>
  <tr><td ><select name="target">$sel_who</select></td>
    <td><input type="text" name="metal" size="12"></td><td>Metal</td></tr>
  <tr><td></td><td><input type="text" name="crystal" size="12"></td><td>Crystal</td></tr>
  <tr><td></td><td><input type="text" name="eonium" size="12"></td><td>Eonium</td></tr>
  <tr><td colspan=3 align="center"><input type=submit value=" Donate " name="galfund"></td></tr>
</table>
EOF;
      }
    }
    echo "</form>\n";
  }
}

require "footer.php";
?>
