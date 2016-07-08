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

require_once "admhead.php";
require_once "admform.php";

if (ISSET($_REQUEST["submit"]) && ISSET($_REQUEST["playerid"]) && $_REQUEST["playerid"] !="") {
  
  $playerid = $_REQUEST["playerid"];
  $q = "SELECT leader,planetname FROM planet  WHERE id='$playerid'";
  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_row($result);
    $id = $playerid;
    $pleader = $row[0];
    $pname = $row[1];
  } else {
    $pleader ="deleted";
    $pname = "deleted";
  }
} else {
   $id = -1;
}

?>

<center>
<br>
<table  width="650" border="1" cellpadding="2" >
<tr>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
  <td align="center" class="a">Enter target leader:</td>
  <td><input type="text" name="playerid" size="25"></td>
  <td colspan="2"><input type=submit value="  Search  " name=submit></td>
</form>
</tr>
</table>

<br>
<table  width="650" border="1" cellpadding="2" >
<tr>
<form method="post" action="
<?php 
  if(ISSET($_REQUEST["submit"]) && $_REQUEST["submit"]==1||$id>0) 
    echo $_SERVER['PHP_SELF']."?submit=1&playerid=$id"; 
  else 
   echo $_SERVER["PHP_SELF"]; 
?>">
  <td>Filter</td>
  <td><select name="a">
      <option value=0>--</option>
      <option value=1>Class =</option>
      <option value=2>Type  =</option>
      <option value=3>Class !=</option>
      <option value=4>Type  !=</option>
      </select>
  </td>
  <td><select name="v">
      <option value=0>--</option>
      <option value=1>C:Login</option>
      <option value=2>C:Logout</option>
      <option value=3>C:Forum</option>
      <option value=4>C:Attack</option>
      <option value=5>C:Init</option>
      <option value=6>C:Signon</option>
      <option value=7>C:Flow</option>
      <option value=1>T1:</option>
      <option value=2>T2:</option>
      <option value=3>T3:</option>
      <option value=4>T4:</option>
      <option value=5>T5:</option>
      <option value=6>T6:</option>
      <option value=7>T7:</option>
      <option value=8>T8:</option>
      <option value=9>T9:</option>
      <option value=10>T10:</option>
      <option value=11>T11:</option>
      <option value=12>T12:</option>
      <option value=13>T13:</option>
      </select>
  </td>
  <td><select name="o">
      <option value=0>--</option>
      <option value=2>OR</option>
      <option value=1>AND</option>
      </select>
  </td>
  <td><select name="b">
      <option value=0>--</option>
      <option value=1>Class =</option>
      <option value=2>Type  =</option>
      <option value=3>Class !=</option>
      <option value=4>Type  !=</option>
      </select>
  </td>
  <td><select name="w">
      <option value=0>--</option>
      <option value=1>C:Login</option>
      <option value=2>C:Logout</option>
      <option value=3>C:Forum</option>
      <option value=4>C:Attack</option>
      <option value=5>C:Init</option>
      <option value=6>C:Signon</option>
      <option value=7>C:Flow</option>
      <option value=1>T1:</option>
      <option value=2>T2:</option>
      <option value=3>T3:</option>
      <option value=4>T4:</option>
      <option value=5>T5:</option>
      <option value=6>T6:</option>
      <option value=7>T7:</option>
      <option value=8>T8:</option>
      <option value=9>T9:</option>
      <option value=10>T10:</option>
      <option value=11>T11:</option>
      <option value=12>T12:</option>
      <option value=13>T13:</option>
      </select>
  </td>
  <td><input type=submit value="  Filter  " name=filter></td>
</form>
</tr>
</table>
<br>

<?php
$where = "";
$f = "";

if ((ISSET($id) && $id>0) || 
    (ISSET($id) && $id!= 0 && ISSET($_REQUEST["filter"])) || 
    (ISSET($_REQUEST["data"]) && $_REQUEST["submit"]==1)) {

  if (ISSET($_REQUEST["filter"])) {
    if (ISSET($_REQUEST["a"])) $a= $_REQUEST["a"];
    if (ISSET($_REQUEST["v"])) $v= $_REQUEST["v"];
    if (ISSET($_REQUEST["b"])) $b= $_REQUEST["b"];
    if (ISSET($_REQUEST["o"])) $o= $_REQUEST["o"];

    if ($a != 0 && $v != 0) {
      $where = "";
      if ($a==1) $where .= "class =";
      else if ($a==2) $where .= "type =";
      else if ($a==3) $where .= "class !=";
      else if ($a==4) $where .= "type !=";

      $where .= " $v";
      $f = "&filter=1&a=$a&v=$v";
    }

    if ($b != 0 && $w != 0 && $o!=0) {
      if ($o == 1) $where .= " AND ";
      else $where = "($where OR ";

      if ($b==1) $where .= "class =";
      else if ($b==2) $where .= "type =";
      else if ($b==3) $where .= "class !=";
      else if ($b==4) $where .= "type !=";

      $where .= " $w";
      if ($o == 2) $where .= ")";
      $f .= "&o=$o&b=$b&w=$w";
    }
    $where = " AND $where ";
  }
  if (ISSET($_REQUEST["data"])) 
    $data = $_REQUEST["data"];
  if (ISSET($data) && $data!="") {
    $q = "SELECT stamp,class,type,data,planet_id FROM logging ".
       "WHERE data='$data' ORDER BY stamp DESC LIMIT 400";
  } else if (ISSET($id) && $id>0) {
    $q = "SELECT stamp,class,type,data,planet_id FROM logging ".
       "WHERE planet_id='$id' $where ORDER BY stamp DESC LIMIT 400";
  } else {
    $q = "SELECT stamp,class,type,data,planet_id FROM logging ".
       "WHERE planet_id!=0 $where ORDER BY stamp DESC LIMIT 400";
  }
  echo "[$q]<br>\n";
  $result = mysqli_query ($db, $q );

  if ($result && mysqli_num_rows($result) > 0) {
    echo "<table width=650 border=1 cellpadding=2>\n";
    if (ISSET($data) && $data!="") 
      echo "<tr class=a><th align=center colspan=5>".
         "Logging: [$data]</th></tr>\n";
    else 
      echo "<tr class=a><th align=center colspan=5>".
         "Logging: $pleader of $pname</th></tr>\n";
    while ($row=mysqli_fetch_array($result)) {
      echo "<tr><td>$row[0]</td><td>";
	// class
	switch($row[1]) {
	case 1: 
		echo "login</td><td>"; 
		if ($row[2] == 1) echo "IP";
		else if ($row[2] == 2) echo "browser"; 
		break;
	case 2: 
		echo "logout</td><td>"; 
		if ($row[2] == 1) echo "nav";
		else if ($row[2] == 2) echo "sleep";
		else if ($row[2] == 3) echo "vac";
		else if ($row[2] == 4) echo "del";
		else if ($row[2] == 5) echo "ban";
		else if ($row[2] == 6) echo "unban";
		else if ($row[2] == 10) echo "30-auto";
		break;
	case 3: 
		echo "forum</td><td>"; 
		break;
	case 4: 
		echo "attack</td><td>";
		if ($row[2] == 1) echo "group"; 
		else if ($row[2] == 2) echo "attack";
		else if ($row[2] == 3) echo "hostile"; 
		else if ($row[2] == 4) echo "defend"; 
		else if ($row[2] == 5) echo "friendly"; 
		else if ($row[2] == 6) echo "recall_d";
		else if ($row[2] == 7) echo "recall_f";
		else if ($row[2] == 8) echo "recall_a";
		else if ($row[2] == 9) echo "recall_h";
		break;
	case 5: 
		echo "init</td><td>"; 
		if ($row[2] == 1) echo "hostile"; 
		else if ($row[2] == 2) echo "norm";
		break;
	case 6:
		echo "signup</td><td>";
                if ($row[2] == 1) echo "IP";
                else if ($row[2] == 2) echo "browser";
                break;
	case 7:
		echo "Auto</td><td>";
		if ($row[2] == 1) echo "prot";
		else if ($row[2] == 2) echo "del";
		else if ($row[2] == 3) echo "sleep";
		break;
	default: 
		echo "$row[1]</td><td>"; 
		break;
        }

        echo "</td>";

	if ($row[3] != "") {
	  $data = $row[3];
          if ( preg_match ("/([^\[]*)\[([0-9]*)].*/", $data, $out_id)) {
            // echo "<td><a href=\"$_SERVER['PHP_SELF']?submit=1&data=$data$f\">$out_id[1]</a> [";
            echo "<td>$out_id[1] [";
            echo "<a href=\"/admin/pinfo.php?submit=1&playerid=$out_id[2]\">$out_id[2]</a>]</td>";
          } else {
            if ($row[1]==1 && $row[2]==1) 
               echo "<td><a href=\"". $_SERVER['PHP_SELF'] ."?submit=1&data=$row[3]$f\">$row[3]</a></td>";
            else
               echo "<td>$row[3]</td>";
          }
        } else 
	  echo "<td></td>";

        echo "<td><a href=\"$base_path/pinfo.php?submit=1".
	     "&playerid=$row[4]\">$row[4]</td>".
             "</tr>\n";
    }
    echo "</table>";
 }
}

?>

</center>

<?php
require_once "../footer.php";
?>
