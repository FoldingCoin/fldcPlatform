<?php
//calculate.php - calculate fldcPlatform tokens for awarding each awarding period
//Copyright © 2015 FoldingCoin Inc., All Rights Reserved

$projBase='/home/fldcPlatform/mergedFolding/';
$projBin='bin';

include($projBase.$projBin.'/includes/functions.php');
include($projBase.$projBin.'/includes/classes.php');
include($projBase.'/config/db.php');

$runStart=time();
echo "Start ".date("c",$runStart)."\n";

$platformAssets=populateAssets();

foreach($platformAssets as $platformAsset){
	$payoutRecords='';
	$assetName=$platformAsset->assetName;
	$awardingPeriod=$platformAsset->awardingPeriod;
	$allFolderCredits=0;
	
	echo "Now processing ".$assetName."\n";
	//determine most current snapshot timestamp
	$mostRecentSnapshot=getMostRecentSnapshot($assetName,$mode);
	//echo "mostRecentSnapshot $mostRecentSnapshot ".date("c",$mostRecentSnapshot)."\n";
	
	///date debug
	//$mostRecentSnapshot=1423891201;
	///date debug
	
	$payoutPlusMinus=5400;
	$mostRecentPayout=getMostRecentPayout($assetName,$mode);
	echo "mostRecentSnapshot $mostRecentSnapshot mostRecentPayout $mostRecentPayout\n";

/*

$mostRecentPayout<($mostRecentSnapshot-$payoutPlusMinus)

($mostRecentSnapshot>(($mostRecentPayout+$awardingPeriod)+$payoutPlusMinus)) OR
($mostRecentSnapshot>(($mostRecentPayout+$awardingPeriod)-$payoutPlusMinus))


*/



	if(
		($mostRecentSnapshot>(($mostRecentPayout+$awardingPeriod)+$payoutPlusMinus)) OR
		($mostRecentSnapshot>(($mostRecentPayout+$awardingPeriod)-$payoutPlusMinus))
	){//THIS IS WHERE to check if the awarding period has gone by
		$currentSnapRecords=populateCurrentSnapRecords($mostRecentSnapshot,$assetName,$mode);
		echo "dumping current snap records\n";
		var_dump($currentSnapRecords);
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
			$previousSnapRecord='';
			$previousSnapRecord=getPreviousSnapRecord($folderFahSHA256,$assetName,$normalizedFolder,$mostRecentSnapshot,$awardingPeriod,$mode);
			//echo "dumping currentSnapRecord\n";
			//var_dump($currentSnapRecord);
			//echo "dumping previousSnapRecord\n";
			//var_dump($previousSnapRecord);
			
			$periodCredits=$currentSnapRecord->cumulativeCredits-$previousSnapRecord->cumulativeCredits;
			$allFolderCredits=$allFolderCredits+$periodCredits;
			//this is a temporary placeholder, as we will calculate the full token payout after getting all the deltas
			$periodTokens=0;
			$payoutTimestamp=$currentSnapRecord->snapshotTimestamp;
			
			$payoutRecords[$folderFahSHA256]=new payoutRecord($payoutTimestamp,$assetName,$normalizedFolder,$mode,$periodCredits,$periodTokens);
		}
		
		if($allFolderCredits==0){
			$allFolderCredits=.00000001;
		}
		$tokensPerFahCredit=$platformAsset->tokensPerPeriod/$allFolderCredits;
		echo "For this snapshot, 1 FAH credit gets you ".sprintf("%01.8f",$tokensPerFahCredit)." tokens of $assetName\n";
		
		foreach($payoutRecords as $payoutRecord){
			$payoutFahSHA256=$payoutRecord->fahSHA256;
			$payoutTokens=$tokensPerFahCredit*$payoutRecord->periodCredits;
			$payoutTokens=sprintf("%01.8f",$payoutTokens);
			$payoutRecord->periodTokens=$payoutTokens;
			
			if($payoutRecord->periodTokens > 0){
				$csv[$platformAsset->assetName][$payoutFahSHA256]=$payoutRecord->address.",".sprintf("%01.8f",$payoutRecord->periodTokens)."\n";
			}
			
			$payoutInsertResult=$payoutRecord->insertPayout();
			
			
		}
	}
	
	
	
}

//now render CSV
foreach($csv as $assetName => $csvLines){
	$db=dbConnect();
	//echo "CSV for $assetName...\n";
	$csvEmailBody='';
	$csvEmailBody=$csvEmailBody."<html><body><p>$assetName Payouts for ".date("c",$payoutTimestamp)."</p>\n<p>Valid Payouts</p>\n<pre>\n";
	foreach($csvLines as $csvLine){
		$csvEmailBody=$csvEmailBody.$csvLine;	
	}
	$csvEmailBody=$csvEmailBody."</pre>\n</body></html>";
	//echo "$csvEmailBody";
	
	if(preg_match("/live/",$mode)){
		$assetEmailQuery="SELECT * FROM fldcPlatform.platformAssets WHERE assetName = '$assetName'";
		if ($assetEmailResults=$db->query($assetEmailQuery)) {
			while($assetEmailRow=$assetEmailResults->fetch_assoc()){
				$toEmail=$assetEmailRow['emailAddress'];
				//override while running catch up days
				//$toEmail="jsewell@foldingcoin.net";
			}
		}
		$subject='';
	}elseif(preg_match("/test/",$mode)){
		$assetEmailQuery="SELECT * FROM fldcPlatform.platformAssets WHERE assetName = '$assetName'";
		if ($assetEmailResults=$db->query($assetEmailQuery)) {
			while($assetEmailRow=$assetEmailResults->fetch_assoc()){
				$toEmail=$assetEmailRow['emailAddress'];
			}
		}
		//if we're testing, override the email to just me, rather than DB
		$toEmail="jsewell@foldingcoin.net";
		$subject='Test Mode ';
	}
	
	$subject = $subject."$assetName Merged Folding VPS Production Daily Distribution ".date("c",$payoutTimestamp);
	$fromEmail = "jsewell@foldingcoin.net";
	$headers = 'From: ' . $fromEmail . "\r\n" . 'X-Mailer: PHP/' . phpversion();
	$headers .= 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	$mailResult = mail($toEmail,$subject,$csvEmailBody,$headers);
	echo "mailresult $mailResult\n";

}



function getMostRecentPayout($assetName,$mode){
	$mostRecentPayout='';
	$db=dbConnect();
	$mostRecentPayoutQuery="SELECT * FROM fldcPlatform.platformPayouts WHERE assetName = '$assetName' AND mode = '$mode' ORDER BY payoutTimestamp DESC LIMIT 1";
	if ($mostRecentPayoutResults=$db->query($mostRecentPayoutQuery)) {
		while($mostRecentPayoutRow=$mostRecentPayoutResults->fetch_assoc()){
			$mostRecentPayout=$mostRecentPayoutRow['payoutTimestamp'];
		}
	}
	return($mostRecentPayout);
}





function getPreviousSnapRecord($folderFahSHA256,$assetName,$normalizedFolder,$mostRecentSnapshot,$awardingPeriod,$mode){
	$previousSnapRecord='';

	$previousTimestampBase=$mostRecentSnapshot-$awardingPeriod;
	//right now just doing +/- 7200 secs (2 hrs +/-)
	//may want to do some more precise/generalized math if there is a coin on a different awardingPeriod
	$previousTimestampStart=$previousTimestampBase-7200;
	$previousTimestampEnd=$previousTimestampBase+7200;
	$endStartDiff=$previousTimestampEnd-$previousTimestampStart;
	//echo "mostRecentSnapshot $mostRecentSnapshot, previousTimestampBase $previousTimestampBase,\n";
	//echo "previousTimestampStart $previousTimestampStart, previousTimestampEnd $previousTimestampEnd, endStartDiff $endStartDiff\n";
	
	$previousSnapRecord=new snapshotRecord($assetName,$normalizedFolder,$mostRecentSnapshot,$mode);
	$previousSnapRecord->cumulativeCredits=0;
	$db=dbConnect();
	$previousSnapQuery="SELECT * FROM fldcPlatform.platformCredits WHERE fahSHA256 = '$folderFahSHA256' AND snapshotTimestamp > $previousTimestampStart AND snapshotTimestamp < $previousTimestampEnd AND assetName = '$assetName' AND mode = '$mode'";
	//echo "$previousSnapQuery\n";
	if ($previousSnapResults=$db->query($previousSnapQuery)) {
		while($previousSnapRow=$previousSnapResults->fetch_assoc()){
			//echo "retrieved previous snap from DB:\n";
			//echo "timestamp ".$previousSnapRow['snapshotTimestamp']."\n";
			//echo "credits ".$previousSnapRow['cumulativeCredits']."\n";
			$previousSnapRecord->address=$previousSnapRow['address'];
			$previousSnapRecord->snapshotTimestamp=$previousSnapRow['snapshotTimestamp'];
			$previousSnapRecord->cumulativeCredits=$previousSnapRow['cumulativeCredits'];
		}
	}
	//var_dump($previousSnapRecord);
	
	return($previousSnapRecord);
}








?>
