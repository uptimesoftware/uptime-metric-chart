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

include("uptimeDB.php");

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
	if ( count ($service_monitor) > 1)
	{
		$data_type_id = $service_monitor[1];
	}
	$performance_monitor = $_GET['monitor'];

}
if (isset($_GET['element'])){
	$elementList = explode(",", $_GET['element']);
}

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

$db = new uptimeDB;
if ($db->connectDB())
{
	echo "";

}
else
{
 echo "unable to connect to DB exiting";	
 exit(1);
}



	
//Enumerate metrics for specific monitor/element instance
if ($query_type == "servicemonitor") {


	//$elementList is an array where each item is elementID-erdcID 	
	if (($data_type_id == 2) ||($data_type_id == 3)) {

		// Get Element Names to use as a look-up later
		$element_id_string = "";
		$element_ids = array();
		foreach ($elementList as $element_id_and_erdc_id) {
			$ids = explode("-", $element_id_and_erdc_id);
			array_push($element_ids, $ids[0]);
		}
		$element_id_string = implode(", ", $element_ids);
		$sql_element_name = "Select entity_id, display_name from entity where entity_id in ( $element_id_string )";
		$result = $db->execQuery($sql_element_name);
		$element_names = array();
		foreach ($result as $row) {
			$element_names[$row['ENTITY_ID']] = $row['DISPLAY_NAME'];
		}


		foreach ($elementList as $element_id_and_erdc_id) {
		
			$ids = explode("-", $element_id_and_erdc_id);
			$element_id = $ids[0];
			$erdc_instance_id = $ids[1];
			
			if ($data_type_id == 2) {
				if ($db->dbType == "mysql")
				{
				$sql = "select * 
						from erdc_int_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id 
						and sampletime > date_sub(now(),interval  ". $time_frame . " second)
						order by sampletime";
				}
				elseif($db->dbType == "oracle")
				{
				$sql = "select * 
						from erdc_int_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id 
						and sampletime > sysdate - interval  '". $time_frame . "' second
						order by sampletime";
				}
				elseif($db->dbType == "mssql")
				{
					$sql = "select * 
							from erdc_int_data eid
							where eid.erdc_instance_id = $erdc_instance_id
							and eid.erdc_parameter_id = $erdc_parameter_id 
							and sampletime > DATEADD(second, -". $time_frame . ", GETDATE())
							order by sampletime";
				}
			} elseif ($data_type_id == 3) {
				if ($db->dbType == "mysql")
				{
				$sql = "select * 
						from erdc_decimal_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id
						and sampletime > date_sub(now(),interval  ". $time_frame . " second)
						order by sampletime";
				}
				elseif($db->dbType == "oracle")
				{

				$sql = "select * 
						from erdc_decimal_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id
						and sampletime >  sysdate - interval  '". $time_frame . "' second
						order by sampletime";


				}
				elseif($db->dbType == "mssql")
				{

				$sql = "select * 
						from erdc_decimal_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id
						and sampletime > DATEADD(second, -". $time_frame . ", GETDATE())
						order by sampletime";
				}
			}
		
			else {
				die('Invalid query');
				}
				
				$result = $db->execQuery($sql);
			
				$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;   
				foreach ($result as $row) {
					$sample_time = strtotime($row['SAMPLETIME'])-$offset;
					if ($sample_time >= $from_time) {
						$x = $sample_time * 1000;
						$y = (float)$row['VALUE'];
						$metric = array($x, $y);
						array_push($performanceData, $metric);
					   }
				}
				
				if ($performanceData)
				{
	
					$series_name = $element_names[$element_id];				

					array_push($oneElement, $series_name);
					array_push($oneElement, $performanceData);
					array_push($json, $oneElement);
					$oneElement = array();
					$performanceData = array();
					$i++;
				}
			
		
	}
}
	elseif ($data_type_id == 6) {

		//get a look-up of object_names based on the erdc_id to use in the loop below
		$my_exploded_string = explode("-", $elementList[0]);
		$erdc_instance_id = $my_exploded_string[1];


		$sql_object_name = "select id, object_name 
                from ranged_object ro
                where ro.instance_id = $erdc_instance_id               
                ";

		$object_name_results = $db->execQuery($sql_object_name);
		$object_names = array();
		foreach ($object_name_results as $obj )
		{
			$obj_id = $obj['ID'];
			$obj_name = $obj['OBJECT_NAME'];
			$object_names[$obj_id] = $obj_name;
		}

		//now we'll get the perf data for each object_id
		foreach($objectList as $single_ranged_object) {
			
			$element_and_ranged = explode("-",$single_ranged_object);
			$erdc_instance_id = $element_and_ranged[0];
			$ranged_object_id = $element_and_ranged[1];

			if ($db->dbType == "mysql")
			{
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
			}
			elseif ($db->dbType == "oracle")
			{

			$sql = "select value,sample_time
				from ranged_object_value rov
				join ranged_object ro on rov.ranged_object_id = ro.id
				join erdc_instance ei on ei.erdc_instance_id = ro.instance_id				
				join erdc_configuration ec on ei.configuration_id = ec.id
				join erdc_parameter ep on ep.erdc_base_id = ec.erdc_base_id
				where rov.ranged_object_id = $ranged_object_id
				and ep.name = rov.name
				and ep.erdc_parameter_id = $erdc_parameter_id
				and rov.sample_time > sysdate - interval  '". $time_frame . "' second
				order by rov.sample_time
				";

			}
			elseif ( $db->dbType == "mssql")
			{

			$sql = "select value,sample_time
				from ranged_object_value rov
				join ranged_object ro on rov.ranged_object_id = ro.id
				join erdc_instance ei on ei.erdc_instance_id = ro.instance_id				
				join erdc_configuration ec on ei.configuration_id = ec.id
				join erdc_parameter ep on ep.erdc_base_id = ec.erdc_base_id
				where rov.ranged_object_id = $ranged_object_id
				and ep.name = rov.name
				and ep.erdc_parameter_id = $erdc_parameter_id
				and rov.sample_time > DATEADD(second, -". $time_frame . ", GETDATE())
				order by rov.sample_time
				";

			}
			
				$result = $db->execQuery($sql);

				$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;   
				foreach($result as $row) {
					$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
					if ($sample_time >= $from_time) {
						$x = $sample_time * 1000;
						$y = (float)$row['VALUE'];
						$metric = array($x, $y);
						array_push($performanceData, $metric);
					}
				}
			

				

			if ($performanceData)
			{

		
				$element_name = $object_names[$ranged_object_id];


				array_push($oneElement, $element_name);
				array_push($oneElement, $performanceData);
				array_push($json, $oneElement);
				$oneElement = array();
				$performanceData = array();
				$i++;
			}
			
		
		}
	}
    // Echo results as JSON
    echo json_encode($json);
}


// Get performance metrics
elseif ($query_type == "performance") {

	// Get Element Names to use as a look-up later
	$element_id_string = implode(", ", $elementList);
	$sql_element_name = "Select entity_id, display_name from entity where entity_id in ( $element_id_string )";
	$result = $db->execQuery($sql_element_name);
	$element_names = array();
	foreach ($result as $row) {
		$element_names[$row['ENTITY_ID']] = $row['DISPLAY_NAME'];
	}



	foreach ($elementList as $element_id) {


		if ($performance_monitor == "cpu") {
			if ($db->dbType == "mysql") {
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.cpu_usr, pa.cpu_sys , pa.cpu_wio
					from performance_sample ps 
					join performance_aggregate pa on pa.sample_id = ps.id
					where ps.uptimehost_id = $element_id					
					and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)
					order by ps.sample_time";
			}
			elseif($db->dbType == "oracle") {
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.cpu_usr, pa.cpu_sys , pa.cpu_wio
					from performance_sample ps 
					join performance_aggregate pa on pa.sample_id = ps.id
					where ps.uptimehost_id = $element_id					
					and ps.sample_time > sysdate - interval  '". $time_frame . "' second
					order by ps.sample_time";

			}
			elseif($db->dbType == "mssql")
			{
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.cpu_usr, pa.cpu_sys , pa.cpu_wio
				from performance_sample ps 
				join performance_aggregate pa on pa.sample_id = ps.id
				where ps.uptimehost_id = $element_id	
				and ps.sample_time > DATEADD(second, -". $time_frame . ", GETDATE())
				order by ps.sample_time";
			}

					
		}
		elseif ($performance_monitor == "used_swap_percent" or $performance_monitor == "worst_disk_usage" or $performance_monitor == "worst_disk_busy"){
			if ($db->dbType == "mysql") {
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.$performance_monitor as value
					from performance_sample ps 
					join performance_aggregate pa on pa.sample_id = ps.id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)
					order by ps.sample_time";
			}
			elseif($db->dbType == "oracle") {
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.$performance_monitor as value
					from performance_sample ps 
					join performance_aggregate pa on pa.sample_id = ps.id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > sysdate - interval  '". $time_frame . "' second
					order by ps.sample_time";


			}
			elseif($db->dbType == "mssql")
			{
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.$performance_monitor as value
				from performance_sample ps 
				join performance_aggregate pa on pa.sample_id = ps.id
				where ps.uptimehost_id = $element_id
				and ps.sample_time > DATEADD(second, -". $time_frame . ", GETDATE())
				order by ps.sample_time";

			}
		}
		elseif ($performance_monitor == "memory") {
			if ($db->dbType == 'mysql')
			{
			$sql = "Select ps.uptimehost_id, pa.sample_id, ps.sample_time, pa.free_mem, ec.memsize
					from performance_sample ps
					join performance_aggregate pa on pa.sample_id = ps.id
					join entity_configuration ec on ec.entity_id = ps.uptimehost_id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)
					order by ps.sample_time";
			}
			elseif($db->dbType == "oracle") {
			$sql = "Select ps.uptimehost_id, pa.sample_id, ps.sample_time, pa.free_mem, ec.memsize
					from performance_sample ps
					join performance_aggregate pa on pa.sample_id = ps.id
					join entity_configuration ec on ec.entity_id = ps.uptimehost_id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > sysdate - interval  '". $time_frame . "' second
					order by ps.sample_time";

			}
			elseif($db->dbType == "mssql")
			{
			$sql = "Select ps.uptimehost_id, pa.sample_id, ps.sample_time, pa.free_mem, ec.memsize
					from performance_sample ps
					join performance_aggregate pa on pa.sample_id = ps.id
					join entity_configuration ec on ec.entity_id = ps.uptimehost_id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > DATEADD(second, -". $time_frame . ", GETDATE())
					order by ps.sample_time";
			}


		}
		else {
			die('Invalid query');
		}
     
			$result = $db->execQuery($sql);

			foreach($result as $row) {
				$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
				$x = $sample_time * 1000;
				if ($performance_monitor == "cpu") {
					$a = (float)$row['CPU_USR'];
					$b = (float)$row['CPU_SYS'];
					$c = (float)$row['CPU_WIO'];
					$y = ($a + $b + $c);
				} elseif ($performance_monitor == "memory") {
					$total_ram = (float)$row['MEMSIZE'];
					$free_ram = (float)$row['FREE_MEM'];
					$used_ram = $total_ram - $free_ram;
					$y = round(($used_ram / $total_ram * 100), 1);
				} elseif ($performance_monitor == "used_swap_percent" or $performance_monitor == "worst_disk_usage"
							or $performance_monitor == "worst_disk_busy") {
								$y = (float)$row['VALUE'];
					}
				$metric = array($x, $y);
				array_push($performanceData, $metric);
				}
			
		
		
		
		
		if ($performanceData)
		{
			$element_name = $element_names[$element_id];

			array_push($oneElement, $element_name);
			array_push($oneElement, $performanceData);
			array_push($json, $oneElement);
		}
		$oneElement = array();
		$performanceData = array();
	}
    // Echo results as JSON
    echo json_encode($json);
}


// Get network device metrics
elseif ($query_type == "network") {

	//make a look-up of port names
	$element_id = $elementList[0];
	$port_names = array();
	$sql_port_names = "Select if_name, if_index from net_device_port_config 
						where entity_id = $element_id";
	$result = $db->execQuery($sql_port_names);
	foreach ($result as $row) {
		$port_names[$row['IF_INDEX']] = $row['IF_NAME'];
	}


	$network_metrics = explode(",", $performance_monitor);
	$network_perf_data = array();

	foreach($ports as $singlePort) {

		if ($db->dbType == "mysql"){
		$sql = "select * from net_device_perf_port pp 
				join net_device_port_config pc on pp.if_index = pc.if_index 
				join net_device_perf_sample ps on ps.id = pp.sample_id
				where pc.entity_id = $elementList[0] 
				and ps.entity_id = $elementList[0] 
				and	pp.if_index = $singlePort
				and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)		  
				order by ps.sample_time";
		}
		elseif($db->dbType == "oracle"){
			$sql = "select * from net_device_perf_port pp 
				join net_device_port_config pc on pp.if_index = pc.if_index 
				join net_device_perf_sample ps on ps.id = pp.sample_id
				where pc.entity_id = $elementList[0] 
				and ps.entity_id = $elementList[0] 
				and	pp.if_index = $singlePort
				and ps.sample_time > sysdate - interval  '". $time_frame . "' second 		  
				order by ps.sample_time";

		}
		elseif($db->dbType == "mssql")
		{
			$sql = "select * from net_device_perf_port pp 
				join net_device_port_config pc on pp.if_index = pc.if_index 
				join net_device_perf_sample ps on ps.id = pp.sample_id
				where pc.entity_id = $elementList[0] 
				and ps.entity_id = $elementList[0] 
				and	pp.if_index = $singlePort
				and ps.sample_time > DATEADD(second, -". $time_frame . ", GETDATE())
				order by ps.sample_time";

		}

			$result = $db->execQuery($sql);
			
			$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;   
			foreach ($result as $row) {
				$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
				$x = $sample_time * 1000;
				foreach ($network_metrics as $network_metric)
				{
					if(preg_match("/kbps/",$network_metric)) {

						$y = (float)$row[strtoupper("$network_metric")] / 1024;
					}
					else {
						$y = (float)$row[strtoupper("$network_metric")];
					}


					$metric = array($x, $y);
					$metric_name = $network_metric . "-" . $singlePort . "-" . $elementList[0];


					if (array_key_exists($metric_name, $network_perf_data))
					{
						
						array_push($network_perf_data[$metric_name], $metric);
					}
					else
					{

						$network_perf_data[$metric_name] = array();
						array_push($network_perf_data[$metric_name], $metric);
					}
					


					
				}


			}

	}

		//re-arrange the $network_perf_data array into timeseries data
		//also put together a name for each series
		foreach ($network_perf_data as $network_metric_key => $network_metric_val)
		{
			
			$key_exploded = explode("-", $network_metric_key);
			$my_metric_name = $key_exploded[0];
			$my_port = $key_exploded[1];
			$my_entity_id = $key_exploded[2];

			//trim out kbps if it's in the metric_name
			if(preg_match("/kbps/", $my_metric_name))
			{
				$my_metric_name = substr($my_metric_name, 5);
			}

			$series_name = $port_names[$singlePort] . " - " . $my_metric_name;

			$my_temp_array = array();

			array_push($my_temp_array, $series_name);
			array_push($my_temp_array, $network_metric_val);

			array_push($json, $my_temp_array);


		}
			


    // Echo results as JSON
    echo json_encode($json);

}

    
// Unsupported request
else {
    echo "Error: Unsupported Request '$query_type'" . "</br>";
    }

?>
