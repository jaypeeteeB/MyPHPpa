<?
// import_request_variables ("GPC", "");

if ( ISSET($_COOKIE["style"]) && $_COOKIE["style"] != null )
  $style = $_COOKIE["style"];
else
  $style = 0;

if ( ISSET($_COOKIE["style_input"]) && $_COOKIE["style_input"] != null )
{
  setcookie("style", "$style_input",  time()+360000000, "/" );
  $style_input = $_COOKIE["style_input"];
  $style = $style_input;
}

$version = "2.1b-2";

if ( ISSET($_REQUEST["showversion"]) )
     die( $version );

if ( !ISSET($_REQUEST["style"]) || (int)$_REQUEST["style"] != $style )
  $style = 0;

switch( $style )
{
 default :
 case 0 : $style_prim = "red";  $style_sec = "orange"; $style_tert = "green"; break;
 case 1 : $style_prim = "lightgreen";  $style_sec = "lightblue"; $style_tert = "#AAAAAA"; break;
 case 2 : $style_prim = "green";  $style_sec = "blue"; $style_tert = "#666666"; break;
 case 3 : $style_prim = "lightblue";  $style_sec = "lightgreen"; $style_tert = "pink"; break;
}

if (ISSET($_REQUEST["shipdata"])) $shipdata = $_REQUEST["shipdata"];
if (ISSET($_REQUEST["ViewShip"])) $ViewShip = $_REQUEST["ViewShip"];
if (ISSET($_REQUEST["NumCalcs"])) $NumCalcs = $_REQUEST["NumCalcs"];
if (ISSET($_REQUEST["Checker"])) $Checker = $_REQUEST["Checker"];
if (ISSET($_REQUEST["Addtype"])) $Addtype = $_REQUEST["Addtype"];
if (ISSET($_REQUEST["CapRule"])) $CapRule = $_REQUEST["CapRule"];
if (ISSET($_REQUEST["ShowLog"])) $ShowLog = $_REQUEST["ShowLog"];
if (ISSET($_REQUEST["ShowTotals"])) $ShowTotals = $_REQUEST["ShowTotals"];
if (ISSET($_REQUEST["dplanetscore"])) $dplanetscore = $_REQUEST["dplanetscore"];
if (ISSET($_REQUEST["input"])) $input = $_REQUEST["input"];
if (ISSET($_REQUEST["ToGetAll"])) $ToGetAll = $_REQUEST["ToGetAll"];

include "vars.php";

?>
     <html>
     <head>
     <link rel="stylesheet" type="text/css" href=BCstyle_<? echo $style ?>.css>
     <title>MyPHPpa Battle Calculator</title>
     </head>

     <?
     //	set_time_limit( 60 );
     include "BCcode.php";

if ( !function_exists("FillFleet") )
     die ("<center>Code library temporary unavailable.. if this persists for more then 15 minutes, please mail <a href='mailto:myphppa@web.de'><u>khan</u></a></center>");

     if ( !ISSET($NumCalcs) )
     $NumCalcs = 1;

     if ( ISSET($Checker) && $Checker )
{
  FillFleet( "Load", $_POST );
  if ( $Addtype != "att" && $Addtype != "def" && $Addtype != "skip" )
    MainLoop( $NumCalcs);
}
else
FillFleet( "New", $_POST );

?>

<script>
<?
echo "   ShipTargets = new Array;\n";
WriteJSInfo( $Fleet[0]["Ships"], $Fleet[1]["Ships"], 0 );
WriteJSInfo( $Fleet[1]["Ships"], $Fleet[0]["Ships"], 1 );
?>

ns4 = (document.layers)? true:false;
ie4 = (document.all)? true:false;

function setStyle(id,nestref, stylename, value)
{
  if (ns4)
    {
      //			var lyr = (nestref)? document[nestref].document[id] : document.layers[id];
      document.tags[id].color = value;

    }
  else if (ie4)
    {
      if ( document.all[id] )
	document.all[id].style[stylename] = value;
    }
}

function OnNameOver( Side, FltNr, Over )
{
  if ( Side == 0 ) Side = 1; else Side = 0;
  SideName = Side ? "a" : "d";
  for( i = 0; i < 3 ; i++ )
    {
      for( t = 0; t < ShipTargets[Side][FltNr][i].length; t++ )
	{
	  if ( ShipTargets[Side][FltNr][i][t] != null )
	    {
	      var LinkName = new String;
	      LinkName = SideName + ShipTargets[Side][FltNr][i][t];
	      if ( Over )
		{
		  if ( i == 0 )
		    setStyle( LinkName,"", "color", "<? echo $style_prim ?>" );
		  else if ( i == 1 )
		    setStyle( LinkName,"", "color", "<? echo $style_sec ?>" );
		  else if ( i == 2 )
		    setStyle( LinkName,"", "color", "<? echo  $style_tert ?>" );

		}
	      else
		{
		  setStyle( LinkName,"", "color", "" );
		}
	    }
	}
    }
}

function ResetBtnClick( form, notscores )
{
  for ( t = 0; t < form.elements.length; t++ )
    if ( form.elements[t].type == "text" && form.elements[t].name != "NumCalcs" )
      if ( !((form.elements[t].name == "aplanetscore" || form.elements[t].name == "dplanetscore") && notscores) )
	form.elements[t].value=0;

  form.NumCalcs.value = 1;
  //    	form.planetscoreratio.value = 100;
}
</script>

<body>

<form name=TheForm action="BC.php" method="post">

<center>
<table class=border cellspacing=2 border=0 cellpadding=2 width=700>
<tr><td class=wrapperborder>
<table class=border cellspacing=2 border=0 cellpadding=0 width=100%>
<tr><td class=wrapper>
<table cellspacing=1 cellpadding=0 border=0 class=header width=100%>
<? if ( filesize("news.php") > 0 ) : ?>
<tr>
<td class=headtext width=100% colspan=3>
<? include "news.php"; ?>
</td>
</tr>
<? endif ?>
<tr>
<td class=headtext width=100%>
<span class=subscript>Version <? echo $version ?>, last update: <? echo date( "jS M Y",max(filemtime("BCcode.php"),max(filemtime("BC.php"),filemtime("ShipTypes.php")))) ?>, Original Made by Joror, (c) WolfPack 2001, <?= round(filesize("logs.php") / 4) ?> hits since installment</span>
</td>
<td class=headtext align=center nowrap>
[<a href='mailto:daan@parse.nl?subject=[Battlecalc]'>Mail creator</a>]
</td>
<td class=headtext align=center nowrap>
[<a href='http://battlecalc.shoq.com'>Orig.: BC Mirrors</a>]
</td>
</tr>
</table>
</td></tr>
</table>
</td></tr>
</table>
</center>
<br>
<center>
<table class='border' cellspacing=2 border=0 cellpadding=2 width=700>
<tr><td class=wrapperborder>
<table class='border' cellspacing=2 border=0 cellpadding=0 width=100%>
<tr><td class=wrapper>
<table cellspacing=1 border=0 cellpadding=0 class=maintable width=100%>
<thead>
<tr>
<td colspan='8' class=top valign=center>
<input type=hidden name=Checker value="true">
<input type=hidden name=shipdata value="<? echo (ISSET($shipdata)?$shipdata:"") ?>">
MyPHPpa Battle Calculator<br>
<span class=disclaimer>
(disclaimer: calculations are based on average, so calculations on <u>small</u> numbers may have a significant difference with reality)
     </span>
</td>
</tr>
<tr>
<td colspan='8' class=namecel valign=center>
Mouseover Legend : <span style="color:<? echo$style_prim?>">Primary target</span>, <span style="color:<?=$style_sec?>">Secondary target</span>, <span style="color:<?=$style_tert?>">Tertiary target</span></span>
</td>
</tr>
<tr>
<td colspan='4' class=friendly>Defending Forces</td>
<td colspan='4' class=hostile>Attacking Forces</td>
</tr>
<tr>
<th class=namecel>Name</th><th class=amountheader>Amount</th>
<th class=lostheader>Lost</th><th class=stunnedheader>Stunned</th>
<th class=namecel>Name</th><th class=amountheader>Amount</th>
<th class=lostheader>Lost</th><th class=stunnedheader>Stunned</th>
</tr>
</thead>
<?
WriteFleets( $Fleet[0], $Fleet[1] );
?>
<tr><td colspan='8'>
<table cellpadding=0 border=0 cellspacing=0 width=100%>
<tr>
<td style="padding:2;width:50%;">
Ticks to calculate &nbsp;<input type=text size=3 name='NumCalcs' value=<? echo $NumCalcs ?>>
</td>
<td style="padding:2;width:25%;">
<input class=checkbox type=checkbox value=1 name="ShowLog" CHECKED>Show calculation logs
</td><td style="padding:2;width:25%;">
<input class=checkbox type=checkbox value=1 name="ShowTotals" CHECKED>Show totals<br>
</td>
</tr>
</table>
</td></tr>
<tr>
<td colspan='8' style="padding:2">
Paste a unit scan/overview count/battle report : <textarea name=input cols=20 rows=1 wrap=soft></textarea>
<input type=hidden name=Addtype value='none'>
<input type=button tabindex=1000 value='add to def' onClick='this.form.Addtype.value="def";this.form.submit();'>
<input type=button tabindex=1000 value='add to att' onClick='this.form.Addtype.value="att";this.form.submit();'>
<input type=button tabindex=1000 value='battlereport' onClick='ResetBtnClick( this.form, true );this.form.Addtype.value="BattleReport";this.form.submit();'>
<br>For military screen pastes :
<input type=checkbox class=checkbox name=fleetbase value=1>Base Fleet
<input type=checkbox class=checkbox name=fleet1 value=1>Fleet 1
<input type=checkbox class=checkbox name=fleet2 value=1>Fleet 2
<input type=checkbox class=checkbox name=fleet3 value=1>Fleet 3
</td>
</tr>

<tr>
<td colspan='8' style="padding:2">
Choose a battlecalc-style :
<input class=checkbox type=radio name=style_input value=0 <? echo ( $style == 0 )? 'CHECKED' : '' ?>>WolfPack
<input class=checkbox type=radio name=style_input value=1 <? echo ( $style == 1 )? 'CHECKED' : '' ?>>Pilkara.com style (red)
     <input class=checkbox type=radio name=style_input value=2 <? echo ( $style == 2 )? 'CHECKED' : '' ?>>Old Elysium style (blue)
     <input class=checkbox type=radio name=style_input value=3 <? echo ( $style == 3 )? 'CHECKED' : '' ?>>Old Concordium style (old PA colors)
     </td>
</tr>
<tr>
<td colspan='8' class=bottom2>
<table cellpadding=0 cellspacing=0 border=0 width=100%>
<tr>
<td width=25% class=bottom2 align=left>
<input type=button style="width:64;" tabindex=1000 value='Reset' onClick='ResetBtnClick( this.form, false );'>
</td><td width=50% class=bottom2 align=center>
<input type=submit style="width:auto;" value='Calculate Battle' tabindex=1000>
</td><td width=25% class=bottom2 align=right>
<input type=button style="width:auto;" tabindex=1000 value='Calc totals' onClick='this.form.Addtype.value="skip"; this.form.ShowTotals.value="1";this.form.submit();'>
</td></tr>
</table>
</td></tr>
</table>
</td></tr>
</table>
</td></tr>
</form>
</table>
<br>
<? 	if ( ISSET($ShowLog) ) : ?>
<table class=border cellspacing=2 border=0 cellpadding=2 width=700>
<tr><td class=wrapperborder>
<table class=border cellspacing=2 border=0 cellpadding=0 width=100%>
<tr><td class=wrapper>
<table cellspacing=1 border=0 class=logtable align=center width=100%>
<thead>
<tr><td class=toplog>Calculator logs for this combat:</td></tr>
</thead>
<?
if ( $CalcLog == "" )
     $CalcLog = "<center>No data.</center>";

     echo "<tr><td colspan=12 style='text-align:left;padding:5'>$CalcLog</td></tr>";
     ?>
     </table>
     </td></tr>
     </table>
     </td></tr>
     </table>
     <?
     endif;
?>
     </center>
     <?

     /* log file */

     $fp = @fopen("logs.php", "a");
if ( $fp )
{
  $ipnrs = explode( ".", $REMOTE_ADDR);
  $line = pack( "L", time());
  @fwrite( $fp, $line );
  @fclose( $fp );
}
?>
</body></html>
