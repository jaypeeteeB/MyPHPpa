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
require "news_util.php";
require "planet_util.inc";

function select_list ($exclude=0) {
  global $db, $myrow;

  $ret = "";

  $q = "SELECT leader, planetname, id FROM planet ".
     "WHERE x=$myrow[x] AND y=$myrow[y] ".
     "AND mode in (1,2,3,4,241,242,243,244) ORDER BY z ASC";

  $result = mysqli_query ($db, $q );

  while ($row = mysqli_fetch_row($result)) {
    if ($exclude != $row[2])
      $ret .= "<option value=\"$row[2]\">$row[0] of $row[1]</option>";
  }
  return $ret;
}

function vote_rows ($vmin, &$table_stub) {
  global $db, $myrow, $galcommander_id, $galcommander_name, $Planetid;

  $table_stub = "";

  $q = "SELECT leader, planetname, vote, id FROM planet ".
     "WHERE x=$myrow[x] AND y=$myrow[y] ORDER BY z ASC";
  $result = mysqli_query ($db, $q );
  // $cnt = mysqli_num_rows($result);

  $select = "<select name=\"gcvote\">";

  while ($row = mysqli_fetch_row($result)) {
    $qq = "SELECT count(*) FROM planet ".
       "WHERE x=$myrow[x] AND y=$myrow[y] AND vote='$row[3]'";
    $res = mysqli_query ($db, $qq );

    if ($res) $myvotes = mysqli_fetch_row($res);
    else $myvotes[0] = 0;

    if ($row[2] == 0 ) {
      $mygc = "Undecided";
    } else { 
      if( $row[2] == $Planetid) {
	$mygc = "$row[0] of $row[1]";
      } else {
	$res = mysqli_query ($db, "SELECT leader, planetname FROM planet ".
			    "WHERE id='$row[2]'" );
	if ($res) {
	  $mygc_row = mysqli_fetch_row($res);
	  $mygc = "$mygc_row[0] of $mygc_row[1]";
	} else {
	  $mygc = "$row[0] of $row[1]";
	}
      }
    }
    if ($vmin <= $myvotes[0] && ($vmin > 1 || $myrow["id"] == 1)) {

      $table_stub .= "<tr><td><span class=\"gc\">$row[0] of $row[1]</span></td>".
	"<td align=center>$myvotes[0]</td>".
	"<td>$mygc</td></tr>";

      $galcommander_id = $row[3];
      $galcommander_name = "$row[0] of $row[1]";

    } else {

      $table_stub .= "<tr><td>$row[0] of $row[1]</td><td align=center>$myvotes[0]</td>".
	"<td>$mygc</td></tr>";
    }
    
    $select .= "<option value=\"$row[3]\">$row[0] of $row[1]</option>";
  }

  $select .= "<option value=\"-1\">Undecided</option></select>";
  return $select;
}

function vote_possible ()
{  
  global $db, $myrow;

  $q = "SELECT count(*) from planet WHERE x=$myrow[x] and y=$myrow[y] ".
       "AND mode IN (1,2,3,4,241,242,243,244)";

  $result = mysqli_query ($db, $q );
  $row = mysqli_fetch_row($result);

  return $row[0];
}

function calc_exile_cost() {
  global $db, $myrow;
  
  $q = "SELECT sum(score) FROM planet WHERE x='$myrow[x]' AND y='$myrow[y]'";
  $result = mysqli_query ($db, $q );
  $row = mysqli_fetch_row($result);

  return (int) ($row[0]*0.05);
}

require_once "navigation.inc";

echo "<div id=\"main\">\n";

/* top table is written now */
top_header ($myrow);

$msg = "";
$galcommander_id=0;
$galcommander_name="";

$count = vote_possible();
$needed_votes = ceil ($count*0.51);
if ($Planetid==1) $needed_votes = 1;

if (ISSET($_POST["gcvote"]) && ISSET($_POST["myvote"])) {
  $gcvote = $_POST["gcvote"];
  $myvote = $_POST["myvote"];

  if ($gcvote < 0) { 
    $gcvote = 0;
    mysqli_query ($db, "UPDATE planet set vote=0 WHERE id='$Planetid'" );
    $msg = "You decided not to vote any GC";
  } else {
    $gname = get_coord_name ($gcvote);

    if ($gname['x'] == $myrow['x'] && $gname['y'] == $myrow['y']) {
      mysqli_query ($db, "UPDATE planet set vote='$gcvote' ".
    	           "WHERE id='$Planetid'" );
      if ($gcvote != $Planetid) {
        $msg = "$myrow[leader] of $myrow[planetname] voted for You as ".
               "Galactic Commander";
        insert_into_news ($gcvote, 10, $msg);
        $msg = "You voted for $gname[leader] of $gname[planetname] as Galactic Commander";
      }
    } else {
      $msg = "Target planet out of reach";
      $log_msg = "$msg: $Planetid -> $gcvote";
      insert_into_news (1, 10, $log_msg);
    }
  }
}

// also sets gc_id
$select = vote_rows($needed_votes, $table_stub);


if ($galcommander_id == $myrow["id"]) {

  if (ISSET($_POST["newname"]) && ISSET($_POST["changename"])) {
    $newname = htmlspecialchars ($_POST["newname"]);
    $res = mysqli_query($db, "UPDATE galaxy set name='$newname' ".
		       "WHERE x='$myrow[x]' AND y='$myrow[y]'" );
  } else {
    $res = mysqli_query($db, "SELECT name from galaxy ".
		       "WHERE x='$myrow[x]' AND y='$myrow[y]'" );
    if ($res && mysqli_num_rows($res)) {
      $row = mysqli_fetch_row($res);
      $newname = $row[0];
    }
  }

  if (ISSET($_POST["newpic"]) && ISSET($_POST["changepic"])) {
    $newpic = htmlspecialchars ($_POST["newpic"]);
    $pend = strtolower(substr($newpic, -3));
    if ($newpic != "CLEAR" &&
	(strlen($newpic)<10 ||
         ( strtolower(substr($newpic, 0, 7)) != "http://" &&
         strtolower(substr($newpic, 0, 8)) != "https://" ) ||
         ($pend != "jpg" && $pend != "gif" && 
	  $pend != "png" && $pend != "tif"))) {
 
        // same as below - quick and dirty
        $res = mysqli_query($db, "SELECT pic from galaxy ".
		       "WHERE x='$myrow[x]' AND y='$myrow[y]'" );
        if ($res && mysqli_num_rows($res)) {
          $row = mysqli_fetch_row($res);
          $newpic = $row[0];
        }

    } else {
      if ($newpic == "CLEAR")
	$newpic = "";
      $res = mysqli_query($db, "UPDATE galaxy set pic='$newpic' ".
		       "WHERE x='$myrow[x]' AND y='$myrow[y]'" );
    }
  } else {
    $res = mysqli_query($db, "SELECT pic from galaxy ".
		       "WHERE x='$myrow[x]' AND y='$myrow[y]'" );
    if ($res && mysqli_num_rows($res)) {
      $row = mysqli_fetch_row($res);
      $newpic = $row[0];
    }
  }

  if (ISSET($_POST["newmsg"]) && ISSET($_POST["changemsg"])) {
    $rmsg = htmlspecialchars ($_POST["newmsg"]);
    if (strlen ($rmsg) > 2047 ) $newmsg = substr($rmsg, 0, 2047);
    else $newmsg = $rmsg;
    $res = mysqli_query($db, "UPDATE galaxy set text='$newmsg' ".
		       "WHERE x='$myrow[x]' AND y='$myrow[y]'" );
  } else {
    $res = mysqli_query($db, "SELECT text from galaxy ".
		       "WHERE x='$myrow[x]' AND y='$myrow[y]'" );
    if ($res && mysqli_num_rows($res)) {
      $row = mysqli_fetch_row($res);
      $newmsg = $row[0];
    }
  }

  $moc_list = select_list($myrow["id"]);

  if (ISSET($_POST["changemoc"]) && ISSET($_POST["mocvote"]) 
      && $myrow["id"] == $galcommander_id) {
    if ($_POST["mocvote"] == -1) {
      mysqli_query($db, "UPDATE galaxy SET moc=0 ".
                  "WHERE x=$myrow[x] and y=$myrow[y]" );
    } else {
      $mocvote = $_POST["mocvote"];
      $res = mysqli_query($db, "SELECT x,y,z FROM planet WHERE id='$mocvote' ".
 		         "AND x=$myrow[x] and y=$myrow[y]" );
      if ($res && mysqli_num_rows($res)>0) {
        mysqli_query($db, "UPDATE galaxy SET moc=$mocvote ".
  		    "WHERE x=$myrow[x] and y=$myrow[y]" );
        $mail = "$myrow[leader] of $myrow[planetname] has choosen you as his ".
	   "Minister of Communication.";
        insert_into_news ($mocvote, 10, $mail); 
      }
    }
  }

  $elected_ministers = "";
  $res = mysqli_query ($db, "SELECT moc,mod,mow FROM galaxy ".
       "WHERE x=$myrow[x] and y=$myrow[y]" );
  if ($res && mysqli_num_rows($res)>0) {
     $row = mysqli_fetch_array ($res);
     if ($row["moc"]!=0 ) {
        $md = get_coord_name ($row["moc"]);
        $elected_ministers = "<tr><td>Minister of Communication</td>".
          "<td colspan=\"2\">&nbsp;$md[leader] of $md[planetname] ".
          "($md[x]:$md[y]:$md[z])</td></tr>\n";
     }
  }

  if ($mytick>0 && ISSET($_POST["startexile"]) && ISSET($_POST["exilevote"]) 
      && $myrow["id"] == $galcommander_id) {
    $exilevote = $_POST["exilevote"];
    $ecost = calc_exile_cost();

    $res = mysqli_query($db, "SELECT x,y,z FROM planet WHERE id='$exilevote' ".
		       "AND x=$myrow[x] and y=$myrow[y]" );

    if ($res && mysqli_num_rows($res)>0) {
      // check for res and take it
       $pay = 0;

       $q = "SELECT metal, crystal, eonium FROM galaxy ".
         "WHERE x='$myrow[x]' AND y='$myrow[y]'";
       $res = mysqli_query ($db, $q );
       
       if ($res) {
         $grow = mysqli_fetch_row($res);

         if (($grow[0]+$grow[1]+$grow[2]) > $ecost) {
            // ok we have enough
            $gm = $grow[0];
            $gc = $grow[1];
            $ge = $grow[2];

            if ($ecost > $ge) {
              $ge = 0;
              $ecost -= $ge;
              if ($ecost > $gc) {
                $gc = 0;
                $ecost -= $gc;
                $gm -= $ecost;
              } else {
                $gc -= $ecost;
              }
            } else {
              $ge -= $ecost;
            }
            mysqli_query ($db, "UPDATE galaxy SET metal='$gm',crystal='$gc',eonium='$ge' ".
              "WHERE x=$myrow[x] and y=$myrow[y]" );
            $pay = 1;
         } else {
            // not evaluated !
            $msg .= "Your galaxy do not have enough resources in the Galaxy Fund!";
         }
       }


       if ( $pay==1 ) {
        // reset 
        mysqli_query ($db, "UPDATE planet SET exile_vote=0 ".
		     "WHERE x=$myrow[x] and y=$myrow[y]"); 

        // start
        mysqli_query($db, "UPDATE galaxy SET exile_id=$exilevote, ".
	  	    "exile_date=now() + INTERVAL 18 HOUR ".
		    "WHERE x=$myrow[x] and y=$myrow[y]" );
        // send info
        $nmsg = "$myrow[leader] of $myrow[planetname] has started an exile ".
	  "vote on You. It will run for 18 hours from now.";
        insert_into_news ($exilevote, 10, $nmsg); 

        // set my vote
        mysqli_query ($db, "UPDATE planet SET exile_vote=1 WHERE id='$Planetid'"); 
        $myrow["exile_vote"] = 1;
      }
    } else {
      // CLEAR
      mysqli_query ($db, "UPDATE planet SET exile_vote=0 ".
		   "WHERE x=$myrow[x] and y=$myrow[y]"); 
      mysqli_query($db, "UPDATE galaxy SET exile_id=0, ".
		  "exile_date=0 ".
		  "WHERE x=$myrow[x] and y=$myrow[y]" );
    }
  }
}


titlebox("Affairs", $msg);

?>

<center>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table class="std" width="650" border="1" cellpadding="5">
<tr><th colspan="3" class="a">Change GC Vote
</td></tr>
<tr><td colspan="3">Vote for GC: your gal must have at least 2 members.<br>
The GC has to have 51% of the votes.<br>
<b>You need <?php echo ($needed_votes>1?$needed_votes:2) ?> votes to elect a Galactic Commander</b></td></tr>
<tr><th width="50%">Ruler of Planet</th>
    <th width="20%">Votes</th>
    <th width="30%">Current Vote</th></tr>

<?php
echo "$table_stub\n";

if ( $count < 1) { 
  echo "<tr><td colspan=\"3\" align=\"center\">".
    "You dont have enough galaxy members</td></tr>";
} else {
  echo "<tr><td colspan=\"3\" align=\"center\">$select &nbsp;".
    "<input type=submit value=\"Vote\" name=\"myvote\"></td></tr>";

  if ($galcommander_name != "") {
    echo "<tr><td colspan=\"3\" align=\"center\">".
      "<b>Your GC is <span class=\"gc\">$galcommander_name</span></b></td></tr>";

    $res = mysqli_query ($db, "UPDATE galaxy set gc='$galcommander_id' WHERE ".
			"x='$myrow[x]' AND y='$myrow[y]'" );
  } else {
    $res = mysqli_query ($db, "UPDATE galaxy set gc=0,moc=0 ".
			"WHERE x='$myrow[x]' AND y='$myrow[y]'" );
  }
}

echo "</table>\n</form>\n<br>\n";

$res = mysqli_query ($db, "SELECT exile_id, date_format(exile_date,'%D %b %H:%i CEST') ".
		    "AS exile_date FROM galaxy ".
		    "WHERE x=$myrow[x] and y=$myrow[y] AND exile_id!=0" );

if ($mytick>0 && $res && mysqli_num_rows($res)>0) {

  if ($exvote) {
    mysqli_query ($db, "UPDATE planet SET exile_vote='$myexvote' WHERE id='$Planetid'" );
    $myrow["exile_vote"] = $myexvote;
  }

  $row = mysqli_fetch_row($res);
  $exwho = get_coord_name ($row[0]);

  if ($myrow["exile_vote"] == 1) $check_yes = "checked";
  else $check_no = "checked";

  $res = mysqli_query($db, "SELECT count(*) FROM planet ".
		     "WHERE x=$myrow[x] and y=$myrow[y] AND exile_vote=1" );
  $rex = mysqli_fetch_row($res);
  $yes_vote = $rex[0];
  $no_vote = $count - $yes_vote;
  $yes_percent = ((1000 * $yes_vote) / $count) * 1. / 10.;
  $no_percent = 100. - $yes_percent;
  
  echo <<<EOF
    <br>
    <form method="post" action="$_SERVER[PHP_SELF]">
    <table class="std" width="650" border="1" cellpadding="5">
    <tr><th colspan="3" class="a">Exile voting</th></tr>
    <tr><td colspan="3">There is an exile vote running against $exwho[leader] of 
          $exwho[planetname] ($exwho[x]:$exwho[y]:$exwho[z]). 
          It will end at $row[1] - to be succesfull 66.67% of galaxy members 
          has to vote <em>EXILE</em>.</td></tr>
    <tr><td>Current vote rate:</td>
    <td colspan="2">EXILE: <b>$yes_vote</b> votes ($yes_percent %)&nbsp;&nbsp;
          Dont EXILE: <b>$no_vote</b> votes ($no_percent %)&nbsp;&nbsp;
	  of $count total.</td></tr>
	 <tr><td>My vote:</td>
         <td><input type="radio" name="myexvote" value="1" $check_yes>EXILE
          &nbsp;&nbsp;<input type="radio" name="myexvote" value="0" $check_no>
          DONT EXILE</td>
    <td align="center">
      <input type="submit" name="exvote" value="  Vote  "></td></tr>
    </table>
    </form>
<br>
EOF;
}

if ($galcommander_id == $myrow["id"]) {

  $exile_cost = calc_exile_cost();

  echo <<<EOF
 <form method="post" action="$_SERVER[PHP_SELF]">
 <table class="std" width="650" border="1" cellpadding="5">
    <tr><th colspan="3" class="a">Commander options</th></tr>
    <tr><th colspan="3" bgcolor="#ff0000">
         Using offensive or insulting pictures or names leads to deletion of Your planet !!!</th></td></tr>
    <tr><td width="180">Galaxy name (max 119)</td>
      <td><input type="text" size="40" maxlength="119" name="newname" value="$newname"></td>
      <td width="110" align="center">
          <input type=submit value="Change" name="changename"></td></tr>
    <tr><td colspan=3 style="border-style:none"></td></tr>
    <tr><td colspan="3" align="center">
         <b>The image url has to begin with http:// and 
            end in '.jpg', '.png', '.gif' or '.tif'</b><br>CLEAR to clear</td></tr>
    <tr><td width="180">Galaxy picture (max 249)</td>
      <td><input type="text" size="40" maxlength="249" name="newpic" value="$newpic"></td>
      <td width="110" align="center">
          <input type=submit value="Change" name="changepic"></td></tr>
    <tr><td colspan=3 style="border-style:none"></td></tr>
    <tr><td colspan="3"><b>GC Message</b> (max 2047 chars with replacement)</td></tr>
    <tr><td colspan="3" align="center">
      <textarea rows="8" cols="60" name="newmsg">$newmsg</textarea></td></tr>
    <tr><td>Available TAGs for GC Message:</td>
        <td colspan="2">
          [c] and [/c] for centering text<br>
          [b] and [/b] for bold text<br>
          [i] and [/i] for italic text<br>
          [color=red] or [color=green] or [color=blue] and [/color] for colors
        </td></tr>
    <tr><td colspan="3" align="center">
        <input type=submit value="Change" name="changemsg"></td></tr>
    <tr><td colspan=3 style="border-style:none"></td></tr>
    <tr><th colspan=3>Minister</th></tr>
    $elected_ministers
    <tr><td>Elect Minister of Communication</td>
	  <td><select name="mocvote"><option value="-1">Clear</option>$moc_list</select></td>
	  <td align="center">
	    <input type=submit value="  Elect  " name="changemoc"></td></tr>
EOF;
  if ($mytick>0) {
  echo <<<EOF
    <tr><td colspan=3 style="border-style:none"></td></tr>
    <tr><th colspan=3>Exiling</th></tr>
    <tr><td colspan=3>Exiling will take 18 hours and 66.67% of galaxy members 
        beeing active in last 36 hours have to vote 'Exile' (banned and Vac 
        planets not counting). 
        Exile costs 5% of galaxy score in resources (actually: $exile_cost) 
        at start of vote.
        Resources are taken from Galaxy Fund in the order E, C and last M.</td></tr>
    <tr><td>Start Exile</td>
	  <td><select name="exilevote">
              <option value="-1">Clear</option>$moc_list</select></td>
	  <td align="center">
            <input type=submit value="  Exile  " name="startexile"></td></tr>
EOF;
  }
  echo <<<EOF
 </table>
 </form>
EOF;
}

echo "\n</center>\n";
echo "</div>\n";

require "footer.php";
?>
