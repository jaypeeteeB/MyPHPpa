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

include_once "auth_check.php";

function my_header ($extra=0,$sess=1,$tickjs=1) {
  global $imgpath, $game, $version, $ticktime;
  global $Planetid, $mytick, $mysettings;

  $gdate = date("D, d M Y H:i:s", time() - 3599);
  // header ("Expires: $gdate GMT");
  header ("Expires: Sat, 1 Jan 2002 00:00:00 GMT");
  header ("Cache-Control: private");
  header ("Pragma: no-cache");
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<html>
<head>
   <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
   <META HTTP-EQUIV="Cache-Control" CONTENT="private">
   <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
   <META HTTP-EQUIV="Expires" CONTENT="Sat, 1 Jan 2002 00:00:00 GMT">
   <META NAME="Author" CONTENT="khan@web.de (Jens Beyer)">
   <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=yes" />
<?php

if (file_exists('/tmp/ticker.run') && ($mysettings&16) && $tickjs==1) {
 $diff = time() - filemtime('/tmp/ticker.run');
 $dtick = time() - filemtime('/tmp/ticker.end');

echo <<<EOF
<script type="text/javascript">
<!--

var aS = $diff;
var aD = $dtick;
var aT = $mytick;
var aR = 0;

function MyTick() {
 aS = aS+1;
 aD = aD+1;
 if (aS >= $ticktime) {
   window.document.getElementById('myt').className = 'red';
   aS = 0;
 }
 if (aD >= $ticktime) {
   aD = 0;
   aT = aT+1;
   aR = aR+1;
   window.document.getElementById('mtt').firstChild.nodeValue = ""+aT+"+"+aR;
 }
 window.document.getElementById('myt').firstChild.nodeValue = ""+aS;
 window.setTimeout('MyTick()',970);
}
// -->
</script>
EOF;
}

   if ($extra) {
     echo $extra;
   } else {
     echo "   <TITLE>$game $version</TITLE>\n";
   }
require_once "mobile.inc";
 
  if ($mobile_detect) {
     echo "   <LINK rel=stylesheet type=\"text/css\" href=\"mobile.css\">";
  } else {
   if (ISSET($imgpath) && $imgpath != "") { 
    
    if ($mysettings&32)
      echo "   <LINK rel=stylesheet type=\"text/css\" href=\"npb.css\">";
    else
      echo "   <LINK rel=stylesheet type=\"text/css\" href=\"mpb.css\">";
   } else {
    if ($mysettings&32)
      echo "   <LINK rel=stylesheet type=\"text/css\" href=\"npa.css\">";
    else
      echo "   <LINK rel=stylesheet type=\"text/css\" href=\"mpa.css\">";
   }
  }
    
  
  if (file_exists('/tmp/ticker.run') && ($mysettings &16) && $tickjs==1) {
    echo "</head>\n<body class=\"a\" ".
      "onLoad=\"window.setTimeout('MyTick()',800)\">\n";
  } else {
    echo "</head>\n<body class=\"a\">\n";
  }
}
?>
