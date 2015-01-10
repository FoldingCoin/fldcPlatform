<?php
//calculate.php - calculate fldcPlatform tokens for awarding each awarding period

$projBase='/home/jsewell/foldingCoinPlatform/';
$projBin='bin';

include($projBase.$projBin.'/includes/functions.php');
include($projBase.$projBin.'/includes/classes.php');
include($projBase.'/config/db.php');

$runStart=time();
echo "Start ".date("c",$runStart)."\n";

$platformAssets=populateAssets();

foreach($platformAssets as $platformAsset){
	$assetName=$platformAsset->assetName;
	$awardingPeriod=$platformAsset->awardingPeriod;
	
	echo "Now processing ".$assetName."\n";
	//determine most current snapshot timestamp
	$mostRecentSnapshot=getMostRecentSnapshot($assetName,$mode);
	echo "mostRecentSnapshot $mostRecentSnapshot\n";
	$currentSnapRecords=populateCurrentSnapRecords($mostRecentSnapshot,$assetName,$mode);
	//var_dump($currentSnapRecords);
	foreach($currentSnapRecords as $currentSnapRecord){
		$normalizedFolder= new normalizedFolder();
		$normalizedFolder->assetName=$currentSnapRecord->assetName;
		$normalizedFolder->snaptype=$currentSnapRecord->snaptype;
		$normalizedFolder->address=$currentSnapRecord->address;
		$normalizedFolder->friendlyName=$currentSnapRecord->friendlyName;
		$normalizedFolder->fahName=$currentSnapRecord->fahName;
		$normalizedFolder->fahTeam=$currentSnapRecord->fahTeam;
		$normalizedFolder->cumulativeCredits=$currentSnapRecord->cumulativeCredits;

		$folderFahSHA256=$currentSnapRecord->fahSHA256;
		$previousSnapRecord=getPreviousSnapRecord($folderFahSHA256,$assetName,$normalizedFolder,$mostRecentSnapshot,$awardingPeriod,$mode);
		echo "dumping currentSnapRecord\n";
		var_dump($currentSnapRecord);
		echo "dumping previousSnapRecord\n";
		var_dump($previousSnapRecord);
	
	}
}





function getMostRecentSnapshot($assetName,$mode){
	$mostRecentSnapshot=1;
	$db=dbConnect();
	$recentSnapQuery="SELECT * FROM fldcPlatform.platformCredits WHERE assetName = '$assetName' AND mode = '$mode' ORDER BY snapshotTimestamp DESC LIMIT 1";
	if ($recentSnapResults=$db->query($recentSnapQuery)) {
		while($recentSnapRow=$recentSnapResults->fetch_assoc()){
			$thisAsset='';
			$snapshotTimestamp=$recentSnapRow['snapshotTimestamp'];
			//echo "assetName $assetName snapshotTimestamp $snapshotTimestamp\n";
		}
	}

	$mostRecentSnapshot=$snapshotTimestamp;
	return($mostRecentSnapshot);
}

function populateCurrentSnapRecords($mostRecentSnapshot,$assetName,$mode){
	$currentSnapRecords='';
	$db=dbConnect();
	$currentSnapQuery="SELECT * FROM fldcPlatform.platformCredits WHERE assetName = '$assetName' AND snapshotTimestamp = '$mostRecentSnapshot' AND mode = '$mode'";
	if ($currentSnapResults=$db->query($currentSnapQuery)) {
		while($currentSnapRow=$currentSnapResults->fetch_assoc()){
			$snaptype=$currentSnapRow['snaptype'];
			$address=$currentSnapRow['address'];
			$friendlyName=$currentSnapRow['friendlyName'];
			$fahName=$currentSnapRow['fahName'];
			$fahTeam=$currentSnapRow['fahTeam'];
			$fahSHA256=$currentSnapRow['fahSHA256'];
			$cumulativeCredits=$currentSnapRow['cumulativeCredits'];

			$normalizedFolder= new normalizedFolder();
			$normalizedFolder->assetName=$assetName;
			$normalizedFolder->snaptype=$snaptype;
			$normalizedFolder->address=$address;
			$normalizedFolder->friendlyName=$friendlyName;
			$normalizedFolder->fahName=$fahName;
			$normalizedFolder->fahTeam=$fahTeam;
			$normalizedFolder->cumulativeCredits=$cumulativeCredits;
			//echo "in populateCurrentSnapRecords...\n";
			//var_dump($normalizedFolder);
			$currentSnapRecord=new snapshotRecord($assetName,$normalizedFolder,$mostRecentSnapshot,$mode);
			//var_dump($currentSnapRecord);
			
			
			//echo "\n";
			$currentSnapRecords[]=$currentSnapRecord;
		}
	}

	return($currentSnapRecords);
}


function getPreviousSnapRecord($folderFahSHA256,$assetName,$normalizedFolder,$mostRecentSnapshot,$awardingPeriod,$mode){
	$previousSnapRecord='';

	$previousTimestampBase=$mostRecentSnapshot-$awardingPeriod;
	//right now using /16 as 24 hrs/16 is 1.5 hours, seems like a good +/- for time stamp
	//may want to do some more precise/generalized math if there is a coin on a different awardingPeriod
	//$previousTimestampStart=$previousTimestampBase-($awardingPeriod/16);
	//$previousTimestampEnd=$previousTimestampBase+($awardingPeriod/16);
	$previousTimestampStart=$previousTimestampBase-7200;
	$previousTimestampEnd=$previousTimestampBase+7200;
	$endStartDiff=$previousTimestampEnd-$previousTimestampStart;
	echo "mostRecentSnapshot $mostRecentSnapshot, previousTimestampBase $previousTimestampBase,\n";
	echo "previousTimestampStart $previousTimestampStart, previousTimestampEnd $previousTimestampEnd, endStartDiff $endStartDiff\n";
	
	$previousSnapRecord=new snapshotRecord($assetName,$normalizedFolder,$mostRecentSnapshot,$mode);
	$db=dbConnect();
	$previousSnapQuery="SELECT * FROM fldcPlatform.platformCredits WHERE fahSHA256 = '$folderFahSHA256' AND snapshotTimestamp > $previousTimestampStart AND snapshotTimestamp < $previousTimestampEnd AND assetName = '$assetName' AND mode = '$mode'";
	echo "$previousSnapQuery\n";
	if ($previousSnapResults=$db->query($previousSnapQuery)) {
		while($previousSnapRow=$previousSnapResults->fetch_assoc()){
			echo "retrieved previous snap from DB:\n";
			echo "timestamp ".$previousSnapRow['snapshotTimestamp']."\n";
			echo "credits ".$previousSnapRow['cumulativeCredits']."\n";
			$previousSnapRecord->address=$previousSnapRow['address'];
			$previousSnapRecord->snapshotTimestamp=$previousSnapRow['snapshotTimestamp'];
			$previousSnapRecord->cumulativeCredits=$previousSnapRow['cumulativeCredits'];
		}
	}
	//var_dump($previousSnapRecord);
	
	return($previousSnapRecord);
}




?>