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

require "popup_header.inc";
require "standard.php";
require "planet_util.inc";

function make_popup_link ($title, $link, $arg) {
  global $myrow;

  // not used or not usefull
  /* 
 if ($myrow["no_popup"] == 1) {
    return "$_SERVER['PHP_SELF']?$arg";
  } else {
    return "javascript:popupWindow('$title','$link?$arg',340,700)";
  }
  */
  return "javascript:popupWindow('$title','$link?$arg',340,700)";
}

function print_mail ($r) {
  global $Planetid, $folder;

  $id = $r["id"];
  $txt = preg_replace ("/</", "&lt;", $r["text"]);
  $text = preg_replace ("/\n/", "<br>", $txt);

  if ($r["sender"] == $Planetid) {
    $ir = get_coord_name($r["receiver"]);
    $info = "To:  $ir[leader] of $ir[planetname] ($ir[x]:$ir[y]:$ir[z])";
  } else {
    $ir = get_coord_name($r["sender"]);
    $info = "From:  $ir[leader] of $ir[planetname] ($ir[x]:$ir[y]:$ir[z])";
  }
 
  echo "<br><table width=\"650\" border=\"1\" cellpadding=\"5\">\n".
    "<tr class=\"a\">".
    "<th class=\"left\" width=\"400\">$info".
    "</th><th class=\"right\">$r[date] CEST</th></tr>";

  echo "<tr><td colspan=\"2\" align=\"left\"><b>Subject: </b>".
    "$r[subject]<br><br>$text";

  echo "</td></tr><tr><td colspan=\"2\" align=\"center\">";
  if ($folder != 2)
    echo "<a href=\"".
      make_popup_link('Reply_Message','nsend_message.php',"reply=".$id).
      "\">Reply</a>&nbsp;|&nbsp;";

  echo "<a href=\"".
    make_popup_link('Forward_Message','nsend_message.php',"forward=".$id).
    "\">Forward</a>&nbsp;|&nbsp;".
    "<a href=\"".$_SERVER['PHP_SELF']."?folder=$folder&delete=$id\">Delete</a>";

  if ($folder != 3)
    echo "&nbsp;|&nbsp;<a href=\"".$_SERVER['PHP_SELF']."?folder=$folder&save=$id\">Save</a>";
  
  echo "</td></tr></table>";
}

function print_jtd ($text, $cp, $link="", $jscript=0) {
  global $browser_type, $imgpath;

  $width = (100. / 6. ) * $cp;

  if ($browser_type) {
    if ($jscript)
      $l = "\"$link\"";
    else 
      $l = "\"window.open('$link', 'main')\"";

    if ($imgpath && $imgpath != "") {
      $td = "<td align=\"center\" bgcolor=\"\" colspan=\"$cp\" ".
	 "onMouseOver=\"this.bgColor='#686888'\" ".
	 "width=\"$width%\" onMouseOut=\"this.bgColor='' \" ".
	 "onClick=$l ".
	 "style=\"cursor:pointer\">\n";
    } else {
      $td = "<td align=\"center\" bgcolor=\"#F0F0F0\" colspan=\"$cp\" ".
	 "width=\"$width%\" onMouseOver=\"this.bgColor='#D0D0D0'\" ".
	 "onMouseOut=\"this.bgColor='#F0F0F0' \" ".
	 "onClick=$l ".
	 "style=\"cursor:pointer\">\n";
    }
    if ($link != "")
      echo "$td<span class=\"dblue\">$text</span></td>";
    else
      echo "$td$text</td>";

  } else {
    if ($link != "") {
      if ($jscript) $link = "javascript:$link";
      $l = "<a href=\"$link\">$text</a>";
    } else {
      $l = $text;
    }
    echo "<td align=\"center\" width=\"$width%\" colspan=\"$cp\">$l</td>\n";
  }
}

function msg_menu ($folder) {
  global $Planetid, $db;

  echo <<<EOF
<center>
<table width="650" border="1" cellpadding="5">
<tr><th class="a" colspan="6">Global Message Options</th></tr>
<tr>
EOF;
  print_jtd("New message", 6, 
           make_popup_link('New_Message','nsend_message.php','new=1'), 1);
  echo "</tr>\n<tr>";

  $num[1]=0;
  $num[2]=0;
  $num[3]=0;

  $q = "SELECT folder, count(*) FROM msg ".
       "WHERE planet_id='$Planetid' AND folder!=0 GROUP BY folder";
  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) > 0) {
    while(($row=mysqli_fetch_array($result))) {
      $num[$row[0]] = $row[1];
    }
  }

  for ($i=1; $i<=3; $i++) {
    switch($i) {
    case 1: $name="Inbox"; break;
    case 2: $name="Sent messages"; break;
    case 3: $name="Saved messages"; break;
    }
    if ($i==$folder) {
      print_jtd("<b>$name</b>&nbsp;:&nbsp;$num[$i]", 2);
    } else {
      print_jtd("$name&nbsp;:&nbsp;$num[$i]", 2, $_SERVER['PHP_SELF']."?folder=$i");
    }
  }

  echo "</tr>\n<tr>";
  print_jtd("Delete all messages in current folder",3, 
           $_SERVER['PHP_SELF']."?folder=$folder&delete_all=1");

  if ($folder != 3) {
    print_jtd ("Save all messages in current folder", 3, 
              $_SERVER['PHP_SELF']."?folder=$folder&save_all=1");
  } else {
    print_jtd ("Save all messages in current folder", 3);

    if ($folder == 1) {
      $q = "UPDATE msg SET old=1 WHERE planet_id='$Planetid' ".
           "AND folder=1";
      mysqli_query ($db, $q );
    }
  }
  echo "</tr></table>\n";
}

function moc_menu ($x, $y) {

  echo <<<EOF
<br>
<table width="650" border="1" cellpadding="5">
<tr><th class="a" colspan="6">Minister of Communication ($x:$y)</th></tr>
<tr>
EOF;

  print_jtd("Send message to all galaxy members", 3,
           make_popup_link('Galaxy_Message','nsend_message.php','cluster='.$x.'&gal='.$y), 1);
  print_jtd("Send message to all MoC in Cluster", 3,
           make_popup_link('Cluster_Message','nsend_message.php','cluster='.$x), 1);

  echo "</tr></table>\n";
}

/* check browser */
$browser=getenv ("HTTP_USER_AGENT");
if (preg_match("/opera/i", $browser)) {
  $browser_type=0;
} else {
  $browser_type=1;
}

/* clear has_mail flag if present */
if ($myrow["has_mail"] == 1) {
  $myrow["has_mail"] = 0;
  mysqli_query ($db, "UPDATE planet SET has_mail=0 WHERE id='$Planetid'" );
}

require_once "navigation.inc";

echo "<div id=\"main\">\n";

/* top table is written now */
top_header($myrow);

if (!ISSET($_REQUEST["folder"])) $folder=1;
else $folder=$_REQUEST["folder"];

if (ISSET($_REQUEST["delete"])) {
  $delete = $_REQUEST["delete"];
  // if mail is sent to myself it doesnt work :-(
  $q = "UPDATE msg SET folder=0 WHERE mail_id='$delete' AND planet_id='$Planetid' ".
       "AND folder='$folder'";

  $res = mysqli_query($db, $q );
  if (mysqli_affected_rows($db)) {
    $res = mysqli_query($db, "UPDATE mail SET ref=ref-1 ".
		       "WHERE id='$delete'" );
  }
}

if (ISSET($_REQUEST["delete_all"])) {
  $q = "SELECT mail_id FROM msg ".
     "WHERE planet_id='$Planetid' AND folder='$folder'";
  $res = mysqli_query($db, $q );

  if ($res && mysqli_num_rows($res) > 0) {
    while($row=mysqli_fetch_row($res)) {
      mysqli_query($db, "UPDATE mail SET ref=ref-1 ".
		  "WHERE id='$row[0]'" );
    }
    $res = mysqli_query($db, "UPDATE msg SET folder=0 ".
			 "WHERE planet_id='$Planetid' ".
			 "AND folder='$folder'" );
  }
}

if (ISSET($_REQUEST["save"]) && $folder!=3) {
  $res = mysqli_query($db, "UPDATE msg SET folder=3 ".
		     "WHERE mail_id='".$_REQUEST["save"]."' AND planet_id='$Planetid' ".
		     "AND folder='$folder'" );
}

if (ISSET($_REQUEST["save_all"]) && $folder!=3) {
  $res = mysqli_query($db, "UPDATE msg SET folder=3 ".
		     "WHERE planet_id='$Planetid' ".
		     "AND folder='$folder'" );
}

titlebox("Messages");

msg_menu($folder);

$q = "SELECT moc FROM galaxy WHERE x='$myrow[x]' AND y='$myrow[y]' AND moc='$Planetid'";
$res = mysqli_query($db, $q );
if (mysqli_num_rows($res) == 1 || $Planetid==1) {
  moc_menu($myrow["x"], $myrow["y"]);
}

if (ISSET($_REQUEST['new']) || ISSET($_REQUEST['reply']) 
    || ISSET($_REQUEST['forward']) || ISSET($_REQUEST['send_to'])) {
  send_message_form(650);
} else {
  $q = "SELECT mail.id AS id, mail.date AS date, mail.subject AS subject, ".
     "mail.text AS text, mail.sender_id AS sender, mail.planet_id as receiver ".
     "FROM mail, msg ".
     "WHERE msg.planet_id='$Planetid' AND msg.folder='$folder' ".
     "AND mail.id=msg.mail_id ".
     "ORDER BY mail.date DESC";

  $result = mysqli_query ($db, $q );
  
  if ($result && mysqli_num_rows($result) > 0) {
    while ($row=mysqli_fetch_array($result)) {
      print_mail ($row);
    }
  }
}

echo "</div>\n";

require "footer.php";
?>
