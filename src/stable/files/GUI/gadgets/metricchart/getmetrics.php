<?php 

//DISCLAIMER:
//LIMITATION OF LIABILITY: uptime software does not warrant that software obtained
//from the Grid will meet your requirements or that operation of the software will
//be uninterrupted or error free. By downloading and installing software obtained
//from the Grid you assume all responsibility for selecting the appropriate
//software to achieve your intended results and for the results obtained from use
//of software downloaded from the Grid. uptime software will not be liable to you
//or any party related to you for any loss or damages resulting from any claims,
//demands or actions arising out of use of software obtained from the Grid. In no
//event shall uptime software be liable to you or any party related to you for any
//indirect, incidental, consequential, special, exemplary or punitive damages or
//lost profits even if uptime software has been advised of the possibility of such
//damages.

// Set the JSON header
header("Content-type: text/json");

if (isset($_GET['query_type'])){
	$query_type = $_GET['query_type'];
}
if (isset($_GET['uptime_offest'])){
	$offset = $_GET['uptime_offest'];
}
if (isset($_GET['time_frame'])){
	$time_frame = $_GET['time_frame'];
}
if (isset($_GET['monitor'])){
	$service_monitor = explode("-", $_GET['monitor']);
	$erdc_parameter_id = $service_monitor[0];
	if ( count ($service_monitor) >= 1)
	{
		$data_type_id = $service_monitor[1];
	}
	$performance_monitor = $_GET['monitor'];
}
if (isset($_GET['element'])){
	$elementList = explode(",", $_GET['element']);
}
//$element = explode("-", $_GET['element']);
//plui
//$element = explode("-", $elementList);
/*
$element_id = $_GET['element'];
$entity_id = $element[0];
$erdc_instance_id = $element[1];
*/
if (isset($_GET['port'])){
	$ports = explode(",", $_GET['port']);
}
if (isset($_GET['object_list'])){
	$objectList = explode(",", $_GET['object_list']);
}
$json = array();
$oneElement = array();
$performanceData = array();
//date_default_timezone_set('UTC');
$UPTIME_CONF="../../../uptime.conf";

// Gets uptime configuration for the database.
if (file_exists($UPTIME_CONF)) {
		$handle = fopen($UPTIME_CONF,"r+") or die("Can't open uptime.conf");
		if ($handle) {
				while (!feof($handle)) // Loop til end of file.
				{
						$buffer = fgets($handle, 4096); // Read a line.
						if (preg_match("/^dbType.*/", $buffer)) // Check for string.
						{
								$data = preg_split("/^dbType=/", $buffer);
								$dbType = rtrim($data[1]);
						}
						if (preg_match("/^dbHostname.*/", $buffer)) // Check for string.
						{
								$data = preg_split("/^dbHostname=/", $buffer);
								$dbHost = rtrim($data[1]);
						}
						if (preg_match("/^dbPort.*/", $buffer)) // Check for string.
						{
								$data = preg_split("/^dbPort=/", $buffer);
								$dbPort = rtrim($data[1]);
						}
						if (preg_match("/^dbName.*/", $buffer)) // Check for string.
						{
								$data = preg_split("/^dbName=/", $buffer);
								$dbName = rtrim($data[1]);
						}
						if (preg_match("/^dbUsername.*/", $buffer)) // Check for string.
						{
								$data = preg_split("/^dbUsername=/", $buffer);
								$dbUsername = rtrim($data[1]);
						}
						if (preg_match("/^dbPassword.*/", $buffer)) // Check for string.
						{
								$data = preg_split("/^dbPassword=/", $buffer);
								$dbPassword = rtrim($data[1]);
						}
				}
				fclose($handle); // Close the file.
		}
} else {
		echo "$UPTIME_CONF does not exist.  Please enter appropriate path to uptime.conf";
		exit(2);
}


if ($dbType == "mysql"){
	// mysql connection details
	$db = mysqli_connect($dbHost.":".$dbPort, $dbName, $dbUsername, $dbPassword);
	//Check connection
	if (mysqli_connect_errno()) {
		printf("Connection failed: %s</br>", mysqli_connect_error());
		exit();
		}
	}
elseif($dbType == "oracle"){
	// Oracle Connection details
	$db = odbc_connect("Driver=/usr/lib/oracle/12.1/client64/lib/libsqora.so.12.1;Server=".$dbHost.";Port=".$dbPort.";Database=".$dbName, $dbUsername, $dbPassword);
	if (!$db){
		//printf("Connection Failed: %s</br>" odbc_errormsg());
		exit();
		}
	}
elseif($dbType == "mssql"){
	// MSSQL connection parameters
	// Still to be done note if use odbc drivers can connect with odbc_connect like oracle then will use the same queries and result set so no need to make any other changes then to this section.
}
else{
	die('Bad database type');
}

// Enumerate monitors  	
if ($query_type == "monitors") {
    $sql = "select distinct erp.ERDC_PARAMETER_ID as erdc_param, eb.name, ep.short_description as short_desc, ep.parameter_type, ep.units, ep.data_type_id, description
            from erdc_retained_parameter erp
            join erdc_configuration ec on erp.configuration_id = ec.id
            join erdc_base eb on ec.erdc_base_id = eb.erdc_base_id
            join erdc_parameter ep on ep.erdc_parameter_id = erp.erdc_parameter_id
            join erdc_instance ei on ec.id = ei.configuration_id
            where ei.entity_id is not null
            order by name, description;
            ";
			
	if ($dbType == "mysql"){		
		$result = mysqli_query($db, $sql);
		// Check query
		if (!$result) {
			die('Invalid query: ' . mysqli_error());
			}
		// Get results
		while ($row = mysqli_fetch_assoc($result)) {
		    if ($row['data_type_id'] == 2 or $row['data_type_id'] == 3 or $row['data_type_id'] == 6) {				
				if ($row['units'] == "") {
					$json[$row['erdc_param'] . "-" . $row['data_type_id']] =
					$row['name'] . " - " . $row['short_desc']
					;
				} else {
					$json[$row['erdc_param'] . "-" . $row['data_type_id']] =
					$row['name'] . " - " . $row['short_desc']
					. " (" . $row['units'] . ")"
					;
				}
			}
        }
		// Close the DB connection
		$result->close();
	}
	else{
		$result=odbc_exec($db,$sql);
		// Check query
		if (!$result) {
			die('Invalid query: ' . odbc_errormsg());
			}
		// Get results
		while (odbc_fetch_row($result)) {
			//Currently only show integer, decimal and ranged-type data 
			if (odbc_result($result,"data_type_id") == 2 or odbc_result($result,"data_type_id") == 3) {
				$json[odbc_result($result,"ERDC_PARAM") . "-" . odbc_result($result,"data_type_id")] =
				odbc_result($result,"name") . " - " . odbc_result($result,"short_desc")
				//. " (" . odbc_result($result,"units") . ")"
				;
				}
			}
		// Close the DB connection
		//odbc_close($result);
	}
    // Echo results as JSON
    echo json_encode($json);
    }

//Enumerate elements and monitor instance namesand associate with a particular monitor
elseif ($query_type == "elements_for_monitor") {
    $sql = "select distinct e.entity_id, e.name, e.display_name, erp.ERDC_PARAMETER_ID as erdc_param, ei.erdc_instance_id as erdc_instance, ei.name monitor_name 
            from erdc_retained_parameter erp
            join erdc_instance ei on erp.CONFIGURATION_ID = ei.configuration_id
            join entity e on e.ENTITY_ID = ei.ENTITY_ID
            where erp.ERDC_PARAMETER_ID = $erdc_parameter_id;
            ";
	if ($dbType == "mysql"){		
		$result = mysqli_query($db, $sql);
		// Check query
		if (!$result) {
			die('Invalid query: ' . mysqli_error());
			}
		// Get results
		while ($row = mysqli_fetch_assoc($result)) {
			$json[$row['entity_id'] . "-" . $row['erdc_instance']]
				= $row['display_name'] . " - " . $row['monitor_name'];
			}
		
		// Close the DB connection
		$result->close();
	}
	else{
    	$result=odbc_exec($db,$sql);
		// Check query
		if (!$result) {
			die('Invalid query: ' . odbc_errormsg());
		}
		// Get results
		
    	while (odbc_fetch_row($result)) {
			$json[odbc_result($result,"entity_id") . "-" . odbc_result($result,"erdc_instance")] 
				= odbc_result($result,"display_name") . " - " . odbc_result($result,"monitor_name");
			}
	}
	
    // Echo results as JSON
    echo json_encode($json);
    }
	
elseif ($query_type == "ranged_objects") {
	
	$i = 0;
	foreach ($elementList as $element_id_and_erdc_id) {
		$ids = explode("-", $element_id_and_erdc_id);
		$element_id = $ids[0];
		$erdc_instance_id = $ids[1];
	

		$sql = "select * 
                from ranged_object ro
                where ro.instance_id = $erdc_instance_id               
                ";

		if ($dbType == "mysql"){		
			$result = mysqli_query($db, $sql);
			// Check query
			if (!$result) {
				die('Invalid query: ' . mysqli_error());
				}
			// Get results
			while ($row = mysqli_fetch_assoc($result)) {
				$json[$row['instance_id']. "-" . $row['id']]
					= $row['object_name'];
				}
			
			// Close the DB connection
			$result->close();
		}
		else{
		}
	}
	// Echo results as JSON
    echo json_encode($json);
				
}
	
	
//Enumerate metrics for specific monitor/element instance
elseif ($query_type == "servicemonitor") {
    
    //Test of variables
    //echo $erdc_parameter_id . "\n";
    //echo $data_type_id  . "\n";
    //echo $entity_id  . "\n";
    //echo $erdc_instance_id . "\n";

	//$elementList is an array where each item is elementID-erdcID 	
	$i = 0;
	if (($data_type_id == 2) ||($data_type_id == 3)) {
		foreach ($elementList as $element_id_and_erdc_id) {
		
			$ids = explode("-", $element_id_and_erdc_id);
			$element_id = $ids[0];
			$erdc_instance_id = $ids[1];
			
			if ($data_type_id == 2) {
				$sql = "select * 
						from erdc_int_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id 
						and sampletime > date_sub(now(),interval  ". $time_frame . " second)
						order by sampletime";
			} elseif ($data_type_id == 3) {
				$sql = "select * 
						from erdc_decimal_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id
						and sampletime > date_sub(now(),interval  ". $time_frame . " second)
						order by sampletime";
			}
		
			else {
				die('Invalid query');
				}
				
			if ($dbType == "mysql"){
				$result = mysqli_query($db, $sql);
				// Check query
				if (!$result) {
					die('Invalid query: ' . mysqli_error());
					}
					
				// Get results
				$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;   
				while ($row = mysqli_fetch_assoc($result)) {
					$sample_time = strtotime($row['sampletime'])-$offset;
					if ($sample_time >= $from_time) {
						$x = $sample_time * 1000;
						$y = (float)$row['value'];
						$metric = array($x, $y);
						array_push($performanceData, $metric);
					   }
					}
				
				
				// Get Element Name
				$sql_element_name = "Select display_name from entity where entity_id = $element_id";
				//echo "Select display_name from entity where entity_id = $element_id\n";
				$result = mysqli_query($db, $sql_element_name);
				if (!$result) {
					die('Invalid query: ' . mysqli_error());
				}
				$row = mysqli_fetch_assoc($result);
				$element_name = $row['display_name'];	
				
				
				// For ranged data, use the object name & element name in the series legend
				if ($data_type_id == 6) {
					$sql_element_name = "select object_name from ranged_object ro where ro.id = $element_id";
					//echo "Select display_name from entity where entity_id = $element_id\n";
					$result = mysqli_query($db, $sql_element_name);
					if (!$result) {
						die('Invalid query: ' . mysqli_error());
					}
					$row = mysqli_fetch_assoc($result);
					$element_name = $row['display_name'];
				}
				
				// Close the DB connection
				$result->close();
			}else{
				$result=odbc_exec($db,$sql);
				// Check query
				if (!$result) {
					die('Invalid query: ' . odbc_errormsg());
					}
				
				// Get results
				$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;   
				while (odbc_fetch_row($result)) {
					$sample_time = strtotime(odbc_result($result,"sampletime"))-$offset;
					if ($sample_time >= $from_time) {
						$x = $sample_time * 1000;
						$y = (float)odbc_result($result,"value");
						$metric = array($x, $y);
						array_push($json, $metric);
					   }
					}
			}
			array_push($oneElement, $element_name);
			array_push($oneElement, $performanceData);
			array_push($json, $oneElement);
			$oneElement = array();
			$performanceData = array();
			$i++;
			
		}
	} elseif ($data_type_id == 6) {
		
		foreach($objectList as $single_ranged_object) {
			
			$element_and_ranged = explode("-",$single_ranged_object);
			$erdc_instance_id = $element_and_ranged[0];
			$ranged_object_id = $element_and_ranged[1];

			$sql = "select value,sample_time
				from ranged_object_value rov
				join ranged_object ro on rov.ranged_object_id = ro.id
				join erdc_instance ei on ei.erdc_instance_id = ro.instance_id				
				join erdc_configuration ec on ei.configuration_id = ec.id
				join erdc_parameter ep on ep.erdc_base_id = ec.erdc_base_id
				where rov.ranged_object_id = $ranged_object_id
				and ep.name = rov.name
				and ep.erdc_parameter_id = $erdc_parameter_id
				and rov.sample_time > date_sub(now(),interval  ". $time_frame . " second)
				order by rov.sample_time
				";
				//echo $sql."\n";
			if ($dbType == "mysql"){
				$result = mysqli_query($db, $sql);
				// Check query
				if (!$result) {
					die('Invalid query: ' . mysqli_error());
					}
					
				// Get results
				$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;   
				while ($row = mysqli_fetch_assoc($result)) {
					$sample_time = strtotime($row['sample_time'])-$offset;
					if ($sample_time >= $from_time) {
						$x = $sample_time * 1000;
						$y = (float)$row['value'];
						$metric = array($x, $y);
						array_push($performanceData, $metric);
					   }
					}
			
				// Get Element Name
				$sql_element_name = "select display_name from entity e 
										join erdc_instance ei on ei.entity_id = e.entity_id
										where erdc_instance_id = $erdc_instance_id";
				
				$result = mysqli_query($db, $sql_element_name);
				if (!$result) {
					die('Invalid query: ' . mysqli_error());
				}
				$row = mysqli_fetch_assoc($result);
				$element_name = $row['display_name'];
				
				// For ranged data, use the object name & element name in the series legend
				$sql_object_name = "select object_name from ranged_object ro where ro.id = $ranged_object_id";

				$result = mysqli_query($db, $sql_object_name);
				if (!$result) {
					die('Invalid query: ' . mysqli_error());
				}
				$row = mysqli_fetch_assoc($result);
				$element_name = $row['object_name'] . " - " . $element_name;
				
				// Close the DB connection
				$result->close();
			}else{	
			}
			
			array_push($oneElement, $element_name);
			array_push($oneElement, $performanceData);
			array_push($json, $oneElement);
			$oneElement = array();
			$performanceData = array();
			$i++;
			
		}
	}
    // Echo results as JSON
    echo json_encode($json);
    }

// Enumerate elements with performance counters   
elseif ($query_type == "elements_for_performance") {
    $sql = "select e.entity_id, e.display_name
            from entity e
            join erdc_base eb on eb.erdc_base_id = e.defining_erdc_base_id
            where e.entity_type_id not in (2, 3, 4, 5)
            and e.entity_subtype_id in (1,21, 12)
            and eb.name != 'MonitorDummyVmware'
            and e.monitored = 1
            order by display_name;
            ";
			
	if ($dbType == "mysql"){	
		$result = mysqli_query($db, $sql);
		// Check query
		if (!$result) {
			die('Invalid query: ' . mysqli_error());
		}
		// Get results
		while ($row = mysqli_fetch_assoc($result)) {
			$json[$row['display_name']] = $row['entity_id'];
		}
		// Close the DB connection
		$result->close();
	}else{
		$result=odbc_exec($db,$sql);
		// Check query
		if (!$result) {
			die('Invalid query: ' . odbc_errormsg());
		}
		// Get results
		while (odbc_fetch_row($result)) {
			$json[odbc_result($result,"display_name")] = odbc_result($result,"entity_id");
		}
    }
	// Echo results as JSON
    echo json_encode($json);
    }

// Get performance metrics
elseif ($query_type == "performance") {

	foreach ($elementList as $element_id) {
		if ($performance_monitor == "cpu") {
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.cpu_usr, pa.cpu_sys , pa.cpu_wio
					from performance_sample ps 
					join performance_aggregate pa on pa.sample_id = ps.id
					where ps.uptimehost_id = $element_id					
					and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)
					order by ps.sample_time";
					
		} elseif ($performance_monitor == "used_swap_percent" or $performance_monitor == "worst_disk_usage"
				  or $performance_monitor == "worst_disk_busy") {
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.$performance_monitor as value
					from performance_sample ps 
					join performance_aggregate pa on pa.sample_id = ps.id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)
					order by ps.sample_time";
		} elseif ($performance_monitor == "memory") {
			$sql = "Select ps.uptimehost_id, pa.sample_id, ps.sample_time, pa.free_mem, ec.memsize
					from performance_sample ps
					join performance_aggregate pa on pa.sample_id = ps.id
					join entity_configuration ec on ec.entity_id = ps.uptimehost_id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)
					order by ps.sample_time";
		} else {
			die('Invalid query');
			}
     
		if ($dbType == "mysql"){
			$result = mysqli_query($db, $sql);
			// Check query
			if (!$result) {
				die('Invalid query: ' . mysqli_error());
				}
			
			
			// Get results 
			while ($row = mysqli_fetch_assoc($result)) {
				$sample_time = strtotime($row['sample_time'])-$offset;
				$x = $sample_time * 1000;
				if ($performance_monitor == "cpu") {
					$a = (float)$row['cpu_usr'];
					$b = (float)$row['cpu_sys'];
					$c = (float)$row['cpu_wio'];
					$y = ($a + $b + $c);
				} elseif ($performance_monitor == "memory") {
					$total_ram = (float)$row['memsize'];
					$free_ram = (float)$row['free_mem'];
					$used_ram = $total_ram - $free_ram;
					$y = round(($used_ram / $total_ram * 100), 1);
				} elseif ($performance_monitor == "used_swap_percent" or $performance_monitor == "worst_disk_usage"
							or $performance_monitor == "worst_disk_busy") {
					$y = (float)$row["$value"];
					}
				$metric = array($x, $y);
				array_push($performanceData, $metric);
				}
			
			// Get Element Name
			$sql_element_name = "Select display_name from entity where entity_id = $element_id";
			$result = mysqli_query($db, $sql_element_name);
			if (!$result) {
				die('Invalid query: ' . mysqli_error());
			}
			$row = mysqli_fetch_assoc($result);
			$element_name = $row['display_name'];			
			
			// Close the DB connection
			$result->close();
		}else{
			$result=odbc_exec($db,$sql);
			// Check query
			if (!$result) {
				die('Invalid query: ' . odbc_errormsg());
				}
			
			$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;
			
			// Get results 
			while (odbc_fetch_row($result)) {
				$sample_time = strtotime(odbc_result($result,"sample_time"))-$offset;
				if ($sample_time >= $from_time) {
					$x = $sample_time * 1000;
					if ($performance_monitor == "cpu") {
						$a = (float)odbc_result($result,"cpu_usr");
						$b = (float)odbc_result($result,"cpu_sys");
						$c = (float)odbc_result($result,"cpu_wio");
						$y = ($a + $b + $c);
					} elseif ($performance_monitor == "memory") {
						$total_ram = (float)odbc_result($result,"memsize");
						$free_ram = (float)odbc_result($result,"free_mem");
						$used_ram = $total_ram - $free_ram;
						$y = round(($used_ram / $total_ram * 100), 1);
					} elseif ($performance_monitor == "used_swap_percent" or $performance_monitor == "worst_disk_usage"
								or $performance_monitor == "worst_disk_busy") {
						$y = (float)odbc_result($result,"value");
						}
					$metric = array($x, $y);
					array_push($performanceData, $metric);
					}
				}
		}
		array_push($oneElement, $element_name);
		array_push($oneElement, $performanceData);
		//print_r($performanceData);
		array_push($json, $oneElement);
		$oneElement = array();
		$performanceData = array();
	}
    // Echo results as JSON
    echo json_encode($json);
    }
elseif ($query_type == "listNetworkDevice") {
	$sql = "select e.entity_id, e.display_name from entity e 
			join entity_subtype es on es.entity_subtype_id = e.entity_subtype_id
			where es.name = 'Network Device' 
			order by es.name";
			
			
	if ($dbType == "mysql"){		
		$result = mysqli_query($db, $sql);
		// Check query
		if (!$result) {
			die('Invalid query: ' . mysqli_error());
			}
		// Get results
		while ($row = mysqli_fetch_assoc($result)) {
			$json[$row['entity_id']]
				= $row['display_name'];
			}
		
		// Close the DB connection
		$result->close();
	}
	else{
		//plui need to update
	/*
    	$result=odbc_exec($db,$sql);
		// Check query
		if (!$result) {
			die('Invalid query: ' . odbc_errormsg());
		}
		// Get results
		
    	while (odbc_fetch_row($result)) {
			$json[odbc_result($result,"entity_id") . "-" . odbc_result($result,"erdc_instance")] 
				= odbc_result($result,"display_name") . " - " . odbc_result($result,"monitor_name");
			}
			*/
	}
	
    // Echo results as JSON
    echo json_encode($json);
}

elseif ($query_type == "devicePort") {
	
	// Only supports 1 network device for now
	$sql = "select if_index, if_name 
			from net_device_port_config pc 
			where pc.entity_id = $elementList[0]";
			
			
	if ($dbType == "mysql"){		
		$result = mysqli_query($db, $sql);
		// Check query
		if (!$result) {
			die('Invalid query: ' . mysqli_error());
			}
		// Get results
		while ($row = mysqli_fetch_assoc($result)) {
			$json[$row['if_index']]
				= $row['if_name'];
			}
		
		// Close the DB connection
		$result->close();
	}
	else{
		//plui need to update
	/*
    	$result=odbc_exec($db,$sql);
		// Check query
		if (!$result) {
			die('Invalid query: ' . odbc_errormsg());
		}
		// Get results
		
    	while (odbc_fetch_row($result)) {
			$json[odbc_result($result,"entity_id") . "-" . odbc_result($result,"erdc_instance")] 
				= odbc_result($result,"display_name") . " - " . odbc_result($result,"monitor_name");
			}
			*/
	}
	
    // Echo results as JSON
    echo json_encode($json);
}

// Get network device metrics
elseif ($query_type == "network") {
	$i = 0;
	foreach($ports as $singlePort) {
		$sql = "select * from net_device_perf_port pp 
				join net_device_port_config pc on pp.if_index = pc.if_index 
				join net_device_perf_sample ps on ps.id = pp.sample_id
				where pc.entity_id = $elementList[0] 
				and	pp.if_index = $singlePort
				and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)		  
				order by ps.sample_time";

		if ($dbType == "mysql"){		
			$result = mysqli_query($db, $sql);
			// Check query
			if (!$result) {
				die('Invalid query: ' . mysqli_error());
				}
			// Get results
			$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;   
			while ($row = mysqli_fetch_assoc($result)) {
				$sample_time = strtotime($row['sample_time'])-$offset;
				$x = $sample_time * 1000;
				if(preg_match("/kbps/",$performance_monitor)) {
					$y = (float)$row["$performance_monitor"] / 1024;
				}
				else {
					$y = (float)$row["$performance_monitor"];
				}
				$metric = array($x, $y);
				array_push($performanceData, $metric);
			}
			
			// Get Port Name
			$sql_port_name = "Select if_name from net_device_port_config 
								where entity_id = $elementList[0] 
								and if_index = $singlePort";
			//echo "Select display_name from entity where entity_id = $element_id\n";
			$result = mysqli_query($db, $sql_port_name);
			if (!$result) {
				die('Invalid query: ' . mysqli_error());
			}
			$row = mysqli_fetch_assoc($result);
			$port_name = $row['if_name'];
			
			
			// Close the DB connection
			$result->close();
		}
		else{
			//plui need to update
		/*
			$result=odbc_exec($db,$sql);
			// Check query
			if (!$result) {
				die('Invalid query: ' . odbc_errormsg());
			}
			// Get results
			
			while (odbc_fetch_row($result)) {
				$json[odbc_result($result,"entity_id") . "-" . odbc_result($result,"erdc_instance")] 
					= odbc_result($result,"display_name") . " - " . odbc_result($result,"monitor_name");
				}
				*/
		}
		array_push($oneElement, $port_name);
		array_push($oneElement, $performanceData);
		array_push($json, $oneElement);
		$oneElement = array();
		$performanceData = array();
		$i++;
	
	}
    // Echo results as JSON
    echo json_encode($json);
	
}

    
// Unsupported request
else {
    echo "Error: Unsupported Request '$query_type'" . "</br>";
    echo "Acceptable types are 'elements', 'monitors', and 'metrics'" . "</br>";
    }

?>
