<?php
	include "ShipTypes.php";

   	/* fleet data */
   	$Fleet = Array();

   	/* battlereport input data */
   	$ShipBattleRep = null;

   	/* Accuracy data */
   	$overall_accuracy = null;

   	/* Warning msg string */
   	$Warning = "";

   	/* calc log buffer */
   	$InitStrBuffer = "";

   	/* for calc log view depth */
   	$DepthLog = 2;

	/* Chance to grab roids, and amount of roids grabbed */
	$RoidChance = 0;
	$RoidChanceHistory = array();
	$RoidTotal = 0;
	$RoidsGrabbed = 0;

  	/* Paste text */
   	$PasteText = "";

	function CreateShipGroup( &$ShipGroup, $ShipType, $BeginAmount)
    {
        $ShipGroup["Type"] = $ShipType;
        $ShipGroup["BeginAmount"] = $BeginAmount;
        $ShipGroup["Amount"] = $BeginAmount;
        $ShipGroup["Killed"] = 0;
        $ShipGroup["Hits"] = 0;
        $ShipGroup["ToBeKilled"] = 0;
        $ShipGroup["ToBeStunned"] = 0;
        $ShipGroup["Stunned"] = 0;
        $ShipGroup["TargetNr"] = 0;
    }

	function CalcLog( $InputString, $deepness = 1 )
	{
		global $CalcLog, $DepthLog;

		if ( $deepness == $DepthLog )
			$CalcLog .= $InputString;
	}

	function AddTotals ( $Type, &$Total, $Amount )
	{
		$Total["Amount"]  = (ISSET($Total) && array_key_exists("Amount", $Total)?$Total["Amount"]:0) + $Amount;
		$Total["Fuel"]    = (ISSET($Total) && array_key_exists("Fuel", $Total )?$Total["Fuel"] :0) + $Amount * $Type["Fuel"];
		$Total["Crystal"] = (ISSET($Total) && array_key_exists("Crystal", $Total )?$Total["Crystal"] :0) + $Amount * $Type["Crystal"];
		$Total["Metal"]   = (ISSET($Total) && array_key_exists("Metal", $Total )?$Total["Metal"] :0) + $Amount * $Type["Metal"];
		$Total["Eonium"]  = (ISSET($Total) && array_key_exists("Eonium", $Total )?$Total["Eonium"] :0) + $Amount * $Type["Eonium"];
		$Total["Worth"]   = (ISSET($Total) && array_key_exists("Worth", $Total )?$Total["Worth"] :0) + $Amount * ( $Type["Crystal"] + $Type["Metal"] + $Type["Eonium"] ) / 10;
		if ( $Type["ShipClass"] == "RO" && $Type["Name"] != "Uninitiated roid" )
			$Total["Worth"] = (ISSET($Total) && array_key_exists("Worth", $Total )?$Total["Worth"] :0) + $Amount * 1500;
	}

	function CalcTotals ( $Flt, $t, &$Totals )
	{
		AddTotals( $Flt["Ships"][$t]["Type"], $Totals["TotalShips"], $Flt["Ships"][$t]["BeginAmount"] );
		AddTotals( $Flt["Ships"][$t]["Type"], $Totals["TotalLost"], $Flt["Ships"][$t]["BeginAmount"]- $Flt["Ships"][$t]["Amount"] ); /* Removed " + $Flt[$t]["Gained"]" */
		AddTotals( $Flt["Ships"][$t]["Type"], $Totals["TotalStunned"], $Flt["Ships"][$t]["Stunned"] );

	}

    function MainLoop ( $NumCalcs )
    {
        global $ShipTypes, $Fleet, $CalcLogBuffer;

        for( $t = 0 ; $t < $NumCalcs ; $t++ )
		{
      		$CalcLogBuffer[0] = "<br><center><span class=title>TICK ". ($t+1) ."</span></center>";

		    ClearHitsStuns( $Fleet[0]["Ships"] );
		    ClearHitsStuns( $Fleet[1]["Ships"] );

		    for( $InitCount = 0; $InitCount < 16; $InitCount++ )
	        {
	            ActInitiative( $Fleet[0], $Fleet[1], $InitCount, 0 );
	            ActInitiative( $Fleet[1], $Fleet[0], $InitCount, 1 );
	            CleanUp( $Fleet[0] );
		    CleanUp( $Fleet[1] );
	        }

			$Fleet[0]["Totals"] = null;
			$Fleet[1]["Totals"] = null;

			for ( $x = 0; $x < count($Fleet[0]["Ships"]); $x++ )
       			CalcTotals( $Fleet[0], $x, $Fleet[0]["Totals"] );
			for ( $x = 0; $x < count($Fleet[1]["Ships"]); $x++ )
       			CalcTotals( $Fleet[1], $x, $Fleet[1]["Totals"] );

			if ( $Fleet[0]["PlanetScore"] != 0 )
				$Fleet[0]["PlanetScore"] -= round($Fleet[0]["Totals"]["TotalLost"]["Worth"]);

			if ( $Fleet[0]["PlanetScore"] < 0 )
				$Fleet[0]["PlanetScore"] = 1;

			if ( $Fleet[1]["PlanetScore"] != 0 )
				$Fleet[1]["PlanetScore"] -= round($Fleet[1]["Totals"]["TotalLost"]["Worth"]);

			if ( $Fleet[1]["PlanetScore"] < 0 )
				$Fleet[1]["PlanetScore"] = 1;

			if ( $CalcLogBuffer[0] )
				CalcLog($CalcLogBuffer[0]."<center>No combat this tick, no valid target available for any ship</center>",2);
	    }

    }

    function ActInitiative ( &$AttFlt, &$DefFlt, $InitCount, $who )
    {
    	global $CalcLogBuffer;

    	$Att = &$AttFlt["Ships"];
    	$Def = &$DefFlt["Ships"];

        for( $t = 0; $t < count($Att); $t++ )
        {
            if ( $Att[$t]["Type"]["Init"] == $InitCount && $Att[$t]["Amount"] - $Att[$t]["Stunned"] > 0 )
            {
           		$TotalGuns = $Att[$t]["Type"]["Guns"] * ( $Att[$t]["Amount"] - $Att[$t]["Stunned"] );

           		$CalcLogBuffer[1] = "<br><b>Acting out initiative $InitCount :</b><br>";

           		CalcLog ( "<br>Acting out initiative $InitCount : (".(array_key_exists("Side",$Att)?$Att["Side"]:0).") ". $Att[$t]["Type"]["Name"] ." <br>");
           		CalcLog ( "Total Guns : ".(ISSET($Guns)?$Guns:0)."<br>");

	           	$CalcLogBuffer[3] = "";
	           	$Done = false;

           		/* primary targets */
           		$GunsLeft = $TotalGuns;
           		while ( !$Done )
           		{
	           		if ( $CalcLogBuffer[3] == "Restshots!" )
		           		$CalcLogBuffer[2] = "<b>Primary targets (rest shots):</b><br>";
		           	else
		           		$CalcLogBuffer[2] = "<b>Primary targets:</b><br>";

	           		list( $GunsLeft, $Done) = AttackTargets( $AttFlt, $Att[$t] , $DefFlt, $Def, $Att[$t]["Type"]["Target1"], $GunsLeft );
	           		CalcLog ( "<br>Guns left after target1 : $GunsLeft<br>");
		           	$CalcLogBuffer[3] = "Restshots!";
	           	}

	           	$CalcLogBuffer[3] = "";
	           	$Done = false;

           		/* secondary targets */
           		while ( !$Done && $GunsLeft > 0 )
           		{
	           		if ( $CalcLogBuffer[3] == "Restshots!" )
		           		$CalcLogBuffer[2] = "<b>Secondary targets (rest shots):</b><br>";
		           	else
	           			$CalcLogBuffer[2] = "<b>Secondary targets:</b><br>";
	           		list( $GunsLeft, $Done) = AttackTargets( $AttFlt, $Att[$t] , $DefFlt, $Def, $Att[$t]["Type"]["Target2"], $GunsLeft );
	           		CalcLog ( "<br>Guns left after target2 : $GunsLeft<br>");
		           	$CalcLogBuffer[3] = "Restshots!";
	           	}

	           	$CalcLogBuffer[3] = "";
	           	$Done = false;

           		/* what's left after that */
           		while ( !$Done && $GunsLeft > 0 )
           		{
	           		if ( $CalcLogBuffer[3] == "Restshots!" )
		           		$CalcLogBuffer[2] = "<b>Tertiary targets (rest shots):</b><br>";
		           	else
	           			$CalcLogBuffer[2] = "<b>Tertiary targets:</b><br>";

	           		list( $GunsLeft, $Done) = AttackTargets( $AttFlt, $Att[$t] , $DefFlt, $Def, $Att[$t]["Type"]["Target3"], $GunsLeft );
	           		CalcLog ( "<br>Guns left after target3 : $GunsLeft<br>");
		           	$CalcLogBuffer[3] = "Restshots!";
	           	}
			if ($Att[$t]["Type"]["Special"] == "Anti PDS" && $who==0) {
				$Att[$t]["ToBeKilled"] += $Att[$t]["Amount"];
				CalcLog ( "Failed Missile exploded<br>", 2 );

			}

	            if ( $InitCount == 0 )
	            {
		            CleanUp( $AttFlt );
		            CleanUp( $DefFlt );
		        }
            }
        }
    }

    function IsTarget ( $AttType, $DefType, $Target )
    {
    	global $EmpTargets;

        if ( $DefType["ShipClass"] == $Target || $Target == "*" )
        {

        	if ( $AttType["Special"] != "Steals roids" && $DefType["ShipClass"] == "RO" )
        		return false;

	    	switch ( $AttType["Special"] )
	    	{
	    		case "Steals resources" : return false;
	    		case "EMPs"				: if ( $DefType["Init"] <= 2 ) return false; 
								  else return true;
	    		case "Anti PDS"				: if ( $DefType["Special"] != "PDS" ) return false;
								  else return true;
	    		default 				: return true;
	    	}
	    }

	    return false;
	}

    function AttackTargets( &$AttFlt, &$Att, &$DefFlt, &$Def, $Target, $inGuns )
    {
		global $CalcType, $Warning, $CalcLogBuffer, $RoidChance, $RoidChanceHistory, $CapRule;

		/* Return Done if shooter is an EMPer and targeting "*" */

        if ( $inGuns == 0 || $Target == "-" )
	       	return array( $inGuns, true );

		$TargetCount = 0;
		$MaxGrabCount = 0;

    	/* roid calculation rules */
    	if ( $DefFlt["PlanetScore"] > 0 )
    	{
			$RoidChance = ($DefFlt["PlanetScore"]/$AttFlt["Totals"]["TotalShips"]["Worth"]) / 10;
           	if ( $RoidChance > 0.15 )
           		$RoidChance = 0.15;
           	$RoidChanceHistory[] = $RoidChance;
    	}
       	elseif( $AttFlt["PlanetScore"] <= 0 )
    	{
			$RoidChance = 0;
			$Warning = "Attention: You need to provide the planetscore for roid loss calculations.";
    	}

		/* Count all available targets */
        for( $t = 0; $t < count($Def); $t++ )
        {
            $Def[$t]["TargetNr"] = 0;
            if ( IsTarget( $Att["Type"], $Def[$t]["Type"], $Target ) )
           	{
           		/* target found */
                if ( $Att["Type"]["Special"] == "EMPs" )
                {
                	$TargetCount += $Def[$t]["Amount"] - $Def[$t]["Stunned"] - $Def[$t]["ToBeStunned"];
               		$Def[$t]["TargetNr"] = $Def[$t]["Amount"] - $Def[$t]["Stunned"] - $Def[$t]["ToBeStunned"];
               	}
                elseif ( $Att["Type"]["Special"] == "Steals roids" )
                {
                	$TargetCount += $Def[$t]["Amount"] - $Def[$t]["ToBeKilled"];
               		$Def[$t]["TargetNr"] = $Def[$t]["Amount"] - $Def[$t]["ToBeKilled"];
					$Def[$t]["MaxGrab"] = floor($RoidChance * ($Def[$t]["Amount"] - $Def[$t]["ToBeKilled"]));
               		$MaxGrabCount += $Def[$t]["MaxGrab"];
                }
               	else
               	{
                	$TargetCount += $Def[$t]["Amount"] - $Def[$t]["ToBeKilled"];
               		$Def[$t]["TargetNr"] = $Def[$t]["Amount"] - $Def[$t]["ToBeKilled"];
               	}
       	    }
        }



/*		if ( $Att["Type"]["Special"] == "Steals roids" && floor($TotalRoids * $RoidChance) > $TargetCount)
		{
			$restroids = floor($TotalRoids * $RoidChance) - $TargetCount;
	        for( $t = 0; $t < count($Def) && $restroids > 0; $t++ )
    	    {
 	           	if ( IsTarget( $Att["Type"], $Def[$t]["Type"], $Target ) )
           		{
					$Def[$t]["TargetNr"]++;
					$restroids--;
					$TargetCount++;
				}
			}
        } */


		/* Return if no targets found */
        if ( $TargetCount == 0 )
	       	return array( $inGuns, true );

		if ( $Att["Type"]["Special"] == "Steals roids" && $MaxGrabCount == 0 )
	       	return array( $inGuns, true );

		/* Resolve Normal Shots */
	    for( $t = 0; $t < count($Def); $t++ )
	    {
	   		if ( $Def[$t]["TargetNr"] > 0 && $Def[$t]["Amount"] != 0 )
	   		{
				/* Check for dead apods (rounding catchup) */
	  			if ( $Att["Type"]["Special"] == "Steals roids" )
					if ( $Att["Amount"] - $Att["ToBeKilled"] - $Att["Stunned"] < $Def[$t]["TargetNr"] )
					{
	//						$TotalCount -= $Def[$t]["TargetNr"] - $Att["Amount"] - $Att["ToBeKilled"] - $Att["Stunned"];
						$Def[$t]["TargetNr"] = $Att["Amount"] - $Att["ToBeKilled"] - $Att["Stunned"];
						if ( $Def[$t]["TargetNr"] <= 0 )
							continue;
					}


	   			/* calc shot going this way */
	   			if ( $Att["Type"]["Special"] == "Steals roids" )
	   				$FiringOnThese = round( $inGuns * ( $Def[$t]["MaxGrab"] / $MaxGrabCount ));
				else
	   				$FiringOnThese = round( $inGuns * ( $Def[$t]["TargetNr"] / $TargetCount ));


	  			if ( $FiringOnThese > $inGuns )
	   				$FiringOnThese = 0; /* bug catcher */

				CalcLog ( "Shooting : ". $Att["Type"]["Name"] ." on ". $Def[$t]["Type"]["Name"] ." with $FiringOnThese out of $inGuns<br>");

				if ( $FiringOnThese > 0 )
				{
					CalcLog ( $CalcLogBuffer[0] .  $CalcLogBuffer[1] .  $CalcLogBuffer[2] ."$AttFlt[Side] shooting : ". $Att["Type"]["Name"] ." on ". $Def[$t]["Type"]["Name"] ." with $FiringOnThese out of $inGuns guns, ", 2);
					$CalcLogBuffer[0] = null;
					$CalcLogBuffer[1] = null;
					$CalcLogBuffer[2] = null;
				}
	    		$ShotsFiredNow = ResolveAvgShots( $FiringOnThese, $Att, $Def[$t], $Def, $RoidChance);
	    		if (ISSET($ShotsUsed)) 
			  $ShotsUsed += $ShotsFiredNow;
			else
			  $ShotsUsed = $ShotsFiredNow;

	    		if ( $ShotsFiredNow < $FiringOnThese )
	    			CalcLog( ", ".( $FiringOnThese - $ShotsFiredNow )." guns unused.<br>", 2 );
	    		elseif ( $FiringOnThese > 0 )
	   				CalcLog( ".<br>", 2 );

	    		CalcLog ( "Shots used: $ShotsUsed<br>");
	    	}
		}


        /* check for faulty roundings  */
        if ( $inGuns-$ShotsUsed < 0 )
        	return array( 0, true);


        /* check for no-shooters (usually error) */
        if ( $ShotsUsed == 0 )
        	return array( $inGuns, true );

       	/* if there were left-over shots */
       	if ( $Att["Type"]["Special"] == "Steals roids" )
	    	return array($inGuns-$ShotsUsed, true);

       	/* if there were left-over shots */
	    return array($inGuns-$ShotsUsed, false);
    }

	function ResolveAvgShots( $FiringOnThese, &$AttShips, &$DefShips, &$Def, $RoidChance )
	{
		global $CalcLogBuffer;

		/* returns shots fired, calcs & adds casualties */

		if ( $FiringOnThese == 0 )
			return 0;

    	if ( $AttShips["Type"]["Special"] == "EMPs" )
		{
			/* ------ EMP Shot resolving ------ */
			$ToStunOne = 100 / ( 100 - $DefShips["Type"]["Emp_res"] );
			$ToStunAll = ceil ( $DefShips["TargetNr"] * ( $ToStunOne ) );

			CalcLog ( "ToStunOne: $ToStunOne<br>\n");
			CalcLog ( "ToStunAll: $ToStunAll<br>\n");

			if ( $ToStunAll < $FiringOnThese )
			{
				$ExcessShots = $FiringOnThese - $ToStunAll;
				$Diff = abs($DefShips["Amount"] - $DefShips["Stunned"] - $DefShips["ToBeStunned"]);
				$DefShips["ToBeStunned"] += $Diff;
				CalcLog ( "ExcessShots : $ExcessShots, ToStunAll : $ToStunAll<br>");
				CalcLog ( "ToBeStunned: ". $DefShips["ToBeStunned"] ."<br>\n");
				CalcLog ( "Guns fired : $FiringOnThese<br>");

				CalcLog ( "stunning <b>". $Diff . "</b> ". $DefShips["Type"]["Name"] ."(s)", 2);

				return $ToStunAll;
			}
			else
			{
				$Diff = floor( $FiringOnThese / $ToStunOne );
				$DefShips["ToBeStunned"] += $Diff;

				CalcLog ( "ToBeStunned: ". $DefShips["ToBeStunned"] ."<br>\n");
				CalcLog ( "Guns fired : $FiringOnThese<br>");

				CalcLog ( "stunning <b>". $Diff . "</b> ". $DefShips["Type"]["Name"] ."(s)", 2);

				return $FiringOnThese;
			}
		}
		elseif ( $AttShips["Type"]["Special"] == "Steals roids" )
		{
			if ( $DefShips["MaxGrab"] < $FiringOnThese )
			{
				$ExcessShots = $FiringOnThese - $DefShips["MaxGrab"];

				/* Kill the apods who capped */
				$AttShips["ToBeKilled"] += $DefShips["MaxGrab"];

				if ( $AttShips["Amount"] - $AttShips["ToBeKilled"] - $AttShips["Stunned"] > 0 )
					$DefShips["ToBeKilled"] = $DefShips["MaxGrab"];

				if (ISSET($_REQUEST["ToGetAll"])) $ToGetAll = $_REQUEST["ToGetAll"];
				else $ToGetAll = 0;

				CalcLog ( "ExcessShots : $ExcessShots, ToGetAll : $ToGetAll<br>");
				CalcLog ( "ToBeGotten: ". $DefShips["ToBeKilled"] ."<br>\n");
				CalcLog ( "(grab)Guns fired : $FiringOnThese<br>");

				CalcLog ( "grabbing <b>". $DefShips["ToBeKilled"] . "</b> ". $DefShips["Type"]["Name"] ."(s)", 2);

				return $DefShips["MaxGrab"];
			}
			else
			{
				$DefShips["ToBeKilled"] += floor( $FiringOnThese );

				/* Kill the apods who capped */
				$AttShips["ToBeKilled"] += floor( $FiringOnThese );


				CalcLog ( "ToBeGotten: ". $DefShips["ToBeKilled"] ."<br>\n");
				CalcLog ( "(grab)Guns fired : $FiringOnThese<br>");

				CalcLog ( "grabbing <b>". $DefShips["ToBeKilled"] . "</b> ". $DefShips["Type"]["Name"] ."(s)", 2);

				return $FiringOnThese;
			}
		}
		else
		{
			/* ------ Normal Shot resolving ------ */

			CalcLog( "<b>Hits: $DefShips[Hits]</b><br>");

			$HitChance = ( 25 + $AttShips["Type"]["Weap_speed"] - $DefShips["Type"]["Agility"] )/100;

			if ( $HitChance > 0 )
			{
				$ToKillTheFirst = ceil( ($DefShips["Type"]["Armour"] - $DefShips["Hits"]) / $AttShips["Type"]["Gunpower"]  / $HitChance );
				$HitsToKillOne = ceil( $DefShips["Type"]["Armour"] / $AttShips["Type"]["Gunpower"] );
				$ToKillOne =  $HitsToKillOne / $HitChance;

				$ToKillAll = ceil($ToKillTheFirst + ( $DefShips["TargetNr"] - 1 ) * $ToKillOne);
			}
			else
			{
				CalcLog ( "killing <b>0</b> ". $DefShips["Type"]["Name"] ."(s)", 2);
				return $FiringOnThese;
			}

			CalcLog ( "ToKillOne: ". round($ToKillOne) ."<br>\n");
			CalcLog ( "HitsToKillOne: $HitsToKillOne<br>\n");
			CalcLog ( "ToKillAll: $ToKillAll<br>\n");
			CalcLog ( "ToKillTheFirst: $ToKillTheFirst<br>\n");

			if ( $ToKillAll < $FiringOnThese )
			{
				$ExcessShots = $FiringOnThese - $ToKillAll;
				$Diff =  $DefShips["Amount"] - $DefShips["ToBeKilled"];
				$DefShips["ToBeKilled"] = $DefShips["Amount"];
				CalcLog ( "ExcessShots : $ExcessShots, ToKillAll : $ToKillAll<br>");
				CalcLog ( "ToBeKilled: ". $DefShips["ToBeKilled"] ."<br>\n");
				CalcLog ( "Guns fired : $FiringOnThese<br>");

//				if ( $CalcLogBuffer[3] == "Restshots!" )
					CalcLog ( "killing <b>". $Diff . "</b> ". $DefShips["Type"]["Name"] ."(s)", 2);
//				else
//					CalcLog ( "killing <b>". $DefShips["ToBeKilled"] . "</b> ". $DefShips["Type"]["Name"] ."(s)", 2);

				return $ToKillAll;
			}
			elseif ( $ToKillTheFirst > $FiringOnThese )
			{
				$DefShips["Hits"] +=  floor($FiringOnThese * $AttShips["Type"]["Gunpower"] * $HitChance);
				CalcLog ( "ToBeKilled: ". $DefShips["ToBeKilled"] ."<br>\n");
				CalcLog ( "Hits: ". $DefShips["Hits"] ."<br>\n");
				CalcLog ( "Guns fired : $FiringOnThese<br>");

//				if ( $CalcLogBuffer[3] == "Restshots!" )
					CalcLog ( "killing <b>0</b> ". $DefShips["Type"]["Name"] ."(s)", 2);
//				else
//					CalcLog ( "killing <b>". $DefShips["ToBeKilled"] . "</b> ". $DefShips["Type"]["Name"] ."(s)", 2);

				return $FiringOnThese;
			}
			else
			{
				$Diff = 1 + floor( ( $FiringOnThese - $ToKillTheFirst ) / $ToKillOne );
				$DefShips["ToBeKilled"] += $Diff;

				/* Check if it's not-able to hit overflow, or not being able to hit strongly enough overflow */
				if ( $AttShips["Type"]["Gunpower"] * ( ( $FiringOnThese - $ToKillTheFirst ) % $ToKillOne) < $DefShips["Type"]["Armour"] )
					$DefShips["Hits"] = $AttShips["Type"]["Gunpower"] * ( ( $FiringOnThese - $ToKillTheFirst ) % $ToKillOne);
				else
					$DefShips["Hits"] = 0;

				CalcLog ( "ToBeKilled: ". $DefShips["ToBeKilled"] ."<br>\n");
				CalcLog ( "Hits: ". $DefShips["Hits"] ."<br>\n");
				CalcLog ( "Guns fired : $FiringOnThese<br>");

//				if ( $CalcLogBuffer[3] == "Restshots!" )
					CalcLog ( "killing <b>". $Diff ."</b> ". $DefShips["Type"]["Name"] ."(s)", 2);
//				else
//					CalcLog ( "killing <b>". $DefShips["ToBeKilled"] . "</b> ". $DefShips["Type"]["Name"] ."(s)", 2);

				return $FiringOnThese;
			}
		}
	}

    function ResolveShot( $AttShips, $DefShips )
    {
	    $RandomNr= rand(0, 100);

		if ( $AttShips["Type"]["Special"] == "EMPs" )
        {
            if ( $RandomNr < 100 - $DefShips["Type"]["Emp_res"] )
               	$DefShips["ToBeStunned"]++;
        }
		else if ( $RandomNr < 25 + $AttShips["Type"]["Weap_speed"] - $DefShips["Type"]["Agility"] )
        {
            $DefShips["ShotsOn"]++;
            $DefShips["Hits"] += $AttShips["Type"]["Gunpower"];
        }

//		if ( $AttShips["Type"]["Special"] == "Steals ships" )
//            $DefShips["BeingStolen"] = true;
    }

    function CleanUp( &$DefFlt )
    {
    	$Def = &$DefFlt["Ships"];
    	$DefFlt["Totals"] = null;

		for( $t = 0; $t < count($Def); $t++ )
        {
            if ( $Def[$t]["Amount"] > 0 && ( $Def[$t]["ToBeKilled"] > 0 || $Def[$t]["ToBeStunned"] > 0 ))
            {
            	if ( true )
            	{
			        CalcLog ( "<br>Cleaning up casualties<br>\n");
			        CalcLog ( "Name :". $Def[$t]["Type"]["Name"] . "<br>\n");
			        CalcLog ( "TargetNr :". $Def[$t]["TargetNr"] . "<br>\n");
					CalcLog ( "Hits :". $Def[$t]["Hits"] . "<br>\n");
					CalcLog ( "Armour :". $Def[$t]["Type"]["Armour"] . "<br>\n");
					CalcLog ( "Amount :". $Def[$t]["Amount"] . " of which Stunned :". $Def[$t]["Stunned"] . "<br>\n");
					CalcLog ( "ToBeKilled :". $Def[$t]["ToBeKilled"] . "<br>\n");
					CalcLog ( "ToBeStunned :". $Def[$t]["ToBeStunned"] . "<br><br>\n");
				}

				$Def[$t]["Amount"] -= $Def[$t]["ToBeKilled"];

				if ($Def[$t]["Amount"] < 0 )
					$Def[$t]["Amount"] = 0;

				$Def[$t]["Stunned"] += $Def[$t]["ToBeStunned"];

				if ( $Def[$t]["Stunned"] > $Def[$t]["BeginAmount"] )
					$Def[$t]["Stunned"] = $Def[$t]["BeginAmount"];

	        }
			$Def[$t]["ToBeKilled"] = 0;
	        $Def[$t]["ToBeStunned"] = 0;
	        $Def[$t]["TargetNr"] = 0;

	        CalcTotals( $DefFlt, $t, $DefFlt["Totals"] );
        }
    }

    function ClearHitsStuns ( &$Flt )
    {
		for ( $t = 0; $t < count($Flt); $t++ )
        {
        	$Flt[$t]["Stunned"] = 0;
        	$Flt[$t]["Hits"] = 0;
        }
    }

	function GetJSTargets ( $Att, $AttNr, $Def, $Side, $Target )
	{
		if ($Target == "Target1")
			$TmpTarget = 0;
		elseif ($Target == "Target2")
			$TmpTarget = 1;
		elseif ($Target == "Target3")
			$TmpTarget = 2;

		$ArrayStr = "	ShipTargets[$Side][$AttNr][$TmpTarget] = new Array(";
		$Tel = 0;

        $TargetCount = 0;

        for( $t = 0; $t < count($Def); $t++ )
        {
	  if ( array_key_exists ($AttNr, $Att) 
	       && IsTarget( $Att[$AttNr]["Type"], 
			    $Def[$t]["Type"], 
			    $Att[$AttNr]["Type"][$Target] ) )
            {
	      if ( $Tel++ > 0 ) $ArrayStr .= ",";
	      $ArrayStr .= $t;
            }
        }

        if ( $Tel == 1 )
        	$ArrayStr .= ", -1";

		$ArrayStr .= ");\n";

		return $ArrayStr;
	}


    function WriteJSInfo( &$Att, &$Def, $Side )
    {

		echo "   ShipTargets[$Side] = new Array;\n";

		$Count = (count($Att) > count($Def)) ? count($Att) : count($Def);

		for( $t = 0 ; $t < $Count  ; $t++)
		{
			echo "   ShipTargets[$Side][$t] = new Array;\n";
			$Str  = GetJSTargets( $Att, $t, $Def, $Side, "Target1" );
			$Str .= GetJSTargets( $Att, $t, $Def, $Side, "Target2" );
			$Str .= GetJSTargets( $Att, $t, $Def, $Side, "Target3" );
			echo $Str;
		}

	}

    function WriteFleets( $AttFlt, $DefFlt )
    {
		function MakeResult( $amount, $report, $report_in_place )
		{
			global $overall_accuracy;

			if ( $report_in_place && ($report || $amount) )
			{
				if ( $amount > $report )
				{
					$diff = abs($report-$amount);
					$calc_accuracy = 1 - ( $diff / $amount );
				}
				else
				{
					$diff = abs($amount-$report);
					$calc_accuracy = 1 - ( $diff / $report );
				}

				$overall_accuracy["tot_acc"] += $calc_accuracy;
				$overall_accuracy["tot_report"] += $report;
				$overall_accuracy["tot_amount"] += $amount;
				$overall_accuracy["count"]++;

				$calc_accuracy = round($calc_accuracy * 100);

				return "$amount<br><span class=estimate>report:$report acc:$calc_accuracy%</span>";
			}
			else
				return $amount;
		}

		global $Warning, $style, $ShipBattleRep, $overall_accuracy;

		$Att = &$AttFlt["Ships"];
		$Def = &$DefFlt["Ships"];

		for( $t = 0 ; $t < count($Def); $t++)
		{

			$amount = $Def[$t]["BeginAmount"]- $Def[$t]["Amount"];
			$report = $ShipBattleRep["def"][$Def[$t]["Type"]["Name"]]["lost"];
			$lost = MakeResult( $amount, $report, $ShipBattleRep );

			$amount = $Def[$t]["Stunned"];
			$report = $ShipBattleRep["def"][$Def[$t]["Type"]["Name"]]["stunned"];
			$stunned = MakeResult( $amount, $report, $ShipBattleRep );
?>
<tr>
<td class=namecel><a tabindex=-1 href='BCview.php?ViewShip=<?php echo  $t ?>&style=<?php echo $style?>' name=d<?php echo  $t ?> onMouseOver="OnNameOver( 0, <?php echo  $t ?>, 1)" onMouseOut="OnNameOver( 0, <?php echo  $t ?>, 0)"><?php echo  $Def[$t]["Type"]["Name"] ?></a></td>
<td class=inputcel><input tabindex=<?php echo  $t + 30 ?> type='text' size=5 name='td<?php echo  $Def[$t]["Type"]["FormName"] ?>' value='<?php echo $Def[$t]["Amount"]?>'></td>
<td class=lostcel><?php echo  $lost ?></td>
<td class=stunnedcel><?php echo  $stunned ?></td>
<?php
		if ( array_key_exists($t, $Att) && $Att[$t] )
		{
			$amount = $Att[$t]["BeginAmount"]- $Att[$t]["Amount"];
			$report = $ShipBattleRep["att"][$Att[$t]["Type"]["Name"]]["lost"];
			$lost = MakeResult( $amount, $report, $ShipBattleRep );

			$amount = $Att[$t]["Stunned"];
			$report = $ShipBattleRep["att"][$Att[$t]["Type"]["Name"]]["stunned"];
			$stunned = MakeResult( $amount, $report, $ShipBattleRep );


?>
<td class=namecel><a tabindex=-1 href='BCview.php?ViewShip=<?php echo  $t ?>&style=<?php echo $style?>' class=normal name=a<?php echo  $t ?> onMouseOver="OnNameOver( 1, <?php echo  $t ?>, 1)" onMouseOut="OnNameOver( 1, <?php echo  $t ?>, 0)"><?php echo  $Att[$t]["Type"]["Name"] ?></a></td>
<td class=inputcel><input tabindex=<?php echo  $t + count($Def) + 30 ?> type='text' size=5 name='ta<?php echo  $Att[$t]["Type"]["FormName"] ?>' value='<?php echo $Att[$t]["Amount"]?>'></td>
<td class=lostcel><?php echo  $lost ?></td>
<td class=stunnedcel><?php echo $stunned ?></td>
</tr>
<?php
	} else {
?>
<td class=namecel>&nbsp;</td>
<td class=inputcel>&nbsp;</td>
<td class=lostcel>&nbsp;</td>
<td class=stunnedcel>&nbsp;</td>
</tr>
<?php
		}
	}

	// Writing totals
	global $ShowTotals;
	if ( $ShowTotals ) :
?>
<tr>
<td class=namecel>Totals</td>
<td class=inputcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalShips"]["Amount"]) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalLost"]["Amount"]) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalStunned"]["Amount"]) ?></td>
<td class=namecel>Totals</td>
<td class=inputcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalShips"]["Amount"]) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalLost"]["Amount"]) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalStunned"]["Amount"]) ?></td>
</tr>
<tr>
<td class=namecel>Total Metal</td>
<td class=inputcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalShips"]["Metal"]) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalLost"]["Metal"]) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalStunned"]["Metal"]) ?></td>
<td class=namecel>Total Metal</td>
<td class=inputcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalShips"]["Metal"])?></td>
<td class=lostcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalLost"]["Metal"]) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalStunned"]["Metal"]) ?></td>
</tr>
<tr>
<td class=namecel>Total Crystal</td>
<td class=inputcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalShips"]["Crystal"]) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalLost"]["Crystal"]) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalStunned"]["Crystal"]) ?></td>
<td class=namecel>Total Crystal</td>
<td class=inputcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalShips"]["Crystal"]) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalLost"]["Crystal"]) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalStunned"]["Crystal"]) ?></td>
</tr>
<tr>
<td class=namecel>Total Eonium</td>
<td class=inputcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalShips"]["Eonium"]) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalLost"]["Eonium"]) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalStunned"]["Eonium"]) ?></td>
<td class=namecel>Total Eonium</td>
<td class=inputcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalShips"]["Eonium"]) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalLost"]["Eonium"]) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalStunned"]["Eonium"]) ?></td>
</tr>
<tr>
<td class=namecel>Fuel costs</td>
<td class=inputcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalShips"]["Fuel"]) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalLost"]["Fuel"]) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalStunned"]["Fuel"]) ?></td>
<td class=namecel>Fuel costs</td>
<td class=inputcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalShips"]["Fuel"]) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalLost"]["Fuel"]) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalStunned"]["Fuel"]) ?></td>
</tr>
<tr>
<td class=namecel>Fleet Points</td>
<td class=inputcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalShips"]["Worth"] ) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalLost"]["Worth"] ) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $DefFlt["Totals"]["TotalStunned"]["Worth"] ) ?></td>
<td class=namecel>Fleet Points</td>
<td class=inputcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalShips"]["Worth"] ) ?></td>
<td class=lostcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalLost"]["Worth"] ) ?></td>
<td class=stunnedcel><?php echo  AddKilosMils( $AttFlt["Totals"]["TotalStunned"]["Worth"] ) ?></td>
</tr>
	<?php
		endif;
	?>
<tr>
<input type=hidden name=CapRule value=<?php echo  (ISSET($CapRule)?$CapRule:"") ?>>
<td class=namecel>Planet score</td>
<td class=inputcel colspan=3 align=left>
<input tabindex=1000 type='text' size=15 name='dplanetscore' value='<?php echo $DefFlt["PlanetScore"]?>'> points
<?php
	if (  ($DefFlt["OriginalScore"] - $DefFlt["PlanetScore"]) > 0 && $DefFlt["PlanetScore"] > 0 )
		echo "<br>Lost : <span class=losttext>". (int)($DefFlt["OriginalScore"] - $DefFlt["PlanetScore"]) ."</span> points"
?>
</td><td class=namecel colspan=4>&nbsp;</td></tr>
<?php if ( $DefFlt["PlanetScore"] > 0 ) { ?>
<tr><td class=namecel>Capture info</td><td class=inputcell colspan=7>
<?php
		global $NumCalcs, $RoidChance, $RoidChanceHistory;


		$text = array();
		$ticks = array();

		for( $tt = 0 ; $tt < count($Def); $tt++)
		{
			if ( $Def[$tt]["Type"]["ShipClass"] == "RO" )
			{
				$RoidGrab = $Def[$tt]["BeginAmount"] - $Def[$tt]["Amount"];
				$BeginAmount = $Def[$tt]["BeginAmount"];
				$MaxGrab = 0;

				for( $i = 0; $i < $NumCalcs; $i++ )
				{
					$MaxGrab += floor($RoidChance*$BeginAmount);
					$BeginAmount -= floor($RoidChance*$BeginAmount);
					if (ISSET($GrabMax) && array_key_exists($i, $GrabMax))
					  $GrabMax[$i] += floor($RoidChance*$BeginAmount);
					else
					  $GrabMax[$i] = floor($RoidChance*$BeginAmount);
					if (ISSET($GrabActual) && array_key_exists($i, $GrabActual))
					  $GrabActual[$i] += $Def[$tt]["BeginAmount"] - $Def[$tt]["Amount"];
					else
					  $GrabActual[$i] = $Def[$tt]["BeginAmount"] - $Def[$tt]["Amount"];
				}

				$text[] = "<span class=losttext>". $RoidGrab ."</span> ". $Def[$tt]["Type"]["Name"] ."s, <span class=losttext>". $MaxGrab ."</span> max";
			}
		}

		for( $i = 0; $i < $NumCalcs; $i++ )
			$ticks[] =  "Tick ". ($i+1) .": <span class=losttext>". round($RoidChanceHistory[$i]*100) ."</span> % roidcap";


		echo "Max grab per tick:<br>";
		echo implode(" | ", $ticks);
		echo "<br>Total grabbed". ($NumCalcs==1?'':' (in '.$NumCalcs.' ticks)') .":<br>";
		echo implode(" | ", $text);
	}
?>
</td>
<tr>
	<td class=namecel>Salvage</td>
	<td class=inputcel colspan=3>
		<span class=losttext><?php echo  AddKilosMils( floor($DefFlt["Totals"]["TotalLost"]["Metal"] * 0.25) ) ?></span> Metal,
		<span class=losttext><?php echo  AddKilosMils( floor($DefFlt["Totals"]["TotalLost"]["Crystal"] * 0.25) ) ?></span> Crystal,
		<span class=losttext><?php echo  AddKilosMils( floor($DefFlt["Totals"]["TotalLost"]["Eonium"] * 0.25) ) ?></span> Eonium
	</td>
	<td class=inputcel colspan=4>&nbsp</td>
</tr>
</tr>
<?php
		if ( $Warning )
			echo "<tr><td class=warningcel colspan=8>$Warning</td></tr>";

		if ( $overall_accuracy["count"] )
		{
			$amount = $overall_accuracy["tot_amount"];
			$report = $overall_accuracy["tot_report"];

			if ( $amount > $report )
				$calc_accuracy = 1 - ( abs($amount-$report) / $amount );
			else
				$calc_accuracy = 1 - ( abs($amount-$report) / $report );

			$calc_accuracy = round($calc_accuracy * 100);

			$overall_accuracy["bigone"] = $calc_accuracy;

			echo "<tr><td class=warningcel colspan=8>Overall accuracy : $calc_accuracy% - battlereport added to archive (anonimous storage, just for bugsearches)<br>";
			echo "<span class=estimate>(Remember: battles have a random-chance builtin that *can* and *will* make the accuracy drop, the more ships=higher accurracy)</span></td></tr>";
		}

		/* end function WriteFleets */
	}

	function AddKilosMils ( $Value )
	{
		/* put a ..k or ...M after values to shorten them */
		if ( $Value > 20000000 )
			return round($Value / 100000)/10 . "M";
		if ( $Value > 100000 )
			return round($Value / 100)/10 . "k";

		return $Value;
	}

	function FillFleet( $Command, $Array )
	{
	        global $ShipTypes, $Fleet, $ShipBattleRep, $PasteText;
	
        	$Fleet[0]["Side"] = "<span class=attacker>Attacker</span>";
	        $Fleet[1]["Side"] = "<span class=defender>Defender</span>";
		$Fleet[0]["Totals"] = null;
		$Fleet[1]["Totals"] = null;

		if (ISSET( $Array["input"] )) {
			/* Edit input value for netscape */
			$Array["input"] = preg_replace( "/^\x20+(.*)$/m", "\\1", $Array["input"]);
			$Array["input"] = preg_replace( "/\n\r/", "\n \r", $Array["input"]);
			/* Edit  input value for opera & netscape finish */
			$Array["input"] = preg_replace( "/\r\n/", " ", $Array["input"]);

			$PasteText = $Array["input"];
		}

	        /* process unit scan/overview/military pastes */
		if ( ISSET($Array["input"]) && $Array["Addtype"] != "BattleReport" )
		{
			// $Array["input"] = preg_replace( "/\r/", " ", $Array["input"]);
			// $Array["input"] = preg_replace( "/\n/", " ", $Array["input"]);
			if ( ISSET($Array["fleetbase"]) || ISSET($Array["fleet1"]) || ISSET($Array["fleet2"]) || ISSET($Array["fleet3"]) )
				preg_match_all( "/(\w*\s?[\w]+)\s([0-9]+|\s)\s([0-9]+|\s)\s([0-9]+|\s)\s([0-9]+|\s)([^0-9a-zA-Z]|$)/iU", $Array["input"], $output, PREG_SET_ORDER  );
			else
				preg_match_all( "/(\w*\s?[\w]+)\s([0-9]+)([^0-9]|$)/isU", $Array["input"], $output, PREG_SET_ORDER  );

			foreach ( (array)$output as $row )
			{
				if ( $Array["fleetbase"] || $Array["fleet1"] || $Array["fleet2"] || $Array["fleet3"] )
				{
					if ( $Array["fleetbase"] )
						$row[2] = (int)$row[2];
					else
						$row[2] = 0;
					if ( $Array["fleet1"] )
						$row[2] += (int)$row[3];
					if ( $Array["fleet2"] )
						$row[2] += (int)$row[4];
					if ( $Array["fleet3"] )
						$row[2] += (int)$row[5];
				}

				switch ( trim($row[1]) )
				{
					case "Metal" : $ShipAddArray[$Array["Addtype"]]["Metal roid"] = $row[2]; break;
					case "Metal Asteroids" : $ShipAddArray[$Array["Addtype"]]["Metal roid"] = $row[2]; break;
					case "Crystal" : $ShipAddArray[$Array["Addtype"]]["Crystal roid"] = $row[2]; break;
					case "Crystal Asteroids" : $ShipAddArray[$Array["Addtype"]]["Crystal roid"] = $row[2]; break;
					case "Eonium" : $ShipAddArray[$Array["Addtype"]]["Eonium roid"] = $row[2]; break;
					case "Eonium Asteroids" : $ShipAddArray[$Array["Addtype"]]["Eonium roid"] = $row[2]; break;
					case "Resource" :
					case "Unknown" :
					case "Uninitiated" :
					case "Unknown Asteroids" : $ShipAddArray[$Array["Addtype"]]["Uninitiated roid"] = $row[2]; break;
					case "Roid Score" :
					case "Score" : $Array[$Array["Addtype"][0]."planetscore"] = $row[2]; break;
					default : $ShipAddArray[$Array["Addtype"]][trim("$row[1]")] = $row[2];
				}
			}
			/* seems to be missed */
		        $Fleet[0]["PlanetScore"]      = (array_key_exists("aplanetscore",$Array)?$Array["aplanetscore"]:0);
		        $Fleet[0]["PlanetScoreRatio"] = (array_key_exists("aplanetscoreratio", $Array)?$Array["aplanetscoreratio"]:0);
		        $Fleet[1]["PlanetScore"]      = (array_key_exists("dplanetscore", $Array)?$Array["dplanetscore"]:0);
		        $Fleet[1]["PlanetScoreRatio"] = (array_key_exists("dplanetscoreratio", $Array)?$Array["dplanetscoreratio"]:0);
		}


		/* process battle report */
		if ( $Array["input"] && $Array["Addtype"] == "BattleReport")
		{
		// $Array["input"] = preg_replace( "/\n\r/", "\n \r", $Array["input"]);
		// $Array["input"] = preg_replace( "/\r\n/", " ", $Array["input"]);
			global $NumCalcs;

			// preg_match_all( "/(\w*\s?[\w]+)\s*((\s[0-9]+)+)/i", $Array["input"], $output, PREG_SET_ORDER  );
			preg_match_all( "/(\w*\s*[A-Za-z]+)((\s+[0-9]+)+)/i", $Array["input"], $output, PREG_SET_ORDER  );

			if ( $NumCalcs != 1 )
			{
				$Warning = "Ticks calc limited to 1 tick when recalculating battlereports!";
				$NumCalcs = 1;
			}

			foreach ( (array)$output as $row )
			{

				switch ( trim($row[1]) )
				{
					case "Metal" : $ShipType = "Metal roid"; break;
					case "Crystal" : $ShipType = "Crystal roid"; break;
					case "Eonium" : $ShipType = "Eonium roid"; break;
					case "Unknown" :
					case "Resource" : $ShipType = "Uninitiated roid"; break;
					case "Score" : $Array["planetscore"] = $row[2];break;
					default : $ShipType = trim($row[1]);
				}

				foreach ( $ShipTypes as $Ship )
				{
					if ( strtolower($Ship["Name"]) == strtolower($ShipType) )
					{
						$values = explode( " ", $row[2] );

						if ( stristr( $ShipType, "planetscore" ) )
							$Array[$ShipType] = $values[1];

						if ( (stristr( $ShipType, "turret" ) || stristr( $ShipType, "cannon" ) || stristr( $ShipType, "emitter" ) || stristr( $ShipType, "launcher" ) || stristr( $ShipType, "roid" )) && count($values) < 6  )
						{
							$ShipBattleRep["def"][$ShipType]["total"] = $ShipAddArray["def"][$ShipType] = $values[1];
							$ShipBattleRep["def"][$ShipType]["lost"] = $values[2];
						}
						else
						{
							$ShipBattleRep["att"][$ShipType]["total"] = $ShipAddArray["att"][$ShipType] = $values[1];
							$ShipBattleRep["att"][$ShipType]["lost"] = $values[2];
							$ShipBattleRep["att"][$ShipType]["stunned"] = $values[3];
							$ShipBattleRep["def"][$ShipType]["total"] = $ShipAddArray["def"][$ShipType] = $values[4];
							$ShipBattleRep["def"][$ShipType]["lost"] = $values[5];
							$ShipBattleRep["def"][$ShipType]["stunned"] = $values[6];
						}
					}
				}
			}
	    	$Fleet[0]["PlanetScore"] = $Array["aplanetscore"];
	    	$Fleet[0]["PlanetScoreRatio"] = $Array["aplanetscoreratio"];
	    	$Fleet[1]["PlanetScore"] = $Array["dplanetscore"];
	    	$Fleet[1]["PlanetScoreRatio"] = $Array["dplanetscoreratio"];
		}


        if ( $Command == "Load" )
        {
        	$tel_att = 0;
        	$tel_def = 0;
		    foreach( (array)$Array as $key => $value )
		    {
		    	if ( $key[0] == "t" )
		    	{
		    		/* clear values if inserting battlereport */
		    		if ( $Array["Addtype"] == "BattleReport" )
		    			$value = 0;

			    	if ( $key[1] == "a" )
			    	{


				  if ( $Array["Addtype"] ) {
					  if (ISSET($ShipAddArray))
		    				$value += $ShipAddArray["att"][$ShipTypes[$tel_att]["Name"]];
				  }
		    			CreateShipGroup( $Fleet[0]["Ships"][$tel_att], $ShipTypes[$tel_att], $value);
		        		CalcTotals( $Fleet[0], $tel_att, $Fleet[0]["Totals"] );
		    			$tel_att++;
		    		}
		    		elseif ( $key[1] == "d" )
		    		{
		    			if ( $Array["Addtype"] ){
					  if (ISSET($ShipAddArray))
		    				$value += $ShipAddArray["def"][$ShipTypes[$tel_def]["Name"]];
					}
		    			CreateShipGroup( $Fleet[1]["Ships"][$tel_def], $ShipTypes[$tel_def], $value);
		        		CalcTotals( $Fleet[1], $tel_def, $Fleet[1]["Totals"] );
		    			$tel_def++;

		    		}
		    	}
		    }
		    $Fleet[0]["PlanetScore"]      = (array_key_exists("aplanetscore",$Array)?$Array["aplanetscore"]:0);
		    $Fleet[0]["PlanetScoreRatio"] = (array_key_exists("aplanetscoreratio", $Array)?$Array["aplanetscoreratio"]:0);
		    $Fleet[1]["PlanetScore"]      = (array_key_exists("dplanetscore", $Array)?$Array["dplanetscore"]:0);
		    $Fleet[1]["PlanetScoreRatio"] = (array_key_exists("dplanetscoreratio", $Array)?$Array["dplanetscoreratio"]:0);
	    }

	    if ( $Command == "New" )
	    {
	    	for( $y = 0; $y < 2; $y++ )
	    	{
		    	for( $t = 0; $t < count($ShipTypes); $t++ )
		    	{
		    		if ( $ShipTypes[$t]["Init"] <= 2 && $y == 0 && $ShipTypes[$t]["Special"] != "Anti PDS" ) continue;
		    		CreateShipGroup( $Fleet[$y]["Ships"][$t], $ShipTypes[$t], 0 );

	        		CalcTotals( $Fleet[$y], $t, $Fleet[$y]["Totals"] );
		    	}
		    }
	    	$Fleet[0]["PlanetScore"] = 0;
	    	$Fleet[0]["PlanetScoreRatio"] = 0;
	    	$Fleet[1]["PlanetScore"] = 0;
	    	$Fleet[1]["PlanetScoreRatio"] = 0;
		}

    	$Fleet[0]["OriginalScore"] = $Fleet[0]["PlanetScore"];
    	$Fleet[1]["OriginalScore"] = $Fleet[1]["PlanetScore"];

    }
?>
