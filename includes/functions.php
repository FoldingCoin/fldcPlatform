<?php
//functions.php - basic utility functions for entire project
//Copyright © 2015 FoldingCoin Inc., All Rights Reserved




function populateAssets(){
	$db=dbConnect();

	$assetsQuery="SELECT * FROM fldcPlatform.platformAssets";
	if ($assetsResults=$db->query($assetsQuery)) {
		while($assetsRow=$assetsResults->fetch_assoc()){
			$thisAsset='';
			$assetName=$assetsRow['assetName'];
			$awardingPeriod=$assetsRow['awardingPeriod'];
			$tokensPerPeriod=$assetsRow['tokensPerPeriod'];
			$teamNumber=$assetsRow['teamNumber'];
			$lastPaidTimestamp=$assetsRow['lastPaidTimestamp'];
			if($lastPaidTimestamp==''){$lastPaidTimestamp=0;}
			$emailAddress=$assetsRow['emailAddress'];
			$logoUrl=$assetsRow['logoUrl'];
			
			$thisAsset=new platformAsset($assetName,$awardingPeriod,$tokensPerPeriod,$teamNumber,$lastPaidTimestamp,$emailAddress,$logoUrl);

			$platformAssets[]=$thisAsset;
		}
	}
	return($platformAssets);
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
	$currentSnapQuery="SELECT * FROM fldcPlatform.platformCredits WHERE assetName = '$assetName' AND snapshotTimestamp = '$mostRecentSnapshot' AND mode = '$mode' ORDER BY cumulativeCredits DESC";
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












//hex input must be in uppercase, with no leading 0x
 
define("ADDRESSVERSION","00"); //this is a hex byte
 
function decodeHex($hex)
{
        $hex=strtoupper($hex);
        $chars="0123456789ABCDEF";
        $return="0";
        for($i=0;$i<strlen($hex);$i++)
        {
                $current=(string)strpos($chars,$hex[$i]);
                $return=(string)bcmul($return,"16",0);
                $return=(string)bcadd($return,$current,0);
        }
        return $return;
}
 
function encodeHex($dec)
{
        $chars="0123456789ABCDEF";
        $return="";
        while (bccomp($dec,0)==1)
        {
                $dv=(string)bcdiv($dec,"16",0);
                $rem=(integer)bcmod($dec,"16");
                $dec=$dv;
                $return=$return.$chars[$rem];
        }
        return strrev($return);
}
 
function decodeBase58($base58)
{
        $origbase58=$base58;
       
        $chars="123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
        $return="0";
        for($i=0;$i<strlen($base58);$i++)
        {
                $current=(string)strpos($chars,$base58[$i]);
                $return=(string)bcmul($return,"58",0);
                $return=(string)bcadd($return,$current,0);
        }
       
        $return=encodeHex($return);
       
        //leading zeros
        for($i=0;$i<strlen($origbase58)&&$origbase58[$i]=="1";$i++)
        {
                $return="00".$return;
        }
       
        if(strlen($return)%2!=0)
        {
                $return="0".$return;
        }
       
        return $return;
}
 
function encodeBase58($hex)
{
        if(strlen($hex)%2!=0)
        {
                die("encodeBase58: uneven number of hex characters");
        }
        $orighex=$hex;
       
        $chars="123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
        $hex=decodeHex($hex);
        $return="";
        while (bccomp($hex,0)==1)
        {
                $dv=(string)bcdiv($hex,"58",0);
                $rem=(integer)bcmod($hex,"58");
                $hex=$dv;
                $return=$return.$chars[$rem];
        }
        $return=strrev($return);
       
        //leading zeros
        for($i=0;$i<strlen($orighex)&&substr($orighex,$i,2)=="00";$i+=2)
        {
                $return="1".$return;
        }
       
        return $return;
}
 
function hash160ToAddress($hash160,$addressversion=ADDRESSVERSION)
{
        $hash160=$addressversion.$hash160;
        $check=pack("H*" , $hash160);
        $check=hash("sha256",hash("sha256",$check,true));
        $check=substr($check,0,8);
        $hash160=strtoupper($hash160.$check);
        return encodeBase58($hash160);
}
 
function addressToHash160($addr)
{
        $addr=decodeBase58($addr);
        $addr=substr($addr,2,strlen($addr)-10);
        return $addr;
}
 
function checkAddress($addr,$addressversion=ADDRESSVERSION)
{
        $addr=decodeBase58($addr);
        if(strlen($addr)!=50)
        {
                return false;
        }
        $version=substr($addr,0,2);
        if(hexdec($version)>hexdec($addressversion))
        {
                return false;
        }
        $check=substr($addr,0,strlen($addr)-8);
        $check=pack("H*" , $check);
        $check=strtoupper(hash("sha256",hash("sha256",$check,true)));
        $check=substr($check,0,8);
        return $check==substr($addr,strlen($addr)-8);
}
 
function hash160($data)
{
        $data=pack("H*" , $data);
        return strtoupper(hash("ripemd160",hash("sha256",$data,true)));
}
 
function pubKeyToAddress($pubkey)
{
        return hash160ToAddress(hash160($pubkey));
}
 
function remove0x($string)
{
        if(substr($string,0,2)=="0x"||substr($string,0,2)=="0X")
        {
                $string=substr($string,2);
        }
        return $string;
}





















?>