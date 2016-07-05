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

require_once "planet_util.inc";
require_once "user_util.inc";

function post_ref ($fstyle, $title, $opt) {
  global $mysettings;

  switch ($fstyle) {
    case 0: $furi = "forump.php"; break;
    case 1: $furi = "posting.php"; break;
    case 2: $furi = "aposting.php"; break;
    case 3: $furi = "hcposting.php"; break;
  }

  // Settings Bit 2: Posting / replys in main frame (no popup)
  if ($mysettings & 2) {
    return "<a href=\"$furi?nowin=1&$opt\">";
  } else {
    return "<a href=\"javascript:popupWindow('$title',".
        "'$furi?$opt',480,700)\">";
  }
}

function print_thread_line($row, $authorized) {
  global $Planetid, $fcreator;

  $tdmod_1 = "";
  $tdmod_2 = "";
  if (strlen($row[4])>14 )
    $tdmod_1 = " class=tiny";
  if (strlen($row[3])>14 )
    $tdmod_2 = " class=tiny";
  $thread_leader = $row[3];
  if ($row[3]=="") $thread_leader = "&nbsp;";

  if ($row[4] == $fcreator
      || $Planetid == $authorized) {
    echo "<tr>".
      "<td><a href=\"".$_SERVER["PHP_SELF"]."?fthread=$row[0]\">$row[1]</a></td>".
      "<td $tdmod_1>$row[4]</td>".
      "<td align=\"right\">$row[5]</td>".
      "<td $tdmod_2>$thread_leader</td>".
      "<td align=\"center\">$row[2]</td>".
      "<td><a href=\"".$_SERVER['PHP_SELF']."?fdel=$row[0]\">D</a></td>".
    "</tr>\n";
  } else {
    echo "<tr>".
      "<td><a href=\"".$_SERVER["PHP_SELF"]."?fthread=$row[0]\">$row[1]</a></td>".
      "<td $tdmod_1>$row[4]</td>".
      "<td align=\"right\">$row[5]</td>".
      "<td $tdmod_2>$thread_leader</td>".
      "<td align=\"center\" colspan=\"2\">$row[2]</td></tr>\n";
   }
}

function print_threads ($fstyle, $gal_id,$authorized) {
  global $db;

  echo "\n<table width=\"650\" border=\"1\" cellpadding=\"3\">\n".
    "<tr class=\"f$fstyle\">\n".
    "<th width=\"290\" align=\"left\">Subject</th>\n".
    "<th width=\"100\" align=\"left\">Creator</th>\n".
    "<th width=\"45\" align=\"right\">Posts</th>\n".
    "<th width=\"80\" align=\"left\">Last post</th>\n".
    "<th width=\"135\" align=\"center\" colspan=\"2\">Date</th>\n".
    "</tr>\n";

  if ($fstyle == 3) {
/* fix me */
    $q ="SELECT politics.id as id,subject, ".
       "date_format(date,'%D %b %H:%i') AS date, ".
       "hcname, creator, replies+1, date AS d ".
       "FROM politics, planet, alliance ".
       "WHERE gal_id='$gal_id' AND planet.id=politics.planet_id ".
       "AND planet.alliance_id = alliance.id ".
       "ORDER by d DESC";
  } else {
/*
    $q ="SELECT politics.id as id,subject, ".
       "date_format(date,'%D %b %H:%i') AS date, ".
       "leader, creator, replies+1, date AS d FROM politics, planet ".
       "WHERE gal_id='$gal_id' AND planet.id=politics.planet_id ".
       "ORDER by d DESC";
*/
    $q = "SELECT politics.id as id,subject, ".
       "date_format(date,'%D %b %H:%i') AS date, ".
       "leader, creator, replies+1, date AS d ".
       "FROM politics left join planet on planet.id=politics.planet_id ".
       "WHERE gal_id='$gal_id' ORDER by d DESC";
  }

  $result = mysqli_query ($db, $q );
  
  if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_row($result)) {
      print_thread_line($row,$authorized);
    }
  } else {
    echo "<tr><td colspan=\"6\">No threads posted</td></tr>\n";
  }
  echo "</table>\n";
}

function show_thread_post ($fstyle, $row, $thread, $authorized) {
  global $Planetid, $fcreator;

  // $txt = ereg_replace ("<", "&lt;", $row[2]);
  // $text = ereg_replace ("\n", "<br>", $txt);
  $txt = preg_replace ("/</", "&lt;", $row[2]);
  $text = preg_replace ("/\n/", "<br>", $txt);
  
  if ($fstyle==3) {
    echo "<tr><td valign=\"top\">$row[1]<br>".
      "<b>$row[0]</b> [$row[5]]</td>".
      "<td align=\"left\" valign=\"top\">$text";
  } else {
    echo "<tr><td valign=\"top\">$row[1]<br>".
      "<b>$row[0]</b> ($row[5]:$row[6]:$row[7])</td>".
      "<td align=\"left\" valign=\"top\">$text";
  }

  if ($fcreator == $row[0]) {
    echo "<br><a href=\"".$_SERVER["PHP_SELF"]."?fthread=$thread&fdelp=$row[4]\">".
      "Delete</a> | ". post_ref($fstyle, "Edit_posting","edit=$row[4]") . 
      "Edit</a>";
  } else if ($Planetid == $authorized) {
    echo "<br><a href=\"".$_SERVER["PHP_SELF"]."?fthread=$thread&fdelp=$row[4]\">".
      "Delete</a>";
  }

  echo "</td></tr>";
}

function show_thread ($fstyle, $fthread, $menu, $authorized) {
  global $Planetid, $db, $mysettings;

echo <<<EOF
<table width="650" border="1" cellpadding="5">
  <tr class="f$fstyle">
  <th width="150">Date / Author</th>
  <th width="500">Post</th><tr>
EOF;

  if ($fstyle==3) {
    $q = "SELECT hcname,date_format(date,'%D %b %H:%i') AS date, text, ".
       "date AS d, poltext.id, tag FROM poltext, planet, alliance ".
       "WHERE poltext.thread_id='$fthread' AND planet.id=poltext.planet_id ".
       "AND planet.alliance_id = alliance.id ";
  } else { 
    $q = "SELECT leader,date_format(date,'%D %b %H:%i') AS date, text, ".
       "date AS d, poltext.id, x,y,z FROM poltext,planet ".
       "WHERE poltext.thread_id='$fthread' AND planet.id=poltext.planet_id ";
  }

  // Settings Bit 1: Thread forward or Reverse by date
  if ($mysettings & 1) {
    $q .= "ORDER BY d DESC";
  } else {
    $q .= "ORDER by d ASC";
  }

  $result = mysqli_query ($db, $q );
  
  if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_row($result)) {
      show_thread_post($fstyle, $row, $fthread,$authorized);
    }
  }   

  echo "<tr><td align=\"center\" colspan=\"2\" width=\"650\">".
   "$menu</td></tr></table>";
}

function delete_thread ($thread_id, $gal_id, $authorized) {
  global $db, $Planetid, $fcreator;

  $q = "DELETE FROM politics WHERE id='$thread_id' AND gal_id='$gal_id' ".
     "AND (creator='$fcreator' OR '$Planetid'='$authorized')";

  $result = mysqli_query ($db, $q );

  if(mysqli_affected_rows($db) == 1) {
    $qp = "DELETE FROM poltext WHERE thread_id='$thread_id' ".
       "AND gal_id='$gal_id'";
    $result = mysqli_query ($db, $qp );
  }
}

function delete_posting ($thread_id, $post_id, $gal_id, $authorized) {
  global $db, $Planetid;

  $q = "DELETE FROM poltext WHERE id='$post_id' AND thread_id='$thread_id' ".
     "AND (planet_id='$Planetid' OR '$Planetid'='$authorized')";
  $result = mysqli_query ($db, $q );

  if(mysqli_affected_rows($db) == 1) {
    $q = "UPDATE politics SET replies=replies-1 ".
       "WHERE poltext.id='$post_id' AND politics.id=poltext.thread_id";
    $result = mysqli_query ($db, $q );
  }
}

function check_valid ($thread_id, $gal_id) {
  global $db, $subject;

  // check if thread belongs to this gal
  $q ="SELECT subject FROM politics ".
     "WHERE gal_id='$gal_id' AND id='$thread_id' ";

  $result = mysqli_query ($db, $q );
  
  if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_row($result)) {
      $subject=$row[0];
    }
    return 1;
  }

  return 0;
}

function forum_head_line ($fstyle, $fid) {
  global $db;

  switch ($fstyle) {
  case 0: { 
    $ret = "Universe Forum"; 
    break;
  }
  case 1: {
    $q = "SELECT name,x,y FROM galaxy WHERE id='$fid'";
    $res = mysqli_query ($db, $q );
    $grow = mysqli_fetch_array($res);
    $ret = "Politics: $grow[name] ($grow[x]:$grow[y])";
    break;
  }
  case 2: {
    global $all;
    $ret = "Alliance Forum [$all[tag]]"; 

    break;
  }
  case 3: {
    $ret = "HC Alliance Forum";
    break;
  }
  }
  return $ret;
}

function forum_head ($fstyle, $fid, $fthread=0) {
  global $subject;

  if ($fthread==0) {
    $menu = post_ref($fstyle, "Create_Posting","create=1") .
       "Create new thread</a>&nbsp;|&nbsp;<a href=\"".$_SERVER['PHP_SELF']."\">Reload</a>";

    $fline = "<th colspan=\"2\" align=\"center\" class=\"f$fstyle\">" . 
       forum_head_line ($fstyle, $fid) . "</th>";
  } else {
    $menu = post_ref($fstyle, "Create_Posting", "reply=$fthread") .
       "Post follow up</a>".
       "&nbsp;|&nbsp;<a href=\"".$_SERVER["PHP_SELF"]."?fthread=$fthread\">Reload</a>".
       "&nbsp;|&nbsp;<a href=\"".$_SERVER["PHP_SELF"]."\"?>Overview</a>";

    $fline = "<td class=\"f$fstyle\"><b>Subject:</b> $subject</td>";
  }
 
  echo <<<EOF
<table width="650" border="1" cellpadding="5">
  <tr>$fline</tr>
  <tr><td align="center">$menu</td></tr>
</table>
<br>
EOF;

  return $menu;
}

////////////////////////////////////////////////////////////////////////////
// EXTERN function
////////////////////////////////////////////////////////////////////////////

function forum_init ($fstyle, $fid) {
  global $authorized, $Planetid, $db, $myrow;
  global $fcreator, $fcoords;

  $authorized = 0;
  $fcreator = $myrow["leader"];
  $fcoords  = "($myrow[x]:$myrow[y]:$myrow[z])";

  if ($Planetid <= 2) 
    $authorized = $Planetid;

  if ($fstyle==1) {
    // politics - reset light
    $q ="UPDATE planet SET has_politics=$myrow[has_politics] & 0xfe ". 
        "WHERE id='$Planetid'";
    $result = mysqli_query ($db, $q );
    $myrow["has_politics"] = $myrow["has_politics"] & 0xfe;

    $q = "SELECT moc FROM galaxy WHERE x='$myrow[x]' AND y='$myrow[y]'";
    $result = mysqli_query ($db, $q );

    if ($result && mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_row($result);
      if ($row[0] == $Planetid)
        $authorized = $Planetid;
    }
  } else if ($fstyle==2) {
    // politics - reset light
    $q ="UPDATE planet SET has_politics=$myrow[has_politics] & 0xfd ".
        "WHERE id='$Planetid'";
    $result = mysqli_query ($db, $q );
    $myrow["has_politics"] = $myrow["has_politics"] & 0xfd;

    $q = "SELECT hc FROM alliance WHERE id=$myrow[alliance_id]";
    $result = mysqli_query ($db, $q );

    if ($result && mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_row($result);
      if ($row[0] == $Planetid)
        $authorized = $Planetid;
    }
  } else if ($fstyle==3) {
    $q = "SELECT hcname,tag FROM alliance ".
         "WHERE alliance.id = $myrow[alliance_id]";
    $result = mysqli_query ($db, $q );
    if ($result && mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_row($result);
      $fcreator = $row[0];
      $fcoords = "[$row[1]]";
    } else {
      die;
      return "Error";
    }
  }
}

function forum_submit ($fstyle, $fid, $fthread) {
  global $authorized;
  global $fdel, $fdelp;

  $msg = "";

  if ($fthread) {
    if ($fdelp) {
      delete_posting ($fthread, $fdelp, $fid, $authorized);
    }
    
    if (check_valid ($fthread, $fid) != 1) {
      error_logout();
    }
  } else {
    if ($fdel) {
      delete_thread ($fdel, $fid, $authorized);
    }
  }

  return $msg;
}

function forum_title ($fstyle) {

  switch ($fstyle) {
  case 0: return "Forum"; break;
  case 1: return "Politics"; break;
  case 2: return "Alliance"; break;
  case 3: return "HC Forum"; break;
  }
}

function forum_show_thread ($fstyle, $fid, $fthread) {
  global $authorized;

  $menu = forum_head ($fstyle, $fid, $fthread);
  show_thread ($fstyle, $fthread, $menu, $authorized);
}

function forum_list_thread($fstyle, $fid) {
  global $authorized;

  forum_head ($fstyle, $fid);
  print_threads($fstyle, $fid, $authorized);
}

?>
