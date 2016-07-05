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

function insert_message ($target_id,  $tname, $subject, $text, $copy=1) {
  global $db, $Planetid;

  $q = "INSERT INTO mail SET planet_id='$target_id',sender_id='$Planetid',".
       "date=now(),subject='$subject',text='$text', ref=2";
  $res = mysqli_query ($db, $q );
  $mid = mysqli_insert_id($db);

  if ($res) {
    $msg = "Message was succesfully sent to ($tname)";
    mysqli_query ($db, "UPDATE planet SET has_mail=1 WHERE id='$target_id'" );

    mysqli_query ($db, "INSERT INTO msg SET mail_id='$mid', ".
                 "planet_id='$target_id'" );
    if ($copy) {
      mysqli_query ($db, "INSERT INTO msg SET mail_id='$mid', ".
                   "planet_id='$Planetid',folder=2,old=1" );
    }
  } else {
    $msg = "Failed to send message";
  }
  return $msg;
}

function send_message_form ($width, $moc=0) {
  global $send_to, $mail_id, $Planetid, $db;

  if(ISSET($_REQUEST["reply"])) $reply = $_REQUEST["reply"];
  if(ISSET($_REQUEST["forward"])) $forward = $_REQUEST["forward"];
  if(ISSET($_REQUEST["cluster"])) $cluster = $_REQUEST["cluster"];
  if(ISSET($_REQUEST["gal"])) $gal = $_REQUEST["gal"];
  if(ISSET($_REQUEST["hc"])) $hc = $_REQUEST["hc"];
  if(ISSET($_REQUEST["alc"])) $alc = $_REQUEST["alc"];

  echo <<<EOF
<form method="post" action="$_SERVER[PHP_SELF]">
<table width="$width" border="1">
<tr><th class="a" colspan="2">
EOF;

  $text = "";
  $subject = "";
  $x = "";
  $y = "";
  $z = "";

  if (ISSET($_REQUEST["reply"]) || ISSET($_REQUEST["forward"])) {

    if(ISSET($_REQUEST["reply"])) {
      $q = "SELECT mail.subject AS subject, mail.text AS text, ".
	 "planet.x AS x, planet.y AS y, planet.z AS z FROM mail, planet ".
	 "WHERE mail.id='$mail_id' AND mail.planet_id='$Planetid' ".
	 "AND planet.id=mail.sender_id";
    } else {
      $q = "SELECT mail.subject AS subject, mail.text AS text, mail.sender_id ".
	 "FROM mail, msg WHERE mail.id='$mail_id' AND msg.mail_id=mail.id ".
	 "AND msg.planet_id='$Planetid'";
    }

    $result = mysqli_query ($db, $q );

    if ($result && mysqli_num_rows($result) == 1) {
      $row=mysqli_fetch_array($result);

      $subject = $row[0];
      $text = ereg_replace ("<", "&lt;", $row[1]);
      if ($reply) {
        $x = $row[2];
        $y = $row[3];
        $z = $row[4];
      } else {
        $sender_id = $row[2];
      }
    }

    if (ISSET($_REQUEST["reply"])) {
      echo "Reply to message</th></tr>";
      $subject = "Re: $subject";
      $text = "--------------------\n$text";
    } else {
      $sr = get_coord_name($sender_id);
      echo "Forward message</th></tr>";
      $subject = "Fw: $subject";
      $text = "Forwarded Message from: $sr[4] of $sr[3] ".
              "($sr[0]:$sr[1]:$sr[2])\n\n$text"; 
    }

  } else {
    if (ISSET($_REQUEST["cluster"]) && $moc==1) {
      if(ISSET($_REQUEST["gal"])) {
        echo "Send galaxy message</th></tr>";
      } else {
        echo "Send cluster message</th></tr>";
      }
    } else if (ISSET($_REQUEST["hc"])) {
      echo "Send HC message</th></tr>";
    } else if (ISSET($_REQUEST["alc"])) {
      echo "Send alliance member message</th></tr>";
    } else {
      echo "Send new message</th></tr>";
    }
  }

  if (ISSET($send_to)) {
    $coords = get_coord ($send_to);

    if ($coords) {
      $x = $coords[0];
      $y = $coords[1];
      $z = $coords[2];
    }
  }

  echo <<<EOF
<tr><th align="left" class="a" width="22%">To:</th>
<th align="left" class="a">Subject:</th></tr>
<tr><td width="22%">
EOF;
  if (ISSET($_REQUEST["cluster"]) && $moc==1) {
    if (ISSET($_REQUEST["gal"])) {
      echo "<b>Galaxy ($cluster:$gal)</b>";
      echo "<input type=\"hidden\" name=\"gal\" value=\"$gal\">\n";
    } else {
      echo "<b>Cluster #$cluster</b>";
    }
    echo "<input type=\"hidden\" name=\"cluster\" value=\"$cluster\">\n";
  } else if (ISSET($_REQUEST["hc"])) {
    echo "<b>HC of [$hc]</b>".
      "<input type=\"hidden\" name=\"hc\" value=\"$hc\">\n";
  } else if (ISSET($_REQUEST["alc"])) {
    echo "<b>Members of [$alc]</b>".
      "<input type=\"hidden\" name=\"alc\" value=\"$alc\">\n";
  } else {
    echo <<<EOF
<input type="text" name="x" size="2" maxlength="2" value="$x">
<input type=text name="y" size="2 maxlength="2" value="$y">
<input type=text name="z" size="2 maxlength="2" value="$z">
EOF;
  }

  echo <<<EOF
</td><td width="78%" align="left">
<input type="text" name="subject" size="30" maxlength="50" value="$subject">
</td></tr><tr><td colspan="2">
<textarea name="text" cols="60" rows="8" wrap="virtual">$text</textarea>
</td></tr><tr><td colspan="2" align="center">
<input type="submit" name="submit" value="       Send       ">
&nbsp;&nbsp; &nbsp;
<input type="reset" value="Clear message">
&nbsp;&nbsp; &nbsp;
</td>
</tr>
</table>
</form>
EOF;

}

if (ISSET($_REQUEST["reply"])) {
     $mail_id = $_REQUEST["reply"]; 
} else if (ISSET($_REQUEST["forward"])) {
     $mail_id = $_REQUEST["forward"]; 
}



if (ISSET($_REQUEST["submit"])) {
  
  if (ISSET( $_REQUEST["subject"] )) $subject = $_REQUEST["subject"] ;
  if (ISSET( $_REQUEST["text"] )) $text = $_REQUEST["text"] ;
  if (ISSET( $_REQUEST["x"] )) $x = $_REQUEST["x"] ;
  if (ISSET( $_REQUEST["y"] )) $y = $_REQUEST["y"] ;
  if (ISSET( $_REQUEST["z"] )) $z = $_REQUEST["z"] ;

  if (! ISSET($subject)) $subject = "";
  if (! ISSET($text)) $text = "";

  if (ISSET($x) && ISSET($y) && ISSET($z)) {
    $target_id = get_id ($x, $y, $z);

    if ($target_id) {
      $msg = insert_message ($target_id, "($x:$y:$z)", $subject, $text);
    } else {
      $msg = "<span class=\"red\">Invalid target coordinates</span>";
    }
  } else {
    if (ISSET($_REQUEST["cluster"])) {
      $cluster = $_REQUEST["cluster"] ;
      if (ISSET($_REQUEST["gal"])) {
        $gal = $_REQUEST["gal"] ;
        $q = "SELECT id FROM planet WHERE x='$cluster' AND y='$gal'";
        $msg = "Message sent to galaxy";
      } else {
        $q = "SELECT moc FROM galaxy WHERE x='$cluster'";
        $msg = "Message sent to cluster";
      }
      $resp = mysqli_query ($db, $q );
      $cnt = mysqli_num_rows($resp);
    
      while ($row=mysqli_fetch_row($resp)) {

        $q = "INSERT INTO mail SET planet_id='$row[0]',sender_id='$Planetid',".
             "date=now(),subject='$subject',text='$text', ref=1";
        $res = mysqli_query ($db, $q );
        $mid = mysqli_insert_id($db);

        if ($res) {
          if ($row[0] != $Planetid) {
            mysqli_query ($db, "UPDATE planet SET has_mail=1 WHERE id='$row[0]'" );
            mysqli_query ($db, "INSERT INTO msg SET mail_id='$mid', ".
                         "planet_id='$row[0]'" );
          } else {
            mysqli_query ($db, "INSERT INTO msg SET mail_id='$mid', ".
                         "planet_id='$Planetid',folder=2,old=1" );
          }
        }      
      }
    } else if (ISSET($_REQUEST["hc"])) {
      $hc = $_REQUEST["hc"] ;
      $q = "SELECT hc FROM alliance WHERE tag='$hc'";
      $res = mysqli_query ($db, $q );
      if ($res && mysqli_num_rows($res)>0) {
        $row = mysqli_fetch_row($res);
        $msg = insert_message ($row[0], "[$hc]", "HC msg: ".$subject, $text, 0);
      }
    } else if (ISSET($_REQUEST["alc"])) {
      $alc = $_REQUEST["alc"] ;
      $q = "SELECT id FROM alliance WHERE tag='$alc'";
      $res = mysqli_query ($db, $q );
      if ($res && mysqli_num_rows($res)==1) {
	$row = mysqli_fetch_row($res);

	$q = "SELECT id FROM planet WHERE alliance_id=$row[0]";
	$resp = mysqli_query ($db, $q );
	$cnt = mysqli_num_rows($resp);

	while ($cnt && $row=mysqli_fetch_row($resp)) {
	  $q = "INSERT INTO mail SET planet_id='$row[0]',sender_id='$Planetid',".
	    "date=now(),subject='[$alc]: $subject',text='$text', ref=1";
	  $res = mysqli_query ($db, $q );
	  $mid = mysqli_insert_id($db);

	  if ($res) {
	    if ($row[0] != $Planetid) {
	      mysqli_query ($db, "UPDATE planet SET has_mail=1 WHERE id='$row[0]'" );
	      mysqli_query ($db, "INSERT INTO msg SET mail_id='$mid', ".
			    "planet_id='$row[0]'" );
	    } else {
	      mysqli_query ($db, "INSERT INTO msg SET mail_id='$mid', ".
			    "planet_id='$Planetid',folder=2,old=1" );
	    }
	  }
	}
      }
    }
  }
}

?>
