<?php
//db.php - contains db configs
//Copyright © 2015 FoldingCoin Inc., All Rights Reserved

//modes can be 'testPlatform' for development, 'qaPlatform' for QA, or 'livePlatform' for Live

$mode='livePlatform';


function dbConnect(){
	$db_host='myhost';
	$db_user='myuser';
	$db_pass='mypass';
	$db_scehma='fldcPlatform';

	/* Connect to a MySQL server */
	$db = new mysqli($db_host, $db_user, $db_pass, $db_scehma);

	if (mysqli_connect_errno()) {
		printf("Can't connect to MySQL Server. Errorcode: %s\n", mysqli_connect_error());
		exit;
	}
	return $db;
}












?>