<?php


include('config.php');

@ini_set("output_buffering", "Off");
@ini_set('implicit_flush', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('max_execution_time', 48000);

if(!file_exists($_SERVER['DOCUMENT_ROOT'] . "/worldbank/cronWorldDevelopmentIndicatorDataCronLog.txt")){
	
	file_put_contents('cronWorldDevelopmentIndicatorDataCronLog.txt', "running\n", FILE_APPEND);
	
	$selectCountries = $db->query('SELECT `Country Name`, `Country Code` FROM `worldbank_indicators` GROUP BY `Country Name`');
	
	while($countryInfo = $selectCountries->fetch_array(MYSQLI_ASSOC)){
		
		$countryName = $countryInfo['Country Name'];
		$countryCode = $countryInfo['Country Code'];
		
		$selectSeries = $db->query('SELECT `Series Name`, `Series Code` FROM `worldbank_indicators` GROUP BY `Series Name`');
	
		while($seriesInfo = $selectSeries->fetch_array(MYSQLI_ASSOC)){
			
			$seriesName = $seriesInfo['Series Name'];
			$seriesCode = $seriesInfo['Series Code'];
			
			$str = file_get_contents('http://api.worldbank.org/countries/' . $countryCode . '/indicators/' . $seriesCode . '?format=json');
			$json = json_decode($str, true);
			
			$findTotalPages = $json[0]['pages'];
			$dataArray = array();
			
			if($findTotalPages > 1){
		
				for($i = 1; $i <= $findTotalPages; $i++){
					
					$str = file_get_contents('http://api.worldbank.org/countries/' . $countryCode . '/indicators/' . $seriesCode . '?format=json&page=' . $i);
					$pg_json = json_decode($str, true);
					
					foreach($pg_json[1] as $jsonData){
						$dataArray[] = $jsonData;
					}
					
				}
				
			} else {
				
				$dataArray = $json[1];
				
			}
			
			$yearlyData = array();
			
			$i = 0;
			
			$updateFieldQuery = array();
			
			foreach($dataArray as $key => $value){
				
				$yearlyData[$i]['year'] = $value['date'];
				$yearlyData[$i]['value'] = $value['value'];
				
				$result = $db->query('SHOW COLUMNS FROM `worldbank_indicators` LIKE "' . $yearlyData[$i]['year'] . ' [YR' . $yearlyData[$i]['year'] . ']"');
				$exists = ($result->num_rows) ? TRUE : FALSE;
				
				if(!$exists){
					$db->query('ALTER TABLE `worldbank_indicators` ADD COLUMN ' . $yearlyData[$i]['year'] . ' [YR' . $yearlyData[$i]['year'] . '] TEXT NULL');
				}
				
				$updateFieldQuery[] = '`' . $yearlyData[$i]['year'] . ' [YR' . $yearlyData[$i]['year'] . ']` = "' . $yearlyData[$i]['value'] . '"';
				
				$i++;
			}
			
			$db->query('UPDATE `worldbank_indicators` SET ' . implode(', ', $updateFieldQuery) . ' WHERE `Country Name` = "' . $countryName . '" AND `Country Code` = "' . $countryCode . '" AND `Series Name` = "' . $seriesName . '" AND `Series Code` = "' . $seriesCode . '"');
			
		}
		
	}
	
	unlink('cronWorldDevelopmentIndicatorDataCronLog.txt');
	
}

?>