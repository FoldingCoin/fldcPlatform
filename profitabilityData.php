<?php
//profitabilityData.php - retrieves data for the profitability calculator
//run this every 15 mins in cron
//Copyrght © 2015 FoldingCoin Inc., All Rights Reserved

class profitabilityData{
	var $credits='';
	var $poloniexPrice='';
}

$reportsDir='/home/jsewell/public_html/platformReports/FLDC';


$statsUrl='http://foldingcoin.net/FLDC-Daily-Payout-Summaries/stats.txt';
$statsLines=file($statsUrl);
foreach($statsLines as $statsLine){
	list($statsHeading,$statsValue)=explode("|",$statsLine);
	//change below when adding to new platform code, It's not "24 hr." but "Awarding Period"
	if(preg_match("/24 hr./",$statsHeading)){
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
	}
}

$profitabilityData=new profitabilityData();
$profitabilityData->credits=$periodCredits;
$profitabilityData->poloniexPrice=$lastPoloniexPrice;
$profitabilityData->fldcPerDay=500000;
$profitabilityJson=json_encode($profitabilityData);
var_dump($profitabilityJson);

$profitabilityFileName=$reportsDir."/profitabilityData.json";
$profitabilityFileHandle=fopen($profitabilityFileName,"w");
fwrite($profitabilityFileHandle,$profitabilityJson);
fclose($profitabilityFileHandle);

?>