<?php
//profitabilityData.php - retrieves data for the profitability calculator
//run this every 15 mins in cron
//Copyright © 2015 FoldingCoin Inc., All Rights Reserved

class profitabilityData{
	var $credits='';
	var $poloniexPrice='';
	var $btcPrice='';
	var $fldcPerDay='';
}

$reportsDir='/var/www/html/platformReports/FLDC';


$statsUrl='http://stats.foldingcoin.net/platformReports/FLDC/stats.txt';
$statsLines=file($statsUrl);
foreach($statsLines as $statsLine){
	list($statsHeading,$statsValue)=explode("|",$statsLine);
	//change below when adding to new platform code, It's not "24 hr." but "Awarding Period"
	if(preg_match("/Awarding Period/",$statsHeading)){
		$periodCredits=$statsValue;
		$periodCredits=preg_replace("/,/","",$periodCredits);
		$periodCredits=trim($periodCredits);
	}
}

$poloniexTickerUrl='https://poloniex.com/public?command=returnTicker';
$poloniexTickerJson=file_get_contents($poloniexTickerUrl);
$poloniexTickerData=json_decode($poloniexTickerJson);
foreach($poloniexTickerData as $poloniexTickerPair => $poloniexTickerResults){
	if($poloniexTickerPair=="BTC_FLDC"){
		$lastPoloniexPrice=$poloniexTickerResults->last;
		echo "lastPoloniexPrice $lastPoloniexPrice\n";
	}
}

$blockchainTickerUrl='https://blockchain.info/ticker';
$blockchainTickerJson=file_get_contents($blockchainTickerUrl);
$blockchainTickerData=json_decode($blockchainTickerJson);
foreach($blockchainTickerData as $blockchainTickerPair => $blockchainTickerResults){
	if($blockchainTickerPair=="USD"){
	$lastBlockchainPrice=$blockchainTickerResults->{'15m'};
		//var_dump($blockchainTickerResults);
		echo "lastBlockchainPrice $lastBlockchainPrice\n";
	}
}



$profitabilityData=new profitabilityData();
$profitabilityData->credits=$periodCredits;
$profitabilityData->poloniexPrice=$lastPoloniexPrice;
//change this on 
$profitabilityData->fldcPerDay=500000;

$profitabilityData->btcPrice=$lastBlockchainPrice;
$profitabilityJson=json_encode($profitabilityData);
var_dump($profitabilityJson);

$profitabilityFileName=$reportsDir."/profitabilityData.json";
$profitabilityFileHandle=fopen($profitabilityFileName,"w");
fwrite($profitabilityFileHandle,$profitabilityJson);
fclose($profitabilityFileHandle);

?>