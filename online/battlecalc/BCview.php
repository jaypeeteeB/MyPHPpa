<html>

<?
	if ( !$style || (int)$style != $style )
		$style = 0;
?>

<head>
<link rel='stylesheet' type='text/css' href='BCstyle_<?= $style ?>.css'><title>Planetarion Battle Calculator - ship info</title>
<?
	include "ShipTypes.php";

	if ( !$ViewShip )
		$ViewShip = 0;

	$ShipType = $ShipTypes[$ViewShip];

	$Target1 = $TypeReal[$ShipType[Target1]];
	$Target2 = $TypeReal[$ShipType[Target2]];
	$Target3 = $TypeReal[$ShipType[Target3]];
?>
</head>
<body>
<center>
<table class=border cellspacing=2 border=0 cellpadding=2>
<tr><td class=wrapperborder>
<table class=border cellspacing=2 border=0 cellpadding=0>
<tr><td class=wrapper>
<table border='0' cellspacing=1 align=center width=400>
<thead><tr><td colspan='2' class=top>Ship Info</td></tr>
<tr><th colspan=2><?= $ShipType[Name] ?></th></tr>
<tr><td class=infocel>Class</td><td><?= $TypeReal[$ShipType[ShipClass]] ?></td></tr>
<tr><td class=infocel>Target Class 1 (Primary)</td><td class=valuecel><?= $Target1 ?></td></tr>
<tr><td class=infocel>Target Class 2 (Secondary)</td><td class=valuecel><?= $Target2 ?></td></tr>
<tr><td class=infocel>Target Class 3 (Tertiary)</td><td class=valuecel><?= $Target3 ?></td></tr>
<tr><td class=infocel>Initiative</td><td class=valuecel><?= $ShipType[Init] ?></td></tr>
<tr><td class=infocel>Agility</td><td class=valuecel><?= $ShipType[Agility] ?></td></tr>
<tr><td class=infocel>Weapon speed</td><td class=valuecel><?= $ShipType[Weap_speed] ?></td></tr>
<tr><td class=infocel>Guns</td><td class=valuecel><?= $ShipType[Guns] ?></td></tr>
<tr><td class=infocel>Gun power</td><td class=valuecel><?= $ShipType[Gunpower] ?></td></tr>
<tr><td class=infocel>Armour</td><td class=valuecel><?= $ShipType[Armour] ?></td></tr>
<tr><td class=infocel>EMP resistance</td><td class=valuecel><?= $ShipType[Emp_res] ?></td></tr>
<tr><td class=infocel>Metal cost</td><td class=valuecel><?= $ShipType[Metal] ?></td></tr>
<tr><td class=infocel>Crystal cost</td><td class=valuecel><?= $ShipType[Crystal] ?></td></tr>
<tr><td class=infocel>Eonium cost</td><td class=valuecel><?= $ShipType[Eonium] ?></td></tr>
<tr><td class=infocel>Fuel intake</td><td class=valuecel><?= $ShipType[Fuel] ?></td></tr>
<tr><td class=infocel>Travel time</td><td class=valuecel><?= $ShipType[Travel] ?></td></tr>
<tr><td class=infocel>Special</td><td class=valuecel><?= $ShipType[Special] ?></td></tr>
<td colspan='2' class=bottom><input type=button value='Back' onClick='history.back()'></td></tr>
</table>
</td></tr>
</table>
</td></tr>
</table>
</center>

</body></html>