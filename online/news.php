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

function add_img_path($name, $alt) {
  global $imgpath;

  if ($imgpath && $imgpath != "") {
    return "<img src=\"$imgpath/myphppa/$name\" width=\"32px\"".
           " height=\"32px\" alt=\"$alt\" border=0>";
  } else {
    return $alt;
  }
}

function print_news_head ($date, $type, $id, $ntick) {

  switch ($type) {
  case 1: $img = add_img_path("battle.png","Bat"); $txt = "Military"; break;
  case 2: $img = add_img_path("donation.png","Don"); $txt = "Donation"; break;
  case 3: 
    $img = "<a href=\"research.php\">". 
       add_img_path("science.png","Sci") ."</a>"; 
    $txt = "Science"; break;
  case 4: 
    $img = "<a href=\"construct.php\">".
       add_img_path("building.png","Bui")."</a>"; 
    $txt = "Building"; break;
  case 5: $img = add_img_path("waves.png","Wav"); $txt = "Wave Jam"; break;
  case 6: $img = add_img_path("launch.png","Lau"); $txt = "Launch"; break;
  case 7: $img = add_img_path("friends.png","Fri"); $txt = "Friendly"; break;
  case 8: $img = add_img_path("hostile.png","Hos"); $txt = "Enemy"; break;
  case 9: $img = add_img_path("recall.png","Rec"); $txt = "Recalled"; break;
  case 10: 
  case 11: $img = add_img_path("message.png","Msg"); $txt = "Message"; break;
  }

  $stick = "";
  if ($ntick !=0)
    $stick = " MyT $ntick,";
  echo "<tr><td width=\"32\" height=\"32\" class=\"c\">$img</td>".
       "<td width=\"540\">$date CEST,$stick $txt</td>".
       "<td width=\"70\" align=\"right\"><a href=\"".$_SERVER['PHP_SELF']."?hide=$id\">".
       "delete</a></td></tr>";
}

/* clear has_news flag if present */
if ($myrow["has_news"] == 1) {
  $myrow["has_news"] = 0;
  mysqli_query ($db, "UPDATE planet SET has_news=0 WHERE id='$Planetid'" );
}

$msg = "";
if (ISSET($_REQUEST["clear"]) && $_REQUEST["clear"] != "") {

  $myrow["news_deleted"] = urldecode($_REQUEST["clear"]);
  if ($myrow["news_deleted"] == "recover") {
    $myrow["news_deleted"] = date ("y-m-d H:i:s", time() - 86400);
    $msg = "News recovered for past 24 hours.\n<br>\n";
  } else {
    $msg = "News deleted until " . $myrow["news_deleted"] . "\n<br>\n";
  }
  mysqli_query ($db, "UPDATE planet SET news_deleted='".$myrow['news_deleted']."' ".
               "WHERE id='$Planetid'" );
}

if (ISSET($_REQUEST["hide"])) {
  $hide = $_REQUEST["hide"];
  mysqli_query ($db, "UPDATE news SET hidden=1 ".
    "WHERE planet_id='$Planetid' AND id='$hide'" );
}

require_once "navigation.inc";

echo "<div id=\"main\">\n";

/* top table is written now */
top_header($myrow);

titlebox("News", $msg);

// $_SESSION["ImgPath"] = "img";
// $imgpath = $_COOKIE["imgpath"];
?>

<center>
<table  width="650" border="1" cellpadding="2" >
<tr><th class="a" colspan="10">Legend</th></tr>
<?php
if ($imgpath && $imgpath!= "") {
  // image urls
  echo "<tr align=\"center\">";
  echo "<td class=\"c\">".add_img_path("battle.png","Bat")."</td>\n";
  echo "<td class=\"c\">".add_img_path("donation.png","Don")."</td>\n";
  echo "<td class=\"c\">".add_img_path("science.png","Sci")."</td>\n";
  echo "<td class=\"c\">".add_img_path("building.png","Bui")."</td>\n";
  echo "<td class=\"c\">".add_img_path("waves.png","Wav")."</td>\n";
  echo "<td class=\"c\">".add_img_path("launch.png","Lau")."</td>\n";
  echo "<td class=\"c\">".add_img_path("friends.png","Fri")."</td>\n";
  echo "<td class=\"c\">".add_img_path("hostile.png","Hos")."</td>\n";
  echo "<td class=\"c\">".add_img_path("recall.png","Rec")."</td>\n";
  echo "<td class=\"c\">".add_img_path("message.png","Msg")."</td>\n";
  echo "</tr>";
}
?>
<tr align="center" >
<td class=\"c\">Combat</td>
<td class=\"c\">Donation</td>
<td class=\"c\">Science</td>
<td class=\"c\">Building</td>
<td class=\"c\">Waves</td>
<td class=\"c\">Launch</td>
<td class=\"c\">Friends</td>
<td class=\"c\">Hostile</td>
<td class=\"c\">Recall</td>
<td class=\"c\">Message</td>
</tr>
</table>
<br>

<a href="<?php echo $_SERVER['PHP_SELF'] . "?clear=" . urlencode(date("y-m-d H:i:s")); ?>">Clear all news</a>
&nbsp;|&nbsp;
<a href="<?php echo $_SERVER['PHP_SELF'] . "?clear=recover"; ?>">Recover news</a>

<br>
<br>

<table  width="650" border="1" cellpadding="2" >
<tr><th class="a" colspan="3">Private News</th></tr>

<?php
$q = "SELECT date, type, text, id, tick ".
     "FROM news WHERE planet_id='$Planetid' ".
     "AND date > '$myrow[news_deleted]' AND hidden=0 ".
     "ORDER BY date DESC";

$result = mysqli_query ($db, $q );

if ($result && mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_row($result)) {
    print_news_head ($row[0], $row[1], $row[3], $row[4]);
    echo "<tr><td width=\"32\">&nbsp;</td>".
         "<td width=\"618\" colspan=\"2\">$row[2]</td></tr>";
  }
} else {
  echo "<tr><td width=\"30\">&nbsp;</td><td width=\"620\">No News</td></tr>";
}
?>
</table>
</center>
</div>

<?php

require "footer.php";
?>
