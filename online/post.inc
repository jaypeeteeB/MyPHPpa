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

if (!ISSET($post_type)) {
  echo "This shouldnt happen";
  die;
}

if (ISSET($_REQUEST["nowin"]) && $_REQUEST["nowin"] == 1) {
  $close_script = "";
} else  {
  $close_script="<SCRIPT LANGUAGE=\"javascript\">\n".
    "<!--\n".
    "// Begin\n".
    "function wclose() {".
    "  this.close();".
    "}\n// END\n//-->\n</SCRIPT>\n";
}

if (ISSET($_REQUEST["create"])) {
     $extra_header = "   <TITLE>Create new thread</TITLE>\n$close_script";
     // $mail_id = unknwoon useage; 
} else if (ISSET($_REQUEST["reply"])) {
     $extra_header = "   <TITLE>Reply</TITLE>\n$close_script";
} else {
     $extra_header = "   <TITLE>Edit</TITLE>\n$close_script";
}
require "standard_pop.php";

if (ISSET($_REQUEST["submit"])) {
  require "post_func.inc";
  check_post();
}

require_once "planet_util.inc";
require_once "logging.php";

if ($post_type == 1) {
  // politics
  $gal_id = get_galaxy_id( $myrow["x"], $myrow["y"]);
  $fscript = "politics.php";
  $fname = "Politics";
} else if ($post_type == 2) {
  // alliance
  $gal_id = $myrow["alliance_id"] + 1024;
  $fscript = "allforum.php";
  $fname = "Alliance";
} else if ($post_type == 0) {
  // world forum
  $gal_id = 0;
  $fscript = "forum.php";
  $fname = "Forum";
} else if ($post_type == 3) {
  // hcposting
  $fscript = "hcforum.php";
  $fname = "HC Alliance Forum";

  require_once "alliance_func.inc"; 
  $all =  get_alliance();

  if ($all && $all["hc"] == $Planetid &&
      ($all["members"] > 2 || $Planetid<=2)) {
    $gal_id = 1023;
  } else {
    echo "You are not allowed to be here.";
    die;
  } 
}

$subject = "";
$reply = "";
$edit = "";
$text = "";
if (ISSET($_REQUEST["subject"])) 
  $subject = $_REQUEST["subject"];
if (ISSET($_REQUEST["reply"])) 
  $reply = $_REQUEST["reply"];
if (ISSET($_REQUEST["edit"])) 
  $edit = $_REQUEST["edit"];
if (ISSET($_REQUEST["text"])) 
  $text = $_REQUEST["text"];

if (ISSET($_REQUEST["submit"])) {

  if ((("$subject"!="") || $reply || $edit)  
      && "$text"!="") {

    if (!$reply && !$edit) {
      if ($post_type == 3) {
	$creator = "$all[hcname]";
      } else {
	$creator = "$myrow[leader]";
      }
      $subject = mysqli_real_escape_string($db, $subject);
      $q = "INSERT INTO politics SET planet_id='$Planetid', date=NOW(),".
	 "subject='$subject',gal_id='$gal_id',creator='$creator'";
      $result = mysqli_query ($db, $q);
      $thid = mysqli_insert_id ($db);

    } else {
      // check if id is valid

      if ($reply) {
        $postid = $reply;
        $q = "SELECT gal_id FROM politics ".
 	   "WHERE id='$reply' AND gal_id='$gal_id'";
      } else {
        $postid = $edit;
        $q = "SELECT gal_id FROM politics,poltext ".
           "WHERE poltext.id='$edit' AND politics.id=poltext.thread_id ".
           "AND gal_id='$gal_id'";
      }
      $result = mysqli_query ($db, $q);

      if ($result && mysqli_num_rows($result) == 1) {
	$thid = $postid;

        if ($reply) {
          $q = "UPDATE politics SET date=NOW(),planet_id='$Planetid', ".
             "replies=replies+1 WHERE id='$thid'";
          $result = mysqli_query ($db, $q);

	  if ($gal_id != 0) {
	    if ($post_type == 1) {
	      $q = "UPDATE planet set has_politics = has_politics | 1 ".
		"WHERE id!='$Planetid' ";
	      $q .= "AND x='$myrow[x]' AND y='$myrow[y]'";
	    } else if($post_type == 2 || $post_type == 3) {
	      $q = "UPDATE planet set has_politics = has_politics | 2 ".
		"WHERE id!='$Planetid' ";
	      $q .= "AND alliance_id='$myrow[alliance_id]'";
	    } 
	    $result = mysqli_query ($db, $q);
	  }
        }
      } else {
        echo "Wrong parameter found - this incidence will be reported<br>";
        $text = mysqli_real_escape_string($db, $text);
	$q = "INSERT INTO news set planet_id=1,date=now(),type=10,".
	   "text='$fname warning\npid: $Planetid\ntext=$text\n'";
	$result = mysqli_query ($db, $q);
        do_log_me (3, 1, "Wrong $fname post");
      }
    }

    if ($thid) {
      if (!$edit) {
        $text = mysqli_real_escape_string($db, $text);
        $q = "INSERT INTO poltext SET thread_id='$thid',text='$text',".
	   "planet_id='$Planetid',date=NOW()";
        $msg = "Successfully posted";
      } else {
        // edit
        $dbtext = mysqli_real_escape_string($db, $text . "\n*** Edited ***");
        $q = "UPDATE poltext SET text='$dbtext' ".
           "WHERE id='$thid' AND planet_id='$Planetid'";
        $text = $dbtext;
        $msg = "Successfully edited your post";
      }
      $result = mysqli_query ($db, $q);

    }

  } else {
    echo "You have to suply all fields<br>";
  }
}

echo "<center><br>";
if (ISSET($msg)) {
  echo "$msg<br>";
}

if ($reply) {
  $act = $_SERVER["PHP_SELF"]."?reply=$reply";
  $postid = $reply;
} else if ($edit) {
  $act = $_SERVER["PHP_SELF"]."?edit=$edit";
} else {
  $act = $_SERVER["PHP_SELF"];
}

echo "<form method=\"post\" action=\"$act\">";
echo "<table width=\"500\" border=\"1\">";
echo "<tr><th class=\"f$post_type\" colspan=\"2\">";

$text = "";
$subject = "";

if ($reply) {
  $q = "SELECT subject FROM politics WHERE id='$reply'";
  $result = mysqli_query ($db, $q);

  if ($result && mysqli_num_rows($result) == 1) {
    $row=mysqli_fetch_array($result);

    // $subject = ereg_replace ("<", "&lt;",$row[0]);
    $subject = preg_replace ("/</", "&lt;",$row[0]);
  } else 
    $subject = "Empty";

  $text = "";
  echo "$fname: Reply to thread</th></tr>";
} else if ($edit) {
  // needs check for planetid

  $q = "SELECT subject,text FROM politics, poltext ".
    "WHERE politics.id=poltext.thread_id AND poltext.id='$edit'";
  $result = mysqli_query ($db, $q);

  if ($result && mysqli_num_rows($result) == 1) {
    $row=mysqli_fetch_array($result);

    $subject = preg_replace ("/</", "&lt;",$row[0]);
    $text = preg_replace ("/</", "&lt;",$row[1]);
  } else {
    $subject = "Empty";
  }

  echo "$fname: Edit Posting</th></tr>";
  
} else {

  $text = "";
  echo "$fname: New thread</th></tr>";
}

echo "<tr><td class=\"f$post_type\"  width=\"80\">Subject:</th>";
echo "<td width=\"420\">";

if (!$subject || $subject == "") {
  echo "<input type=\"text\" name=\"subject\" size=\"55\" maxlength=\"249\">";
} else {
  echo "$subject";
}
?>

</td></tr>
<tr><td colspan="2" width="50">
<textarea name="text" cols="60" rows="12" wrap="virtual"><?php echo $text ?></textarea>
</td></tr><tr><td colspan="2" align="center">
<input type="submit" name="submit" value="      Submit      ">
&nbsp;&nbsp; &nbsp;
<input type="reset" value="Clear posting">
&nbsp;&nbsp; &nbsp;
</td>
</tr>
</table>
</form>

<table width="500" border="0"><tr><td align="right">
<?php
  if($mysettings & 2) {
    if ($reply)
      echo "<a href=\"$fscript?fthread=$reply\">Return to thread</a>\n";
    else
      echo "<a href=\"$fscript\">Return to $fname</a>\n";
  } else {
    echo "<a href=\"javascript:close()\">Close this Window</a>\n";
  }

echo "</td></tr></table>\n\n";

require "footer.php";
?>
