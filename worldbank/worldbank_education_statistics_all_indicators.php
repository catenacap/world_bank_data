<?php

 
 exit;
 
include('config.php');

@ini_set("output_buffering", "Off");
@ini_set('implicit_flush', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('max_execution_time', 48000);

$file = fopen('Innitial  - b77c7871-485e-4d26-b6f5-614bec4bb1cd_Data.csv', 'r');

$createDBColumns = '';
$dbColumnsArray = array();

$i = 0;
while(($line = fgetcsv($file)) !== FALSE) {
	
	if($i == 0){
		
		foreach($line as $column){
			
			$column = preg_replace("/[^A-Za-z0-9 \[\]]/", '', $column);
			
			if(trim($column) == 'Country'){
				$dbColumnsArray[] = '`' . trim($column) . ' Name`';
			} else if(trim($column) == 'Series'){
				$dbColumnsArray[] = '`' . trim($column) . ' Name`';
			} else if(trim($column) == 'Series Code'){
				$dbColumnsArray[] = '`' . trim($column) . '`';
			} else if(trim($column) == 'Country Code'){
				$dbColumnsArray[] = '`' . trim($column) . '`';
			} else if(trim($column) >= '2020'){
				$explodedYears = explode(' ', trim($column));
				$dbColumnsArray[] = '`' . $explodedYears[0] . '`';
			} else {
				$dbColumnsArray[] = '`' . trim($column) . '`';
			}
			
		}
		
	}
	
	if($i > 0){
		
		$columnArrayValue = array();
		
		foreach($line as $columnValue){
			
			$columnArrayValue[] = '"' . $columnValue . '"';
			
		}
		
		$db->query('INSERT INTO `worldbank_education_statistics_all_indicators` (' . implode(', ', $dbColumnsArray) . ') VALUES (' . implode(', ', $columnArrayValue) . ')');
		
	}
  
  $i++;
}

fclose($file);
 
?>