<?php

$json = file_get_contents('http://stats.foldingcoin.net/platformReports/FLDC/profitabilityData.json');
$obj = json_decode($json);

$avgFldc = number_format((float)$obj->{'fldcPerDay'}/$obj->{'credits'}, 8, '.', '');
?>
<html>
<script language=JavaScript>


function profit() {
 var fahPPD = document.calc.fahPPD.value;
 var avgFLDC = document.calc.avgFLDC.value;
 var fldcPrice = document.calc.fldcPrice.value;
 var btcPrice = document.calc.btcPrice.value;
 var result1 = fahPPD*avgFLDC;
 var result2 = result1.toFixed(8);
 var result3 = fahPPD*avgFLDC*fldcPrice;
 var result4 = result3.toFixed(8);
 var result5 = result4*btcPrice;
 document.getElementById("fldcPerDay").innerHTML = result2
 document.getElementById("btcPerDay").innerHTML = result4
 document.getElementById("usdPerDay").innerHTML = '$'+result5.toFixed(2)
 }
//-->

</script>

<form name="calc" method="post">
	<table cellSpacing="1" cellPadding="1" border="0">
		<tr>
			<td>Your estimated FAH PPD:</td><td><input size='10' name='fahPPD'></td>	
		</tr>
		<tr>
			<td>FLDC Per FAH Credit:</td><td><input size='10' name='avgFLDC' value='<?php echo $avgFldc;?>'></td>
		</tr>
		<tr>
			<td>FLDC/BTC Price:</td><td><input size='10' name='fldcPrice' value='<?php echo $obj->{'fldcPrice'}; ?>'></td>
		</tr>
		<tr>
			<td>BTC/USD Price:</td><td>$<input size='10' name='btcPrice' value='<?php echo $obj->{'btcPrice'};?>'></td>
		<tr>
			<td>FLDC Per Day:</td><td> <b id='fldcPerDay'></b></td>
		</tr>
		<tr>
			<td>BTC Per Day:</td><td> <b id='btcPerDay'></b></td>
		</tr>
		<tr>
			<td>USD Per Day:</td><td> <b id='usdPerDay'></b></td>
		<tr>
			<td><input onclick='profit()'  type='button' value='Calculate'><input onclick='this.form.reset();' type='button' value='Reset Prices'></td>
		</tr>
	</table>
</form>


</html>