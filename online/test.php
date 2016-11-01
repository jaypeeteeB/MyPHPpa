<?php 

require_once "strong-passwords.php";

for ($i=0; $i<10; $i++) {
    $pw = generateStrongPassword(8);
    echo "Found [$i]: ". $pw ."<br>";
}

?>

