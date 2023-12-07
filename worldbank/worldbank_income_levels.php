<?php

 
include('config.php');

$str = file_get_contents('http://api.worldbank.org/incomeLevels?format=json');
$json = json_decode($str, true);

$findTotalPages = $json[0]['pages'];
$dataArray = array();

if($findTotalPages > 1){
	
	for($i = 1; $i <= $findTotalPages; $i++){
		
		$str = file_get_contents('http://api.worldbank.org/incomeLevels?format=json&page=' . $i);
		$pg_json = json_decode($str, true);
		
		foreach($json[1] as $jsonData){
			$dataArray[] = $jsonData;
		}
		
	}
	
} else {
	
	$dataArray = $json[1];
	
}

if(count($dataArray) > 0){
	
	$dbTableColumns = array_keys($dataArray[0]);
	$dbCreateColumn = '';
	$dbCreateColumnArray = array();
	
	foreach($dbTableColumns as $dbColumn){
		
		if($dbColumn == 'id'){
			$dbColumn = 'short_code';
			$dbCreateColumn .= '`' . $dbColumn . '` varchar(10) NOT NULL, ';
		} else {
			$dbCreateColumn .= '`' . $dbColumn . '` varchar(255) NOT NULL, ';
		}
		
		$dbCreateColumnArray[] = $dbColumn;
		
	}
	
	$checkDBTableExists = $db->query("SELECT * FROM information_schema.tables WHERE table_schema = 'invacio_db1' AND table_name = 'worldbank_income_levels' LIMIT 1");
	
	if($checkDBTableExists->num_rows > 0){
		
		// Find total fields and check worldbank sources and fields having same numbers or not
		$checkFieldsExists = $db->query("SELECT * FROM information_schema.columns WHERE table_name = 'worldbank_income_levels'");
		
		$dbFieldsArray = array();
								
		if((count($dbCreateColumnArray) + 1) > $checkFieldsExists->num_rows){
			
			while($dbFields = $checkFieldsExists->fetch_array(MYSQLI_ASSOC)){
				
				$dbFieldsArray[] = $dbFields['COLUMN_NAME'];
				
			}
			
		}
		
		unset($dbFieldsArray[0]);
		
		foreach($dbCreateColumnArray as $val){
						
			if(!in_array($val, $dbFieldsArray)){
				
				$db->query("ALTER TABLE worldbank_income_levels ADD COLUMN `" . $val . "` text NOT NULL");
					
			}
			
		}
		
	}
	
	// Create Database table
	$db->query('CREATE TABLE IF NOT EXISTS `worldbank_income_levels` (
									  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
									  ' . $dbCreateColumn . '
									  PRIMARY KEY (`id`)
									) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8');
									
	
	$checkDBTableExists = $db->query("SELECT * FROM information_schema.tables WHERE table_schema = 'invacio_db1' AND table_name = 'worldbank_income_levels' LIMIT 1");
	
	if($checkDBTableExists->num_rows > 0){
		
		// Find total fields and check worldbank sources and fields having same numbers or not
		$checkFieldsExists = $db->query("SELECT * FROM information_schema.columns WHERE table_name = 'worldbank_income_levels'");
		
		while($dbFields = $checkFieldsExists->fetch_array(MYSQLI_ASSOC)){
			
			if($dbFields['COLUMN_NAME'] == 'short_code'){
				$dbFieldsArray[] = 'id';
			} else {
				$dbFieldsArray[] = $dbFields['COLUMN_NAME'];
			}
			
		}
		
	}
	
	array_shift($dbFieldsArray);
				
	foreach($dataArray as $dataRow){
		
		$createTableKeysArray = array();
		$createTableValuesArray = array();
		$createTableWhereQuery = '';
		$createTableUpdateQuery = array();
		
		foreach($dataRow as $key => $value){
			
			if(in_array($key, $dbFieldsArray)){
				
				if($key == 'id'){
					$key = 'short_code';
				}
				
				$createTableWhereQuery .= ' AND `' . $key . '` = ' . '"' . $db->real_escape_string(trim(strip_tags($value))) . '"';
				$createTableUpdateQuery[] = '`' . $key . '` = ' . '"' . $db->real_escape_string(trim(strip_tags($value))) . '"';
				$createTableKeysArray[] = '`' . $key . '`';
				$createTableValuesArray[] = '"' . $db->real_escape_string(trim(strip_tags($value))) . '"';
			}
			
		}
		
		$selectWorldSourcesData = $db->query('SELECT * FROM `worldbank_income_levels` WHERE 1 = 1 ' . $createTableWhereQuery);
				
		if($selectWorldSourcesData->num_rows == 0){
			
			$db->query('INSERT INTO `worldbank_income_levels` (' . implode(', ', $createTableKeysArray) . ') VALUES (' . implode(', ', $createTableValuesArray) . ')');
			
		} else {
			
			$sourceDBInfo = $selectWorldSourcesData->fetch_array(MYSQLI_ASSOC);
			
			$db->query('UPDATE `worldbank_income_levels` SET ' . implode(', ', $createTableUpdateQuery) . ' WHERE `id` = "' . $sourceDBInfo['id'] . '"');
			
		}
		
	}
	
}
 
?>