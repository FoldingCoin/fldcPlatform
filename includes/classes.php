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
	var $logoUrl;

	function __construct($assetName,$awardingPeriod,$tokensPerPeriod,$teamNumber,$lastPaidTimestamp,$emailAddress,$logoUrl){
		$this->assetName=$assetName;
		$this->awardingPeriod=$awardingPeriod;
		$this->tokensPerPeriod=$tokensPerPeriod;
		$this->teamNumber=$teamNumber;
		$this->lastPaidTimestamp=$lastPaidTimestamp;
		$this->emailAddress=$emailAddress;
		$this->logoUrl=$logoUrl;
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
	function logoUrlSet($logoUrl){
		$this->logoUrl=$logoUrl;
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
				list($friendlyName,$address)=explode("_".$currentAsset."_",$name);
				$this->address=$address;
				$this->friendlyName=$friendlyName;
				$this->fahName=$name;
				$this->fahTeam=$team;
				$this->cumulativeCredits=$credits;
				//var_dump($this);
			}
		}
	}

	function allAssetsFinder($line,$platformAsset){
		list($name,$credits,$workUnits,$team)=explode("\t",$line);
		$this->assetName=$platformAsset->assetName;
		$this->snaptype='anyTeam';
		list($friendlyName,$address)=explode("_"."ALL"."_",$name);
		$this->address=$address;
		$this->friendlyName=$friendlyName;
		$this->fahName=$name;
		$this->fahTeam=$team;
		$this->cumulativeCredits=$credits;
		echo "for an ALL folder...\n";
		var_dump($this);
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

class payoutRecord{
	var $snapshotTimestamp;
	var $assetName;
	var $address;
	var $friendlyName;
	var $fahName;
	var $fahTeam;
	var $fahSHA256;
	var $cumulativeCredits;
	var $mode;
	var $periodCredits;
	var $periodTokens;

	function __construct($snapshotTimestamp,$assetName,$normalizedFolder,$mode,$periodCredits,$periodTokens){
		$this->snapshotTimestamp=$snapshotTimestamp;
		$this->assetName=$assetName;
		$this->address=$normalizedFolder->address;
		$this->friendlyName=$normalizedFolder->friendlyName;
		$this->fahName=$normalizedFolder->fahName;
		$this->fahTeam=$normalizedFolder->fahTeam;
		$this->fahSHA256=hash('SHA256',$normalizedFolder->fahTeam.$normalizedFolder->fahName);
		$this->cumulativeCredits=$normalizedFolder->cumulativeCredits;
		$this->mode=$mode;
		$this->periodCredits=$periodCredits;
		$this->periodTokens=$periodTokens;
	}
	
	function insertPayout(){
		$insertResult='start';
		$db=dbConnect();
		if ($stmt = $db->prepare("INSERT INTO fldcPlatform.platformPayouts (assetName,payoutTimestamp,fahSHA256,address,friendlyName,fahName,fahTeam,payoutCredits,payoutTokens,cumulativeCredits,mode) VALUES (?,?,?,?,?,?,?,?,?,?,?)")) {
			/* bind parameters for markers */
			$stmt->bind_param("sissssiidis", $this->assetName,$this->snapshotTimestamp,$this->fahSHA256,$this->address,$this->friendlyName,$this->fahName,$this->fahTeam,$this->periodCredits,$this->periodTokens,$this->cumulativeCredits,$this->mode);
			/* execute query */
			$stmt->execute();
			//var_dump($stmt);
			/* close statement */
			$stmt->close();
		}
		return($insertResult);
	}




}







?>