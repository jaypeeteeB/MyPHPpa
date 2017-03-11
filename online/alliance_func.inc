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

function get_alliance () {
  global $db, $myrow;

  if ($myrow["alliance_id"] == 0) return 0;

  $q = "SELECT * FROM alliance WHERE id='$myrow[alliance_id]'";
  $res = mysqli_query($db,  $q );
  if ($res)
    $all = mysqli_fetch_array($res);
  else
    return 0;
  $all["status"] = !($myrow["status"] & 2);

  return $all;
}

function print_alliance_status ($all) {
  global $db, $myrow;

  if (!$all) return;

  $q = "SELECT * FROM planet WHERE id='$all[hc]'";
  $res = mysqli_query($db,  $q );
  if ($res)
    $hc = mysqli_fetch_array($res);
  else {
    echo "HC missing in DB!";
    return;
  }

  echo <<<EOF
<table class="std" border="1" width="650" cellpadding="2">
  <tr><th colspan="2" class="a">Alliance Status</th></tr>
  <tr><td width="150" align="right">Alliance&nbsp;</td>
      <td>&nbsp;$all[name]</td></tr>
  <tr><td width="150" align="right">Tag&nbsp;</td>
      <td>&nbsp;[$all[tag]]</td></tr>
  <tr><td width="150" align="right">Members&nbsp;</td>
      <td>&nbsp;$all[members]</td></tr>
  <tr><td width="150" align="right">HC public&nbsp;</td>
      <td>&nbsp;$all[hcname]</td></tr>
EOF;
  if ($all["status"]) {
    echo <<<EOF
  <tr><td width="150" align="right">HC ingame&nbsp;</td>
      <td>&nbsp;$hc[leader] of $hc[planetname] ($hc[x]:$hc[y]:$hc[z])</td></tr>
EOF;
  } else {
    echo <<<EOF
  <tr><td width="150" align="right">Status&nbsp;</td>
      <td>&nbsp;<b>UNTRUSTED</b></td></tr>
EOF;
  }
  if ($all["hc"] == $myrow["id"] || $all["offa"] == $myrow["id"] || $myrow["id"] <= 2 ) {
    echo "<tr><td width=\"150\" align=\"right\">Alliance Secret&nbsp;</td>\n".
      "<td>&nbsp;$all[secret]</td></tr>\n";
  } 
  if ($all["hc"] != $myrow["id"]) {
    echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">".
      "<tr><td align=\"center\" colspan=\"2\" valign=\"center\">".
      "<input type=submit value=\"  Quit alliance  \" name=\"oquit\">".
      "</td></tr></form>\n";
  }
  echo "</table>\n";
}

function delete_alliance () {
  global $db, $myrow, $all;

  if (!$all || $all["hc"] != $myrow["id"]) {
    return "You are not allowed to do this";
  }
  
  $myrow["alliance_id"] = 0;
  mysqli_query($db, "UPDATE planet SET alliance_id=0,status=status&0xFD ".
              "WHERE alliance_id='$all[id]'" );
  mysqli_query($db, "DELETE FROM alliance WHERE id='$all[id]'" );

  $msg = "Alliance '[$all[tag]] $all[name]' deleted.";
  $all = 0;
  return $msg;
}

function list_alliances() {
  global $db, $myrow;
  global $Planetid;

  $q = "SELECT tag,name,hcname,members ".
       "FROM alliance WHERE members>2 OR tag='ADM' ORDER BY members DESC";
  $res = mysqli_query($db,  $q );

  if (!$res || mysqli_num_rows($res)==0) {
    echo "No alliances found.<br>";
    return;
  }

  echo "<table class=\"std\" border=\"1\" width=\"650\" cellpadding=\"2\">\n".
   "<tr><th colspan=\"4\" class=\"a\">Alliance listing</th></tr>\n".
   "<tr><th width=\"60\">Tag</th><th width=\"60\">Members</th>".
   "<th>Name</th><th width=\"80\">HC Name</th></tr>\n";

  while ($row=mysqli_fetch_row($res)) {
    echo "<tr><td align=\"center\">[$row[0]]</td>".
      "<td align=\"center\">$row[3]</td><td>$row[1]</td>".
      "<td><a href=\"javascript:popupWindow('HC_Alliance',".
      "'nsend_message.php?hc=$row[0]',340,700)\">$row[2]</a></td></tr>\n";
  }
  echo "</table>\n";
}

function list_alliances_admin($ref) {
  global $db, $myrow;

  $q = "SELECT tag,name,hcname,members,id FROM alliance ORDER BY members DESC";
  $res = mysqli_query($db,  $q );

  if (!$res || mysqli_num_rows($res)==0) {
    echo "No alliances found.<br>";
    return;
  }

  echo "<table class=\"std\" border=\"1\" width=\"650\" cellpadding=\"2\">\n".
   "<tr><th colspan=\"5\" class=\"a\">Alliance listing</th></tr>\n".
   "<tr><th width=\"60\">Tag</th><th width=\"60\">Members</th>".
   "<th width=\"30\">Id</th>".
   "<th>Name</th><th width=\"80\">HC Name</th></tr>\n";
  
  while ($row=mysqli_fetch_row($res)) {
    $r_ctl = mysqli_query($db, "SELECT COUNT(*) FROM planet WHERE alliance_id=$row[4]" );
    $r_row = mysqli_fetch_row($r_ctl);

    echo "<tr><td align=\"center\">[<a href=\"$ref$row[4]\">$row[0]</a>]</td>".
      "<td align=\"center\">$row[3] ($r_row[0])</td><td>$row[4]</td><td>$row[1]</td>".
      "<td>$row[2]</td></tr>";
  }
  echo "</table>\n";
}

function untrusted_msg() {
  echo "<table class=\"std\" border=\"1\" width=\"650\" cellpadding=\"2\">\n".
      "<tr><td><span class=\"red\">Sorry you need to be trusted by HC first</span>".
      "</td></tr></table>\n";
}

function list_alliance_members ($all) {
  global $db, $myrow;

  if (!$all["status"]) {
    untrusted_msg();
    return;
  }

  $q = "SELECT leader,planetname,x,y,z,id,score,status,mode,has_hostile ".
       "FROM planet WHERE alliance_id='$all[id]' ORDER BY x,y,z";
  $res = mysqli_query($db,  $q );

  if (!$res || mysqli_num_rows($res)==0) {
    echo "No alliance members for $all[tag] found.";
    return;
  }

  echo "<table class=\"std\" border=\"1\" width=\"650\" cellpadding=\"2\">\n".
   "<tr><th colspan=\"3\" class=\"a\">Member listing [$all[tag]]</th></tr>\n".
   "<tr><th width=\"100\">Coords</th><th>Name</th><th>Score</th></tr>\n";
  
  while ($row=mysqli_fetch_row($res)) {
    if (($row[8]&0xF) == 2) $online = "&nbsp;*&nbsp;";
    else $online = "";
    echo "<tr><td align=\"center\">(" . 
         coord_ref($row[2],$row[3], "$row[2]:$row[3]:$row[4]") . ")";
/*
    if ($row[9]!=0) {
      echo "&nbsp;<span class=\"red\">&yen;</span>";
    }
*/
    echo "</td><td>";
    if ($row[5] == $all["hc"])
      echo "<span class=\"gc\">$row[0] of $row[1] (HC)</span>$online";
    else if ($row[5] == $all["offa"])
      echo "<span class=\"moc\">$row[0] of $row[1] (O)</span>$online";
    else 
      echo "$row[0] of $row[1]$online";

    if (($myrow["id"] == $all["hc"] || $myrow["id"] == $all["offa"]) 
        && $row[5] != $myrow["id"]) {

      if ($myrow["id"] == $all["hc"]) {
        echo "&nbsp;<a href=\"".$_SERVER['PHP_SELF']."?okick=$row[5]\">[kick]</a>";
      }

      if ($row[7] & 2) {
         echo "&nbsp;<a href=\"".$_SERVER['PHP_SELF']."?otrust=$row[5]\">[trust]</a>";
      }
    }

    echo "</td><td align=\"right\" width=\"140\">".pval($row[6])."</td></tr>";
  }
  echo "</table>\n";
}

function leave_alliance () {
  global $db, $myrow, $all, $Planetid;

  if ($myrow["alliance_id"]!=$all["id"]) 
    return "What ?";

  $text = "$myrow[leader] of $myrow[planetname] (".
          coord_ref($myrow[x], $myrow[y], "$myrow[x]:$myrow[y]:$myrow[z]") . ") ".
          "left your alliance.";
  insert_into_news ($all["hc"], 11, $text);

  mysqli_query($db,  "UPDATE planet SET alliance_id=0,status=status&0xFD ".
               "WHERE id='$Planetid'" );
  // affected rows abfragen
  if ( $Planetid == $all["offa"]) { 
    mysqli_query($db,  "UPDATE alliance SET members=members-1, offa=NULL ".
               "WHERE id='$myrow[alliance_id]'" );
  } else {
    mysqli_query($db,  "UPDATE alliance SET members=members-1 ".
               "WHERE id='$myrow[alliance_id]'" );
  }
  $myrow["alliance_id"] = 0;
  $all = 0;

  return "You left the alliance.";
}

function kick_alliance ($kid) {
  global $db, $myrow, $all, $Planetid;

  if ($myrow["alliance_id"]==0) {
    return "Not member of an alliance!.";
  }
  if (!$all || $all["hc"] != $Planetid)
    return "You are not allowed to do this.";

  mysqli_query($db,  "UPDATE planet SET alliance_id=0,status=status&0xFD ".
               "WHERE id='$kid' AND alliance_id='$myrow[alliance_id]'" );
  // affected rows abfragen
  mysqli_query($db,  "UPDATE alliance SET members=members-1 ".
               "WHERE id='$myrow[alliance_id]'" );
  $all["members"] -= 1;
  $text = "You are no longer member of [$all[tag]].";
  insert_into_news ($kid, 11, $text);
}

function join_alliance ($sec) {
  global $db, $myrow, $all;

  if ($myrow["alliance_id"]!=0) {
    return "You are already member of an alliance!.";
  }

  $q = "SELECT id FROM alliance WHERE secret='$sec'";
  $res = mysqli_query($db,  $q );
  if (!$res || mysqli_num_rows($res)==0) {
    return "No such alliance!.";
  }
  $row=mysqli_fetch_row($res);

  mysqli_query($db,  "UPDATE planet SET alliance_id='$row[0]',status=status|2 ".
               "WHERE id='$myrow[id]'" );
  mysqli_query($db,  "UPDATE alliance SET members=members+1 WHERE id='$row[0]'" );

  $myrow["alliance_id"] = $row[0];
  $myrow["status"] = $myrow["status"] | 2;

  $all = get_alliance();

  $text = "$myrow[leader] of $myrow[planetname] (". 
           coord_ref($myrow[x], $myrow[y], "$myrow[x]:$myrow[y]:$myrow[z]") . ")".
          " joined your alliance.";
  insert_into_news ($all["hc"], 11, $text);

  return "";
}

function off_menu () {
  global $Planetid, $all;

  if (!$all || $all["offa"] != $Planetid) return;

    echo <<<EOF
<br>
<form method="post" action="$_SERVER[PHP_SELF]">
<table class="std" border="1" width="650" cellpadding="2">
  <tr><th colspan="2" class="a">Officer options</th></tr>
  <tr>
    <td align="left" width="540">Send a message to <b>all</b> members</td>
    <td class="tdlink"><a href="javascript:popupWindow('All_Message','nsend_message.php?alc=$all[tag]',340,700)">Message</a></td>
  </tr>
  <tr>
    <td align="left"><b>Change alliance secret</b></td>
    <td align="right"><input type=submit value="  Change  " name="osec"></td></tr>
</table>
</form>
EOF;
}

function elect_offa($offa_id) {
  global $Planetid, $all, $db;

  $msg = "Invalid selection.";

  if (!$all || $all["hc"] != $Planetid) return $msg;
  if ($all["offa"] == $offa_id) return;

  if ($all["offa"] > 0) {
    mysqli_query ($db, "UPDATE alliance SET offa=0 ".
       "WHERE id=$all[id]" );
    $msg = "You are not longer Officer in the alliance [$all[tag]].";
    insert_into_news ($all["offa"], 11, $msg);
    $all["offa"] = 0;
    $msg = "Your alliance does not have an Officer anymore.";
  }

  $q = "SELECT x, y, z, leader, planetname FROM planet ".
       "WHERE id=$offa_id AND alliance_id=$all[id] AND status&2 = 0";
  $res = mysqli_query ($db, $q );

  if ($res && mysqli_num_rows($res)) {
    $row = mysqli_fetch_row($res);

    mysqli_query ($db, "UPDATE alliance SET offa='$offa_id' ".
       "WHERE id=$all[id]" );
    $all["offa"] = $offa_id;

    $msg = "You are now Officer in the alliance [$all[tag]].";
    insert_into_news ($all["offa"], 11, $msg);

    $msg = "$row[3] of $row[4] ($row[0]:$row[1]:$row[2]) ".
           "is now Officer in the alliance [$all[tag]].";
  } else 
    return $msg;
  return $msg;
}

function select_offa () {
  global $all, $Planetid, $db;

  $select = "";
  $q = "SELECT x, y, z, leader, planetname, id FROM planet ".
       "WHERE alliance_id=$all[id] AND status&2 = 0";
  $res = mysqli_query ($db, $q );

  if ($res && mysqli_num_rows($res)) {
    $select = "<select name=\"offa\">\n";

    while ($row = mysqli_fetch_row($res)) {

      if ($row[5] != $Planetid) {

        if ($row[5] == $all["offa"]) {
          $select .= "<option selected value=\"$row[5]\">";
        } else {
          $select .= "<option value=\"$row[5]\">";
        }

        $select .= "$row[3] of $row[4] ($row[0]:$row[1]:$row[2])".
             "</option>\n";
      }
    }
    if ($all["offa"] && $all["offa"]>0)
      $select .= "<option value=\"-1\">Clear</option></select>\n";
    else
      $select .= "<option selected value=\"-1\">Clear</option></select>\n";
  }

  return $select;
}

function hc_menu () {
  global $Planetid, $all;

  if (!$all || $all["hc"] != $Planetid) return;

  $selection = select_offa();
  echo <<<EOF
<br>
<form method="post" action="$_SERVER[PHP_SELF]">
<table class="std" border="1" width="650" cellpadding="2">
  <tr><th colspan="3" class="a">HC options</th></tr>
  <tr>
    <td align="left" width="540" colspan="2">Send a message to <b>all</b> members</td>
    <td class="tdlink"><a href="javascript:popupWindow('All_Message','nsend_message.php?alc=$all[tag]',340,700)">Message</a></td>
  </tr>
  <tr>
    <td align="left" width="540" colspan="2">HC Alliance only forum</td>
    <td class="tdlink"><a href="hcforum.php">HC Forum</a></td>
  </tr>
  <tr>
     <td align="left" width="540">Officer of Alliance</td>
     <td align="right">$selection</td>
     <td align="right"><input type=submit value="    Elect    " name="oela"></td>
  </tr>
  <tr>
    <td align="left" colspan="2"><b>Change alliance secret</b></td>
    <td align="right"><input type=submit value="  Change  " name="osec"></td></tr>
  <tr>
    <td align="left" colspan="2"><b>DELETE alliance [$all[tag]]</b></td>
    <td align="right"><input type=submit value="   Delete   " name="odel"></td></tr>
</table>
</form>
EOF;
}

function join_menu () {

  echo <<<EOF
<form method="post" action="$_SERVER[PHP_SELF]">
<table class="std" border="1" width="650">
<tr><th colspan="5" class="a">Join an existing alliance</th></tr>
<tr>
    <th width="150">Secret:</th>
    <td width="400" align="center">
      <input type="text" maxlength="8" size="12" name ="osecret"></td>
</tr>
<tr><td colspan="3" align="center">
  <input type=submit value="   Join   " name="ojoin"></td></tr>
</table>
</form>
EOF;
}

function create_menu () {

  echo <<<EOF
<form method="post" action="$_SERVER[PHP_SELF]">
<table class="std" border="1" width="650">
<tr><th colspan="3" class="a">Create a new alliance</th></tr>
<tr><th width="100">HC public name</th>
    <td align="right">25 chars</td>
    <td align="left">
        <input type="text" maxlength="25" size="25" name ="nhc"></td>
  </tr>
<tr><th width="100">Tag</th>
    <td align="right">4 chars</td>
    <td align="left">
        <input type="text" maxlength="4" size="6" name ="ntag"></td>
  </tr>
<tr><th width="100">Name</th>
    <td align="right">149 chars</td>
    <td align="left">
        <input type="text" maxlength="159" size="65" name ="nname"></td>
  </tr>
<tr><td colspan="3" align="center">
  <input type=submit value="  Create  " name="ncreate"></td></tr>
</table>
</form>
EOF;
}

function create_alliance ($nhc, $ntag, $nname) {
  global $Planetid, $db, $myrow, $all;

  $create_failure = 0;

  // check for all vars
  if ($nhc && $nhc!="" &&
      $ntag && $ntag!="" &&
      $nname && $nname!="") {

    $nhc  = trim(chop($nhc));
    $nname= trim(chop($nname));
    $ntag = trim(chop($ntag));

    $pat = array("/^\((.*)\)/","/^{(.*)}/","/^\[(.*)\]/","/</","/'/");
    $rep =  array("\\1","\\1","\\1","","");

    $nhc   = preg_replace ($pat, $rep, $nhc);
    $nname = preg_replace ($pat, $rep, $nname);
    $ntag  = preg_replace ($pat, $rep, $ntag);

    $nhc   = htmlspecialchars ($nhc);
    $nname = htmlspecialchars ($nname);
    $ntag  = htmlspecialchars ($ntag);

    $nhc  = trim(chop($nhc));
    $nname= trim(chop($nname));
    $ntag = trim(chop($ntag));
  }

  if ($nhc && $nhc!="" &&
      $ntag && $ntag!="" &&
      $nname && $nname!="") {

    // check if tag/name is new
    $q = "SELECT id FROM alliance ".
      "WHERE hcname='$nhc' OR name='$nname' OR tag='$ntag'";
    $res = mysqli_query ($db, $q );

    if ($res && mysqli_num_rows($res)==0) {
      // create alliance
      $pw_tmp = md5 (uniqid (rand()));
      $pw = substr($pw_tmp, 0, 8);

      $q = "INSERT INTO alliance SET tag='$ntag', hcname='$nhc', ".
        "members=1, name='$nname', secret='$pw', hc=$myrow[id]";
      $res = mysqli_query ($db, $q );
      $alid = mysqli_insert_id ($db);

      mysqli_query($db, "UPDATE planet set alliance_id='$alid' ".
                  "WHERE id='$Planetid'");

      $myrow["alliance_id"] = $alid;

      $create_failure = 10;
      $all = get_alliance();

    } else {
      $create_failure = 2;
    }
  } else {
    $create_failure = 1;
  }

  switch($create_failure) {
   case 1: $msg = "Please fil all fields."; break;
   case 2: $msg = "Some fields are already taken - please rechoose"; break;
   case 10: $msg = "Alliance created! Secret: [<b>$pw</b>]"; break;
  }

  return $msg;
}

function change_secret () {
  global $Planetid, $myrow, $db, $all;

  if (!$all || ($all["hc"] != $Planetid && $all["offa"] != $Planetid))
    return "You arent allowed to do this";

  $pw_tmp = md5 (uniqid (rand()));
  $pw = substr($pw_tmp, 0, 8);

  mysqli_query ($db, "UPDATE alliance SET secret='$pw' ".
       "WHERE id='$myrow[alliance_id]'" );
  $msg = "Alliance secret updated.";
  $all["secret"] = $pw;

  return $msg;
}

function trust_member ($mid) {
  global $Planetid, $myrow, $db, $all;

  if (!$all || ($all["hc"] != $Planetid && $all["offa"] != $Planetid))
    return "You arent allowed to do this";

  mysqli_query ($db, "UPDATE planet SET status=status&0xFD ".
               "WHERE alliance_id='$myrow[alliance_id]' AND id='$mid' " );
  $n = mysqli_affected_rows ($db);

  $res = mysqli_query($db, "SELECT leader,planetname FROM planet ".
                     "WHERE id='$mid' " );
  if (!$res || mysqli_num_rows($res) == 0)
    return "No such planet!";
  $row = mysqli_fetch_row($res);

  if ($n == 1) {
    $msg = "$row[0] of $row[1] now is trusted member of [$all[tag]].";

    $text = "You are now a trusted member of [$all[tag]].";
    insert_into_news ($mid, 11, $text);

  } else {
    $msg = "You cannot trust $row[0] of $row[1].";
  }
  return $msg;
}
?>
