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
	$payoutRecords='';	
	$assetName=$platformAsset->assetName;
	$mostRecentSnapshot=getMostRecentSnapshot($assetName,$mode);
	$payoutRecords=populatePayoutRecords($assetName,$mostRecentSnapshot,$mode);
	//var_dump($payoutRecords);
	if($payoutRecords!=""){
		echo "Now Processing Stats for $assetName\n";
		writePayoutReport($platformAsset,$payoutRecords,$mostRecentSnapshot,$reportsDir.$assetName,$mode,$runStart);
		writeStatsFile($assetName,$payoutRecords,$mostRecentSnapshot,$reportsDir.$assetName,$mode);
		createHtmlIndexes($platformAsset,$reportsDir.$assetName);
	}
	
	
	
}


function writePayoutReport($platformAsset,$payoutRecords,$mostRecentSnapshot,$reportsDir,$mode,$runStart){
	$assetName=$platformAsset->assetName;
	$assetLogoUrl=$platformAsset->logoUrl;
	
	$reportYYYYMMDD=date("Ymd",$mostRecentSnapshot);
	$reportFileName=$reportsDir.'/'.$reportYYYYMMDD.'-'."payoutReport.html";
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
	$reportHtml=$reportHtml."<p>In this awarding period, 1 folding credit is worth ".number_format($tokensPerCredit,8)." $assetName tokens.</p>\n";

	$reportHtml=$reportHtml."<p>Payouts:</p>\n<table border = 1><tr><th>Folder Name</th><th>Folder Team</th><th>Folder Address</th><th>Credits Folded This Period</th><th>Percentage</th><th>$assetName Paid</th></tr>\n";
	//then go through the payouts one at a time
	foreach($payoutRecords as $payoutRecord){
		$address=$payoutRecord->address;
		$periodTokens=$payoutRecord->periodTokens;
		$periodCredits=$payoutRecord->periodCredits;
		$friendlyName=$payoutRecord->friendlyName;
		$fahTeam=$payoutRecord->fahTeam;
		
		$folderPct=number_format((($periodTokens/$reportTotalTokens)*100),2);
		$reportTotalPct=$reportTotalPct+$folderPct;
		
		if($periodTokens>0){
			$reportHtml=$reportHtml."<tr><td>$friendlyName</td><td>$fahTeam</td><td>$address</td><td>".number_format($periodCredits)."</td><td>$folderPct%</td><td>".number_format($periodTokens,8)."</td></tr>\n";
		}
	}
	$reportHtml=$reportHtml."<tr><td></td><td></td><td>Totals</td><td>".number_format($reportTotalCredits)."</td><td>".round($reportTotalPct,0)."</td><td>".number_format(round($reportTotalTokens,0))."</td>\n";
	$reportHtml=$reportHtml."</table>\n";
	$reportHtml=$reportHtml."<p>This report generated ".date("c",$runStart)."</p>\n";
	$reportHtml=$reportHtml."</body></html>\n";

	$reportHtmlFileHandle=fopen($reportFileName,"w");
	fwrite($reportHtmlFileHandle,$reportHtml);
	fclose($reportHtmlFileHandle);

}



function writeStatsFile($assetName,$payoutRecords,$mostRecentSnapshot,$reportsDir,$mode){
	$statFileName=$reportsDir."/stats.txt";
	echo "statFileName $statFileName\n";
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
	$statCode=$statCode."Awarding Period Credits: |".number_format($totalFoldedCredits)."\n";
	$statCode=$statCode."Credits: |".number_format($allCredits)."";

	//echo "=begin stats====\n";
	//echo "$statCode\n";
	//echo "=end stats====\n";
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
	if(isset($payoutRecords)){
		return($payoutRecords);
	}else{
		return('');
	}
}


function createHtmlIndexes($platformAsset,$reportsDir){
	$assetName=$platformAsset->assetName;
	$logoUrl=$platformAsset->logoUrl;
	
	$assetIndexFileName=$reportsDir."/index.html";
	$assetIndexHtml='';
	$assetIndexHtml=$assetIndexHtml."<html><head><title>$assetName :: Distribution History</title></head>
<body><h2>$assetName :: Distribution Summary</h2>
<img src=$logoUrl>\n";

	$files=scandir($reportsDir);
	foreach($files as $file){
		if(!is_dir($file) AND preg_match("/html/",$file) AND !preg_match("/reports/",$file) AND !preg_match("/index/",$file)){
			echo "doing $file\n";
			$yyyy=substr($file,0,4);
			$mm=substr($file,4,2);
			$dd=substr($file,6,2);
			$unixtime=mktime(8,0,5,$mm,$dd,$yyyy);
			$month=date("F",$unixtime);

			$assetIndexHtml=$assetIndexHtml."<p><a href=".$reportsDir."/$file>Payouts $month $dd, $yyyy</a></p>\n";
		}
	}
	$assetIndexHtml=$assetIndexHtml."</body></html>\n";
	echo "$assetIndexHtml\n";
	echo "assetIndexFileName $assetIndexFileName\n";
	$assetIndexFileHandle=fopen($assetIndexFileName,"w");
	fwrite($assetIndexFileHandle,$assetIndexHtml);
	fclose($assetIndexFileHandle);

}


?>
