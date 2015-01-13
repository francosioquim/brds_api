<?php

define("API_KEY", "reynold");

$f3=require('lib/base.php');


$f3->set('DEBUG',1);

if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');

$f3->config('config.ini');

$f3->route('GET /',
	function($f3) {
		$classes=array(
			'Base'=>
				array(
					'hash',
					'json',
					'session'
				),
			'Cache'=>
				array(
					'apc',
					'memcache',
					'wincache',
					'xcache'
				),
			'DB\SQL'=>
				array(
					'pdo',
					'pdo_dblib',
					'pdo_mssql',
					'pdo_mysql',
					'pdo_odbc',
					'pdo_pgsql',
					'pdo_sqlite',
					'pdo_sqlsrv'
				),
			'DB\Jig'=>
				array('json'),
			'DB\Mongo'=>
				array(
					'json',
					'mongo'
				),
			'Auth'=>
				array('ldap','pdo'),
			'Bcrypt'=>
				array(
					'mcrypt',
					'openssl'
				),
			'Image'=>
				array('gd'),
			'Lexicon'=>
				array('iconv'),
			'SMTP'=>
				array('openssl'),
			'Syncdb' => array(),
			'Web'=>
				array('curl','openssl','simplexml'),
			'Web\Geo'=>
				array('geoip','json'),
			'Web\OpenID'=>
				array('json','simplexml'),
			'Web\Pingback'=>
				array('dom','xmlrpc')
		);
		$f3->set('classes',$classes);
		$f3->set('content','welcome.htm');


		


		echo View::instance()->render('layout.htm');
	}
);

$f3->route('GET /userref',
	function($f3) {
		$f3->set('content','userref.htm');
		echo View::instance()->render('layout.htm');
	}
);


// sync customers by customer number - ok
// sync materials by customer number
// sync materials by material number
// sync plant location - ok
// sync packaging materials - ok

/**
*	Synchronize Customer by Customer code
*	@return 
*	@param 
*
*	@code 	customer code
* 	@key 	api key
*
**/

$f3->route('GET /customer/@code/@key',
	function($f3) {
		if ($f3->get('PARAMS.key') == API_KEY) {
			if ($f3->get('PARAMS.code')) {
				// retrieve database entry
				$sync_db = new Syncdb;
				$table_info = $sync_db->getTableEntry("qas.KNA1", "KUNNR", $f3->get('PARAMS.code'));
				// save to brds db
				if (!empty($table_info)) {
					$to_save = array('code' => $table_info->KUNNR,'name' => $table_info->NAME1,'address' => $table_info->ORT01);
					if ($sync_db->saveBrdsCustomer('mst_customer',$to_save)) 
						echo "Success";
					else
						echo "Duplicate Entry";
				}
			}
		} else {
			echo 'Invalid API key';
		}

	}
);

/**
*	Synchronize Materials by Customer Code
*	@return 
*	@param 
*
*	@code 	customer code
* 	@key 	api key
*
*	[IPRKZ sled unit]
*	"D" = ""‏
*	"W" = "1"‏
*	"M" = "2"‏
*	"Y" = "3"‏
*
*	[Conversion table MARM]
*	UMREN = numerator
*	UMREZ = denominator
*	MEINH = unit
*
*  	note: first 8-digit of the material is the customer code, test data - X7CMARIN
*  	MAKT - MAKTX, MARAV - material - pallet relationship
*
*	note: for synchronizing via materials code, just replace the code param
**/


$f3->route('GET /materials_customer/@code/@key',
	function($f3) {
		if ($f3->get('PARAMS.key') == API_KEY) {
			if ($f3->get('PARAMS.code')) {
				// retrieve database entry
				$sync_db = new Syncdb;
				$materials = array();

				// get materials conversion table
				$conversion_table = $sync_db->getMaterialsConversion("qas.MARM", "qas.MARM.MATNR", $f3->get('PARAMS.code')); 
				
				// sync materials conversion table
				$conversion = array();
				foreach ($conversion_table as $key => $conversion_info) {
					if (count($conversion) > 0 && $key > 0) {
						if ($conversion_table[$key]->MATNR == $conversion_table[$key-1]->MATNR) {
							// place on the previous conversion value
							$target_key = sizeof($conversion)-1;
							// check if it can be pushd to 2
							if ($conversion[$target_key]['unit_2'] == '') {
								$conversion[$target_key]['unit_2'] = $conversion_info->MEINH;
								$conversion[$target_key]['num_2'] = $conversion_info->UMREN;
								$conversion[$target_key]['den_2'] = $conversion_info->MEINH;								
							} else if ($conversion[$target_key]['unit_3'] == '') {
							// check if it can be pushd to 3
								$conversion[$target_key]['unit_3'] = $conversion_info->MEINH;
								$conversion[$target_key]['num_3'] = $conversion_info->UMREN;
								$conversion[$target_key]['den_3'] = $conversion_info->MEINH;								
							}
						} else {
							$conversion[] = array(
								'material_code'=>$conversion_info->MATNR,								
								'unit_1'=>$conversion_info->MEINH,
								'num_1'=>$conversion_info->UMREN,
								'den_1'=>$conversion_info->UMREZ,
								'unit_2'=>'','num_2'=>'','den_2'=>'',
								'unit_3'=>'','num_3'=>'','den_3'=>'');
						}
					} else {
						$conversion[] = array(
							'material_code'=>$conversion_info->MATNR,
							'unit_1'=>$conversion_info->MEINH,
							'num_1'=>$conversion_info->UMREN,
							'den_1'=>$conversion_info->UMREZ,
							'unit_2'=>'','num_2'=>'','den_2'=>'',
							'unit_3'=>'','num_3'=>'','den_3'=>'');
					}
				}

				$sync_db->saveMaterialsConversion('mst_material_conversion',array_unique($conversion, SORT_REGULAR),$f3->get('PARAMS.code'));

				// get materials
				$table_infos = $sync_db->getMaterials("qas.MARA", "qas.MARA.MATNR", $f3->get('PARAMS.code'));
				
					foreach ($table_infos as $table_info_key => $table_info) {
						//$result = $sync_db->getEntry('mst_allowed_ip', 'plant_location', $plant_value );
							$material_info = array('item_code'=>$table_info->MATNR,
													'description'=>$table_info->MAKTX,
													'pallet_ind'=>$table_info->MAGRV,
													'sled'=>$table_info->MHDHB,
													'sled_unit'=>$table_info->IPRKZ );
							$materials[] = $material_info;
					}
							
					if (!$sync_db->saveBrdsMaterialsByCustomer('mst_material',array_unique($materials, SORT_REGULAR),$f3->get('PARAMS.code'))) {
						echo 'Failed to synchronize with BRDS.';
					} else {
						echo 'Succes';
					}

			}
		} else {
			echo 'Invalid API key';
		}

	}
);

/**
*	Synchronize Plant Location
*	@return 
*	@param 
*   
*	note: only for BBL1, BBL2, BBL3
**/

$f3->route('GET /plant_location/@key',
	function($f3) {
		if ($f3->get('PARAMS.key') == API_KEY) {
				$plants = array('BBL1','BBL2','BBL3');
				// retrieve database entry
				$sync_db = new Syncdb;
				$plant_locations = array();
				// drop table before syncing
				$sync_db->dropTable('mst_plant_location');
				foreach ($plants as $plant_key => $plant_value) {
					$table_infos = $sync_db->getTableEntries("qas.T001L", "WERKS", $plant_value);

					foreach ($table_infos as $table_info_key => $table_info) {
						$result = $sync_db->getEntry('mst_allowed_ip', 'plant_location', $plant_value );

						if (isset($result[0]['ip_address']) && !empty($result[0]['ip_address'])){
							$allowed_ip = $result[0]['ip_address'];
							$plant_location = array('plant_location'=>$table_info->WERKS,
													'storage_location'=>$table_info->LGORT,
													'storage_name'=>$table_info->LGOBE,
													'allowed_ip'=>$allowed_ip);
							$plant_locations[] = $plant_location;

						} else {
							echo 'Plant IP address is not available on the system.';
						}
					}
				}
				if (!$sync_db->saveBrdsPlantlocation('mst_plant_location',array_unique($plant_locations, SORT_REGULAR))) {
					echo 'Failed to synchronize with BRDS.';
				} else {
					echo 'Succes';
				}
		} else {
			echo 'Invalid API key';
		}

	}
);

/**
*	Synchronize Packaging Materials
*	@return 
*	@param 
*
*	@code 	customer code
* 	@key 	api key
*
**/

$f3->route('GET /packaging/@key',
	function($f3) {
		if ($f3->get('PARAMS.key') == API_KEY) {
				// retrieve database entry
				$sync_db = new Syncdb;
				$packaging = array();
				$table_infos = $sync_db->getTable("qas.TERVH");
				// save to brds db
				foreach ($table_infos as $table_info_key => $table_info) {
						//$result = $sync_db->getEntry('mst_allowed_ip', 'plant_location', $plant_value );
						$packaging_info = array('pallet_type'=>$table_info->MAGRV,
												'pallet_ind'=>$table_info->TRATY);
						$packaging[] = $packaging_info;
				}
							
				if (!$sync_db->saveBrdsPackaging('mst_packaging',array_unique($packaging, SORT_REGULAR))) {
					echo 'Failed to synchronize with BRDS.';
				} else {
					echo 'Succes';
				}
		} else {
			echo 'Invalid API key';
		}

	}
);


$f3->run();