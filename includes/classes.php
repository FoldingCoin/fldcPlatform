<?php
//classes.php - class definitions for entire project
//Copyrght © 2015 FoldingCoin Inc., All Rights Reserved

class platformAsset{
	var $assetName;
	var $awardingPeriod;
	var $tokensPerPeriod;
	var $teamNumber;
	var $lastPaidTimestamp;
	var $emailAddress;

	function __construct($assetName,$awardingPeriod,$tokensPerPeriod,$teamNumber,$lastPaidTimestamp,$emailAddress){
		$this->assetName=$assetName;
		$this->awardingPeriod=$awardingPeriod;
		$this->tokensPerPeriod=$tokensPerPeriod;
		$this->teamNumber=$teamNumber;
		$this->lastPaidTimestamp=$lastPaidTimestamp;
		$this->emailAddress=$emailAddress;
	}

	function assetNameSet($assetName){
		$this->assetName=$assetName;
	}
	function awardingPeriodSet($awardingPeriod){
		$this->awardingPeriod=$awardingPeriod;
	}
	function tokensPerPeriodSet($tokensPerPeriod){
		$this->tokensPerPeriod=$tokensPerPeriod;
	}
	function teamNumberSet($teamNumber){
		$this->teamNumber=$teamNumber;
	}
	function lastPaidTimestampSet($lastPaidTimestamp){
		$this->lastPaidTimestamp=$lastPaidTimestamp;
	}
	function emailAddressSet($emailAddress){
		$this->emailAddress=$emailAddress;
	}
}

class normalizedFolder{
	var $assetName;
	var $snaptype;
	var $address;
	var $friendlyName;
	var $fahName;
	var $fahTeam;
	var $cumulativeCredits;

	function __construct(){
		$this->address='invalid';
	}
	
	function ownTeamFinder($line,$platformAssets){
		list($name,$credits,$workUnits,$team)=explode("\t",$line);
		foreach($platformAssets as $platformAsset){
			if($team==$platformAsset->teamNumber){
				$this->assetName=$platformAsset->assetName;
				$this->snaptype='ownTeam';
				$this->address=$name;
				$this->friendlyName=$team;
				$this->fahName=$name;
				$this->fahTeam=$team;
				$this->cumulativeCredits=$credits;
				//var_dump($this);
			}
		}
	}
	function anyTeamFinder($line,$platformAssets){
		list($name,$credits,$workUnits,$team)=explode("\t",$line);
		foreach($platformAssets as $platformAsset){
			$currentAsset=$platformAsset->assetName;
			if(preg_match("/\_$currentAsset\_/",$name)){
				$this->assetName=$platformAsset->assetName;
				$this->snaptype='anyTeam';
				list($freindlyName,$address)=explode("_".$currentAsset."_",$name);
				$this->address=$address;
				$this->friendlyName=$freindlyName;
				$this->fahName=$name;
				$this->fahTeam=$team;
				$this->cumulativeCredits=$credits;
				//var_dump($this);
			}
		}
	}
}

class snapshotRecord{
	var $snapshotTimestamp;
	var $assetName;
	var $snaptype;
	var $address;
	var $friendlyName;
	var $fahName;
	var $fahTeam;
	var $fahSHA256;
	var $cumulativeCredits;
	var $mode;
	
	function __construct($recordAsset,$normalizedFolder,$snapshotTimestamp,$mode){
		$this->snapshotTimestamp=$snapshotTimestamp;
		$this->mode=$mode;
		$this->assetName=$recordAsset;

		$this->snaptype=$normalizedFolder->snaptype;
		$this->address=$normalizedFolder->address;
		$this->friendlyName=$normalizedFolder->friendlyName;
		$this->fahName=$normalizedFolder->fahName;
		$this->fahTeam=$normalizedFolder->fahTeam;
		$this->fahSHA256=hash('SHA256',$normalizedFolder->fahTeam.$normalizedFolder->fahName);
		$this->cumulativeCredits=$normalizedFolder->cumulativeCredits;
	}
	
}









?>