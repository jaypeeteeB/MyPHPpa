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
require_once "../create_user.php";
require_once "admin_pw.inc";

function create_gal ($x, $y) {
  global $db;

  $result = mysqli_query ($db, "SELECT id FROM galaxy WHERE x='$x' AND y='$y'" );

  if ($result && (mysqli_num_rows($result) == 0)) {
    $result = mysqli_query ($db, "INSERT INTO galaxy set x='$x',y='$y'" );
  }
}

function trunc_table ($table) {
  global $db, $dbname;

  echo "TRUNCATE TABLE $dbname.$table<br>";
  if (file_exists('/tmp/ticker.run') || $table=="") {
    echo "<b>Cant do that now!</b><br>";
  } else {
    $result = mysqli_query ($db, "TRUNCATE TABLE $dbname.$table" );
    if ($result) echo "truncated $table...<br>\n"; 
  }
}

function create_admin () {
  global $db;
  global $admin_pw, $mod_pw;

  $result = mysqli_query($db, "INSERT into user ".
                        "SET login='admin',password='". $admin_pw ."'," .
                        "email='myphppa@web.de',planet_id='1'");
  if (!$result) {
    echo "Failed to insert admin<br>";
    return;
  }
  $result = mysqli_query($db, "INSERT into user ".
                        "SET login='moderator',password='". $mod_pw ."'," .
                        "email='myphppa@web.de',planet_id='2'");
  if (!$result) {
    echo "Failed to insert moderator<br>";
    return;
  }

  $result = mysqli_query($db, "INSERT into planet set planetname='here'," .
                        "leader='Admin',mode=0xF2,x=1,y=1,z=1" );
  $planet_id = mysqli_insert_id ($db);

  /* signup date, first tick */
  $result = mysqli_query($db, "UPDATE user SET planet_id='$planet_id',".
                        "signup=NOW(),first_tick=0,last=NOW() WHERE ".
                        "login='admin' AND password='". $admin_pw ."'" );

  create_user ($planet_id);

  $result = mysqli_query($db, "INSERT into planet set planetname='the game'," .
                        "leader='Moderator',mode=0xF2,x=1,y=1,z=2" );
  $planet_id = mysqli_insert_id ($db);

  /* signup date, first tick */
  $result = mysqli_query($db, "UPDATE user SET planet_id='$planet_id',".
                        "signup=NOW(),first_tick=0,last=NOW() WHERE ".
                        "login='moderator' AND password='". $mod_pw ."'" );
  create_user ($planet_id);

  $result = mysqli_query($db, "UPDATE galaxy SET members=2, name='My Galaxy' ".
                        "WHERE id=1" );

}
?>


<br>

<?php

if (ISSET($_POST["submit"])) {
  trunc_table ("fleet");
  trunc_table ("fleet_cap");
  trunc_table ("galaxy");
  trunc_table ("politics");
  trunc_table ("poltext");
  trunc_table ("msg");
  trunc_table ("mail");
  trunc_table ("news");
  trunc_table ("pds");
  trunc_table ("pds_build");
  trunc_table ("planet");
  trunc_table ("rc");
  trunc_table ("rc_build");
  trunc_table ("scan");
  trunc_table ("scan_build");
  trunc_table ("unit_build");
  trunc_table ("units");
  trunc_table ("user");
  trunc_table ("alliance");
  trunc_table ("journal");

  trunc_table ("logging");
  trunc_table ("general");

  $result = mysqli_query ($db, "INSERT INTO general set tick=0" );
  if ($result)
    echo "reset ticks to 0...<br>\n";

  echo "creating gals...";
  for ($i=1; $i<=$universe_size;$i++) {
    for ($j=1; $j<=$cluster_size;$j++) {
      echo "$i $j<br>";
      create_gal($i, $j);
    }
  }
  create_admin();
  echo "done<br<\n";

  echo "<b>All data reset - plz signup again now";
} else {
?>
<center>
<form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
<table width="650" border="1" cellpadding="10">
<tr><th>You really want to recreate theuniverse ?<br>
You will have to signup again after this</th></tr>
<tr><td align="center"><input type=submit value="DO IT" name="submit"></td></tr>
</table>
</form>
</center>
<?php
}

require_once "../footer.php";
?>
