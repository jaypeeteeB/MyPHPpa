<?php
    function CreateShipType( &$ShipType, $TypeNr, $Name, $FormName, $ShipClass, $T1, $T2, $T3, $Init, $Agility, $WP_SP, $Guns, $GunPwr, $Armour, $EMP_res, $Metal, $Crystal, $Eonium, $Fuel, $Travel, $Special )
    {
        $ShipType = Array(
        	"TypeNr" => $TypeNr,
        	"Name" => $Name,
        	"FormName" => $FormName,
        	"ShipClass" => $ShipClass,
        	"Target1" => $T1,
        	"Target2" => $T2,
        	"Target3" => $T3,
        	"Init" => $Init,
        	"Agility" => $Agility,
        	"Weap_speed" => $WP_SP,
        	"Guns" => $Guns,
        	"Gunpower" => $GunPwr,
        	"Armour" => $Armour,
        	"Emp_res" => $EMP_res,
        	"Metal" => $Metal,
        	"Crystal" => $Crystal,
        	"Eonium" => $Eonium,
        	"Worth" => round(0.1*($Metal + $Crystal)),
        	"Fuel" => $Fuel,
        	"Travel" => $Travel,
        	"Special" => $Special
       	);
    }

   	$ShipTypes = Array();
    CreateShipType( $ShipTypes[0],  0,  "Interceptor",    "interceptor", "FI", "FR", "CO", "FI",  8, 30, 30,  1,  2,   3, 50,  1250,     0,    0,   10, 2, "None");
    CreateShipType( $ShipTypes[1],  1,  "Phoenix",        "pheonix",     "CO", "CR", "DE", "FR",  6, 25, 10,  1, 12,  12, 65,  2000,   500,    0,   25, 3, "None");
    CreateShipType( $ShipTypes[2],  2,  "War Frigate",     "warfrigate", "FR", "CO", "FI", "-",  10, 20, 25,  3,  5,  30, 75,  5500,  1500,    0,   80, 4, "None");
    CreateShipType( $ShipTypes[3],  3,  "Devastor",     "devastor",      "DE", "BS", "CR", "DE",  7, 15,  5,  3, 25,  70, 80, 12000,  4000,    0,  175, 4, "None");
    CreateShipType( $ShipTypes[4],  4,  "Star Cruiser",    "starcruiser","CR", "FR", "CO", "-",  11, 10, 15,  8, 15, 155, 85, 24000,  6000,    0,  350, 5, "None");
    CreateShipType( $ShipTypes[5],  5,  "Dreadnaught",    "dreadnaught", "BS", "FI", "CO", "-",   9,  5, 30,100,  2, 400, 90, 70000, 16000,    0,  700, 5, "None");
    CreateShipType( $ShipTypes[6],  6,  "Spider",         "spider",      "FI", "CO", "FR", "-",   5, 30,  0,  1,  1,   2, 45,     0,  1250,    0,   10, 2, "EMPs");
    CreateShipType( $ShipTypes[7],  7,  "Wraith",         "wraith",      "CO", "CR", "DE", "FR", 12, 25, 15,  1, 12,  12, 65,  2000,  1000,    0,   30, 2, "Cloaked");
    CreateShipType( $ShipTypes[8],  8,  "Black Widow",    "blackwidow",  "FR", "CO", "FI", "-",   4, 20,  0,  5,  1,  30, 70,  2000,  5000,    0,   80, 4, "EMPs");
    CreateShipType( $ShipTypes[9],  9,  "Ghost",          "ghost",       "DE", "BS", "CR", "DE", 13, 15,  5,  3, 25,  70, 75,  9000,  7000,    0,  175, 4, "Cloaked");
    CreateShipType( $ShipTypes[10], 10, "Tarantula",      "tarantula",   "CR", "CR", "DE", "FR",  3,  8,  0,  6,  1, 135, 70, 14000, 12000,    0,  350, 5, "EMPs");
    CreateShipType( $ShipTypes[11], 11, "Spectre",        "spectre",     "BS", "FI", "CO", "-",  14,  5, 30,100,  2, 350, 85, 53000, 33000,    0,  700, 5, "Cloaked");
    CreateShipType( $ShipTypes[12], 12, "Astro Pod",       "astropod",   "FR", "RO",  "-", "-",  15, 18,  0,  1,  1,  12, 65,  1750,  500,  500,  125, 4, "Steals roids");

    CreateShipType( $ShipTypes[13], 20, "Planetary Missile", "missile",    "MI", "*", "-",  "-",   1, 50, 65, 12, 1,   1,   5,   200,   200,  250,    8, 1, "Anti PDS");

    CreateShipType( $ShipTypes[14], 13, "Meson Cannon",   "mesoncannon",   "FI", "CO", "FI",  "-",  2,  1, 30,  1,  2,  8, 100,   350,   350,  350,    0, 0, "PDS");
    CreateShipType( $ShipTypes[15], 14, "Hyperon Turret", "hyperonturret", "FI", "FR", "CO",  "-",  2,  1, 25,  1, 10,  16, 100,  1000,  1000, 1000,    0, 0, "PDS");
    CreateShipType( $ShipTypes[16], 15, "Neutron Emitter","neutronemitter","CO", "DE", "FR",  "-",  2,  1, 20,  1, 20,  32, 100,  2000,  2000, 2000,    0, 0, "PDS");
    CreateShipType( $ShipTypes[17], 16, "Photon Cannon",  "photoncannon",  "CO", "CR", "DE",  "-",  2,  1, 15,  1, 50,  64, 100,  3500,  3500, 3500,    0, 0, "PDS");
    CreateShipType( $ShipTypes[18], 17, "ION Turret",     "ionturret",     "FR", "BS", "CR",  "-",  2,  1,  5,  1, 75,  96, 100,  5000,  5000, 5000,    0, 0, "PDS");
    CreateShipType( $ShipTypes[19], 18, "Missile Launcher","misslauncher", "DE",  "-",  "-",  "-",  2,  1,  0,  1,  0,  48, 100,  1500,  1500,  5000,   0, 0, "PDS");
    CreateShipType( $ShipTypes[20], 19, "Missile Cannon", "misscannon",    "CO", "MI", "-",   "-",  0,  1, 30, 10,  1,   8, 100,   750,   750,  750,    0, 0, "PDS");

    CreateShipType( $ShipTypes[21], 18, "Metal roid",     "metalroid",   "RO",  "-",  "-",  "-",  0,  0,  0,  0,  0,   0,  0, 0,     0,    0,    0, 0, "Roid");
    CreateShipType( $ShipTypes[22], 19, "Crystal roid",   "crystalroid", "RO",  "-",  "-",  "-",  0,  0,  0,  0,  0,   0,  0, 0,     0,    0,    0, 0, "Roid");
    CreateShipType( $ShipTypes[23], 20, "Eonium roid",    "eoniumroid",  "RO",  "-",  "-",  "-",  0,  0,  0,  0,  0,   0,  0, 0,     0,    0,    0, 0, "Roid");
    CreateShipType( $ShipTypes[24], 21, "Uninitiated roid","uninitroid", "RO",  "-",  "-",  "-",  0,  0,  0,  0,  0,   0,  0, 0,     0,    0,    0, 0, "Roid");

	$TypeReal["FI"] = "Fighter";
	$TypeReal["CO"] = "Corvette";
	$TypeReal["FR"] = "Frigate";
	$TypeReal["DE"] = "Destroyer";
	$TypeReal["CR"] = "Cruiser";
	$TypeReal["BS"] = "Battleship";
	$TypeReal["MI"] = "Missile";
	$TypeReal["RO"] = "Asteroid";
	$TypeReal["-"]  = "None";
	$TypeReal["*"]  = "Any class";
	$TypeReal["**"] = "Resources";
?>
