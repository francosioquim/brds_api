<?php

class Syncdb
{
	public $host = 'localhost';
	public $dbname = 'sync';
	public $dbname2 = 'brds_db';
	public $user = 'root';
	public $password = 'password';

    public function getTable($tablename) {
		$serverName = "QASV"; //serverName\instanceName
		$connectionInfo = array( "Database"=>"QAS", "UID"=>"Manten", "PWD"=>"@kaitou2");
		$conn = sqlsrv_connect( $serverName, $connectionInfo);

		if( $conn ) {
		    // Execute query
			$stmt = "SELECT * FROM ".$tablename;
		    if(($result = sqlsrv_query($conn,$stmt)) !== false){
		        $return_value = array();
		        while( $obj = sqlsrv_fetch_object( $result )) {
		              array_push( $return_value, $obj);
		        }
		        return $return_value;
		    } else {
		    	echo "Error getting database results.<br />";
		    }

		} else{
		    echo "Connection could not be established.<br />";
		    die( print_r( sqlsrv_errors(), true));
		}
    }

    public function getTableEntry($tablename, $column, $filter) {
		$serverName = "QASV"; //serverName\instanceName
		$connectionInfo = array( "Database"=>"QAS", "UID"=>"Manten", "PWD"=>"@kaitou2");
		$conn = sqlsrv_connect( $serverName, $connectionInfo);

		if( $conn ) {
		    // Execute query
			$stmt = "SELECT * FROM ".$tablename." WHERE ".$column." = '".$filter."'";
		    if(($result = sqlsrv_query($conn,$stmt)) !== false){
		        return sqlsrv_fetch_object( $result );
		    } else {
		    	echo "Error getting database results.<br />";
		    }

		} else{
		    echo "Connection could not be established.<br />";
		    die( print_r( sqlsrv_errors(), true));
		}
    }

    public function getTableEntries($tablename, $column, $filter) {
		$serverName = "QASV"; //serverName\instanceName
		$connectionInfo = array( "Database"=>"QAS", "UID"=>"Manten", "PWD"=>"@kaitou2");
		$conn = sqlsrv_connect( $serverName, $connectionInfo);

		if( $conn ) {
		    // Execute query
			$stmt = "SELECT * FROM ".$tablename." WHERE ".$column." = '".$filter."'";
		    if(($result = sqlsrv_query($conn,$stmt)) !== false){
		        $return_value = array();
		        while( $obj = sqlsrv_fetch_object( $result )) {
		              array_push( $return_value, $obj);
		        }
		        return $return_value;
		    } else {
		    	echo "Error getting database results.<br />";
		    }

		} else{
		    echo "Connection could not be established.<br />";
		    die( print_r( sqlsrv_errors(), true));
		}
    }

    public function getMaterials($tablename, $column, $filter) {
		$serverName = "QASV"; //serverName\instanceName
		$connectionInfo = array( "Database"=>"QAS", "UID"=>"Manten", "PWD"=>"@kaitou2");
		$conn = sqlsrv_connect( $serverName, $connectionInfo);

		if( $conn ) {
		    // Execute query
			$stmt = "SELECT * FROM ".$tablename." INNER JOIN qas.MAKT ON qas.MARA.MATNR=qas.MAKT.MATNR WHERE ".$column." LIKE '".$filter."%'";

		    if(($result = sqlsrv_query($conn,$stmt)) !== false){
		        $return_value = array();
		        while( $obj = sqlsrv_fetch_object( $result )) {
		              array_push( $return_value, $obj);
		        }
		        return $return_value;
		    } else {
		    	echo "Error getting database results.<br />";
		    }

		} else{
		    echo "Connection could not be established.<br />";
		    die( print_r( sqlsrv_errors(), true));
		}
    }

    public function getMaterialsConversion($tablename, $column, $filter) {
		$serverName = "QASV"; //serverName\instanceName
		$connectionInfo = array( "Database"=>"QAS", "UID"=>"Manten", "PWD"=>"@kaitou2");
		$conn = sqlsrv_connect( $serverName, $connectionInfo);

		if( $conn ) {
		    // Execute query
			$stmt = "SELECT MATNR,MEINH,UMREZ,UMREN FROM ".$tablename." WHERE ".$column." LIKE '".$filter."%'";

		    if(($result = sqlsrv_query($conn,$stmt)) !== false){
		        $return_value = array();
		        while( $obj = sqlsrv_fetch_object( $result )) {
		              array_push( $return_value, $obj);
		        }
		        return $return_value;
		    } else {
		    	echo "Error getting database results.<br />";
		    }

		} else{
		    echo "Connection could not be established.<br />";
		    die( print_r( sqlsrv_errors(), true));
		}
    }

    public function getStatus($name) {
		$db=new DB\SQL('mysql:host='.$this->host.';port=3306;dbname='.$this->dbname,$this->user,$this->password);
		$result = $db->exec('SELECT status FROM status WHERE name="'.$name.'"');
		if (isset($result[0]['status']) && !empty($result[0]['status']))
			return $result[0]['status'];
		else 
			return 'Error sending request.';
    }

    public function getEntry($table, $column, $name) {
		$db=new DB\SQL('mysql:host='.$this->host.';port=3306;dbname='.$this->dbname2,$this->user,$this->password);
		$result = $db->exec('SELECT * FROM '.$table.' WHERE '.$column.' = "'.$name.'"');
		if (isset($result) && !empty($result))
			return $result;
		else 
			return 'Error sending request.';
    }

    private function has_next($array) {
	    if (is_array($array)) {
	        if (next($array) === false) {
	            return false;
	        } else {
	            return true;
	        }
	    } else {
	        return false;
	    }
	}

	public function dropTable($tablename) {
		$db=new DB\SQL('mysql:host='.$this->host.';port=3306;dbname='.$this->dbname2,$this->user,$this->password);
		if($db->exec('DELETE FROM '.$tablename)) {
				return true;
		} else {
			return false;
		}
	
	}

    public function saveBrdsCustomer($tablename, $data) {
		$db=new DB\SQL('mysql:host='.$this->host.';port=3306;dbname='.$this->dbname2,$this->user,$this->password);
		
		$query = 'INSERT INTO '.$tablename.' (code, name, address)
			VALUES ("'.$data['code'].'", "'.$data['name'].'","'.$data['address'].'")';		

		// check for duplicate entry
		$dupe_check = $db->exec('SELECT * FROM '.$tablename.' WHERE code="'.$data['code'].'"');

		if (empty($dupe_check)) {
				$result = $db->exec($query);
				return true;
		} else {
			return false;
		}
    }

    public function saveMaterialsConversion($tablename, $data, $customer) {
		$db=new DB\SQL('mysql:host='.$this->host.';port=3306;dbname='.$this->dbname2,$this->user,$this->password);
		
		// clean up table
		$db->exec("DELETE FROM ".$tablename." WHERE material_code LIKE '".$customer."%'");

		if (!empty($data)) {
			// build query
			$query = 'INSERT INTO '.$tablename.' (material_code, unit_1, num_1, den_1, unit_2, num_2, den_2, unit_3, num_3, den_3) VALUES ';
			
		    foreach ( $data as $key => $value )
		    {
		        $query .= '("'.$value['material_code'].'","'.$value['unit_1'].'","'.$value['num_1'].'","'.$value['den_1'].'"
				,"'.$value['unit_2'].'","'.$value['num_2'].'","'.$value['den_2'].'"
				,"'.$value['unit_3'].'","'.$value['num_3'].'","'.$value['den_3'].'")';

		        if(($this->has_next($data)))
		        {
		            $query .= ",";
		        }
		    }
			if (substr($query, -1, 1) == ',') 
				$query = substr($query, 0, -1);

			if($db->exec($query)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
    }

    public function saveBrdsMaterialsByCustomer($tablename, $data, $customer) {
		$db=new DB\SQL('mysql:host='.$this->host.';port=3306;dbname='.$this->dbname2,$this->user,$this->password);
		
		// clean up table
		$db->exec("DELETE FROM ".$tablename." WHERE item_code LIKE '".$customer."%'");

		if (!empty($data)) {
			// build query
			$query = 'INSERT INTO '.$tablename.' (item_code, barcode, description, pallet_ind, sled, sled_unit) VALUES ';
			


		    foreach ( $data as $key => $value )
		    {
		    	$sled_unit = ($value['sled_unit'] == ' ' ? 'd' : $value['sled_unit']); 
		        $query .= '("'.$value['item_code'].'", "'.$value['item_code'].'", "'.$value['description'].'","'.$value['pallet_ind'].'"
		        	,"'.$value['sled'].'","'.$sled_unit.'")';

		        if(($this->has_next($data)))
		        {
		            $query .= ",";
		        }
		    }
			if (substr($query, -1, 1) == ',') 
				$query = substr($query, 0, -1);

			if($db->exec($query)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
    }

    public function saveBrdsPlantlocation($tablename, $data) {
		$db=new DB\SQL('mysql:host='.$this->host.';port=3306;dbname='.$this->dbname2,$this->user,$this->password);
		
		// clean up table
		$db->exec('DELETE FROM '.$tablename.' WHERE 1');

		$query = 'INSERT INTO '.$tablename.' (plant_location, storage_location, storage_name, allowed_ip) VALUES ';
		// loop over the array
	    foreach ( $data as $key => $value )
	    {
	        $query .= '("'.$value['plant_location'].'", "'.$value['storage_location'].'","'.$value['storage_name'].'","'.$value['allowed_ip'].'")';

	        if(($this->has_next($data)))
	        {
	            $query .= ",";
	        }
	    }
		if (substr($query, -1, 1) == ',') 
			$query = substr($query, 0, -1);

		if($db->exec($query)) {
			return true;
		} else {
			return false;
		}
    }

    public function saveBrdsPackaging($tablename, $data) {
		$db=new DB\SQL('mysql:host='.$this->host.';port=3306;dbname='.$this->dbname2,$this->user,$this->password);
		
		// clean up table
		$db->exec('DELETE FROM '.$tablename.' WHERE 1');

		$query = 'INSERT INTO '.$tablename.' (pallet_type, pallet_ind) VALUES ';
		// loop over the array
	    foreach ( $data as $key => $value )
	    {
	        $query .= '("'.$value['pallet_type'].'", "'.$value['pallet_ind'].'")';

	        if(($this->has_next($data)))
	        {
	            $query .= ",";
	        }
	    }
		if (substr($query, -1, 1) == ',') 
			$query = substr($query, 0, -1);

		if($db->exec($query)) {
			return true;
		} else {
			return false;
		}
    }
}
?>