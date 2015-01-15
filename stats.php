<?php
//stats.php - Generate Payout Stats
//Copyrght © 2015 FoldingCoin Inc., All Rights Reserved

$projBase='/home/jsewell/foldingCoinPlatform/';
$projBin='bin';

$reportsDir='/home/jsewell/public_html/platformReports/';

include($projBase.$projBin.'/includes/functions.php');
include($projBase.$projBin.'/includes/classes.php');
include($projBase.'/config/db.php');

$runStart=time();
echo "Start ".date("c",$runStart)."\n";

$platformAssets=populateAssets();

foreach($platformAssets as $platformAsset){
	$assetName=$platformAsset->assetName;
	$mostRecentSnapshot=getMostRecentSnapshot($assetName,$mode);
	$payoutRecords=populatePayoutRecords($assetName,$mostRecentSnapshot,$mode);
	echo "Now Processing Stats for $assetName\n";
	//var_dump($payoutRecords);
	writePayoutReport($platformAsset,$payoutRecords,$mostRecentSnapshot,$reportsDir,$mode,$runStart);
	writeStatsFile($assetName,$payoutRecords,$mostRecentSnapshot,$reportsDir,$mode);
	
	
	
	
}


function writePayoutReport($platformAsset,$payoutRecords,$mostRecentSnapshot,$reportsDir,$mode,$runStart){
	$assetName=$platformAsset->assetName;
	$assetLogoUrl=$platformAsset->logoUrl;
	
	$reportYYYYMMDD=date("Ymd",$mostRecentSnapshot);
	$reportFileName=$reportsDir.$assetName.'-'.$reportYYYYMMDD.'-'."payoutReport.html";
	echo "reportFileName $reportFileName\n";
	
	
	//write the HTML for the folder credits/coins report
	$reportHtml='';
	$reportHtml=$reportHtml."<html><head><title>$assetName :: Distribution Report</title></head>\n";
	$reportHtml=$reportHtml."<body><h2>$assetName :: Distribution Report</h2>\n";
	$reportHtml=$reportHtml."<img src=$assetLogoUrl>\n";
	$reportHtml=$reportHtml."<p>Distribution Snapshot dated ".date("c",$mostRecentSnapshot)."</p>\n";

	$reportTotalCredits=0;
	$reportTotalTokens=0;
	$reportTotalPct=0;
	
	//figure out totals
	foreach($payoutRecords as $payoutRecord){
		$reportTotalCredits=$reportTotalCredits+$payoutRecord->periodCredits;
		$reportTotalTokens=$reportTotalTokens+$payoutRecord->periodTokens;
	}
	
	$tokensPerCredit=$reportTotalTokens/$reportTotalCredits;
	$reportHtml=$reportHtml."<p>In this awarding period, 1 folding credit is worth $tokensPerCredit $assetName tokens.</p>\n";

	$reportHtml=$reportHtml."<p>Payouts:</p>\n<table border = 1><tr><th>Folder Name</th><th>Folder Team</th><th>Folder Address</th><th>Credits Folded This Period</th><th>Percentage</th><th>$assetName Paid</th></tr>\n";
	//then go through the payouts one at a time
	foreach($payoutRecords as $payoutRecord){
		$address=$payoutRecord->address;
		$periodTokens=$payoutRecord->periodTokens;
		$periodCredits=$payoutRecord->periodCredits;
		$friendlyName=$payoutRecord->friendlyName;
		$fahTeam=$payoutRecord->fahTeam;
		
		$folderPct=sprintf("%01.2f",($periodTokens/$reportTotalTokens)*100);
		$reportTotalPct=$reportTotalPct+$folderPct;
		
		if($periodTokens>0){
			$reportHtml=$reportHtml."<tr><td>$friendlyName</td><td>$fahTeam</td><td>$address</td><td>$periodCredits</td><td>$folderPct%</td><td>$periodTokens</td></tr>\n";
		}
	}
	$reportHtml=$reportHtml."<tr><td></td><td>Totals</td><td>$reportTotalCredits</td><td>".round($reportTotalPct,0)."</td><td>".sprintf("%01.0f",$reportTotalTokens)."</td>\n";
	$reportHtml=$reportHtml."</table>\n";
	$reportHtml=$reportHtml."<p>This report generated ".date("c",$runStart)."</p>\n";
	$reportHtml=$reportHtml."</body></html>\n";

	$reportHtmlFileHandle=fopen($reportFileName,"w");
	fwrite($reportHtmlFileHandle,$reportHtml);
	fclose($reportHtmlFileHandle);

}



function writeStatsFile($assetName,$payoutRecords,$mostRecentSnapshot,$reportsDir,$mode){
	$statFileName=$reportsDir.$assetName.'-'."stats.txt";
	echo "$statFileName\n";
	$statCode='';
	$activeFolders=0;
	$allFolders=0;
	$totalFoldedCredits=0;
	
	foreach($payoutRecords as $payoutRecord){
		if($payoutRecord->periodCredits>0){
			$activeFolders=$activeFolders+1;
		}
		$totalFoldedCredits=$totalFoldedCredits+$payoutRecord->periodCredits;
		$allFolders=$allFolders+1;
	}
	
	$allCredits=getAllCredits($mostRecentSnapshot,$assetName,$mode);
	
	$statCode=$statCode."|<b>All $assetName Folders</b>\n";
	$statCode=$statCode."Folders: |$activeFolders ($allFolders)\n";
	$statCode=$statCode."24 hr. Credits: |".number_format($totalFoldedCredits)."\n";
	$statCode=$statCode."Credits: |".number_format($allCredits)."";

	echo "=begin stats====\n";
	echo "$statCode\n";
	echo "=end stats====\n";
	$statFileHandle=fopen($statFileName,"w");
	fwrite($statFileHandle,$statCode);
	fclose($statFileHandle);

}

function getAllCredits($mostRecentSnapshot,$assetName,$mode){
	$currentSnapRecords=populateCurrentSnapRecords($mostRecentSnapshot,$assetName,$mode);
	$allCredits=0;
	foreach($currentSnapRecords as $currentSnapRecord){
		$allCredits=$allCredits+$currentSnapRecord->cumulativeCredits;
	}
	return($allCredits);
}


function populatePayoutRecords($assetName,$mostRecentSnapshot,$mode){
	$db=dbConnect();
	//find all the payouts for this timestamp and asset
	$payoutsQuery="SELECT * FROM fldcPlatform.platformPayouts WHERE payoutTimestamp = $mostRecentSnapshot AND assetName = '$assetName' AND mode = '$mode' ORDER BY payoutCredits DESC";
	echo "$payoutsQuery\n";
	if ($payoutsResults=$db->query($payoutsQuery)) {
		while($payoutsRow=$payoutsResults->fetch_assoc()){
			$assetName=$payoutsRow['assetName'];
			$address=$payoutsRow['address'];
			$snapshotTimestamp=$payoutsRow['payoutTimestamp'];
			$friendlyName=$payoutsRow['friendlyName'];
			$fahName=$payoutsRow['fahName'];
			$fahTeam=$payoutsRow['fahTeam'];
			$fahSHA256=$payoutsRow['fahSHA256'];
			$cumulativeCredits=$payoutsRow['cumulativeCredits'];
			$mode=$payoutsRow['mode'];
			$payoutCredits=$payoutsRow['payoutCredits'];
			$payoutTokens=$payoutsRow['payoutTokens'];
			
			$normalizedFolder=new normalizedFolder();
			$normalizedFolder->assetName=$assetName;
			$normalizedFolder->address=$address;
			$normalizedFolder->friendlyName=$friendlyName;
			$normalizedFolder->fahName=$fahName;
			$normalizedFolder->fahTeam=$fahTeam;
			
			$payoutRecords[]=new payoutRecord($snapshotTimestamp,$assetName,$normalizedFolder,$mode,$payoutCredits,$payoutTokens);
		}
	}
	return($payoutRecords);
}





?>