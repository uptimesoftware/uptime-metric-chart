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

$query_type = $_GET['query_type'];
$host = $_GET['uptime_host'];
$time_frame = $_GET['time_frame'];
$service_monitor = explode("-", $_GET['monitor']);
$erdc_parameter_id = $service_monitor[0];
$data_type_id = $service_monitor[1];
$performance_monitor = $_GET['monitor'];
$element = explode("-", $_GET['element']);
$element_id = $_GET['element'];
$entity_id = $element[0];
$erdc_instance_id = $element[1];
$hostname = $host . ":3308";
$dbname = "uptime";
$username = "uptime";
$pw = "uptime";
$db = mysqli_connect($hostname, $dbname, $username, $pw);
$json = array();

// Check connection
if (mysqli_connect_errno()) {
    printf("Connection failed: %s</br>", mysqli_connect_error());
    exit();
    }

// Enumerate elements/entities
if ($query_type == "elements") {
    $sql = "SELECT * FROM entity";
    $result = mysqli_query($db, $sql);
    // Check query
    if (!$result) {
        die('Invalid query: ' . mysqli_error());
    }
    while ($row = mysqli_fetch_assoc($result)) {
        $json[$row['entity_id']] = $row['display_name'];
        } 
    // Close the DB connection
    $result->close();
    // Echo results as JSON
    echo json_encode($json);
    }

// Enumerate monitors   
elseif ($query_type == "monitors") {
    $sql = "select distinct erp.ERDC_PARAMETER_ID, eb.name, ep.short_description, ep.parameter_type, ep.units, ep.data_type_id
            from erdc_retained_parameter erp
            join erdc_configuration ec on erp.configuration_id = ec.id
            join erdc_base eb on ec.erdc_base_id = eb.erdc_base_id
            join erdc_parameter ep on ep.erdc_parameter_id = erp.erdc_parameter_id
            join erdc_instance ei on ec.id = ei.configuration_id
            where ei.entity_id is not null
            order by name, description;
            ";
    $result = mysqli_query($db, $sql);
    // Check query
    if (!$result) {
        die('Invalid query: ' . mysqli_error());
    }
    // Get results
    while ($row = mysqli_fetch_assoc($result)) {
        //Currently only show integer and decimal -type data
        if ($row['data_type_id'] == 2 or $row['data_type_id'] == 3) {
            $json[$row['ERDC_PARAMETER_ID'] . "-" . $row['data_type_id']] =
            $row['name'] . " - " . $row['short_description']
            //. " (" . $row['units'] . ")"
            ;
            }
        }
    
    // Close the DB connection
    $result->close();
    // Echo results as JSON
    echo json_encode($json);
    }

//Enumerate elements and monitor instance namesand associate with a particular monitor
elseif ($query_type == "elements_for_monitor") {
    $sql = "select distinct e.entity_id, e.name, e.display_name, erp.ERDC_PARAMETER_ID, ei.erdc_instance_id, ei.name monitor_instance_name
            from erdc_retained_parameter erp
            join erdc_instance ei on erp.CONFIGURATION_ID = ei.configuration_id
            join entity e on e.ENTITY_ID = ei.ENTITY_ID
            where erp.ERDC_PARAMETER_ID = $erdc_parameter_id;
            ";
    $result = mysqli_query($db, $sql);
    // Check query
    if (!$result) {
        die('Invalid query: ' . mysqli_error());
    }
    // Get results
    while ($row = mysqli_fetch_assoc($result)) {
        $json[$row['entity_id'] . "-" . $row['erdc_instance_id']]
            = $row['display_name'] . " - " . $row['monitor_instance_name'];
        }
    
    // Close the DB connection
    $result->close();
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
    
    if ($data_type_id == 2) {
        $sql = "select * 
                from erdc_int_data eid
                where eid.erdc_instance_id = $erdc_instance_id
                and eid.erdc_parameter_id = $erdc_parameter_id 
                ";
    } elseif ($data_type_id == 3) {
        $sql = "select * 
                from erdc_decimal_data eid
                where eid.erdc_instance_id = $erdc_instance_id
                and eid.erdc_parameter_id = $erdc_parameter_id
                order by erdc_int_data_id DESC
                ";
    } else {
        die('Invalid query');
        }
        
    $result = mysqli_query($db, $sql);
    // Check query
    if (!$result) {
        die('Invalid query: ' . mysqli_error());
        }
        
    // Get results
    $from_time = strtotime("-" . (string)$time_frame . " seconds");   
    while ($row = mysqli_fetch_assoc($result)) {
        $sample_time = strtotime($row['sampletime']);
        if ($sample_time >= $from_time) {
            $x = $sample_time * 1000;
            $y = (float)$row['value'];
            $metric = array($x, $y);
            array_push($json, $metric);
           }
        }
    // Close the DB connection
    $result->close();
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
    // Echo results as JSON
    echo json_encode($json);
    }

// Get performance metrics
elseif ($query_type == "performance") {
    
    if ($performance_monitor == "cpu") {
        $sql = "Select ps.uptimehost_id, ps.sample_time, pa.cpu_usr, pa.cpu_sys , pa.cpu_wio
                from performance_sample ps 
                join performance_aggregate pa on pa.sample_id = ps.id
                where ps.uptimehost_id = $element_id
                order by ps.sample_time";
    } elseif ($performance_monitor == "used_swap_percent" or $performance_monitor == "worst_disk_usage"
              or $performance_monitor == "worst_disk_busy") {
        $sql = "Select ps.uptimehost_id, ps.sample_time, pa.$performance_monitor
                from performance_sample ps 
                join performance_aggregate pa on pa.sample_id = ps.id
                where ps.uptimehost_id = $element_id
                order by ps.sample_time";
    } elseif ($performance_monitor == "memory") {
        $sql = "Select ps.uptimehost_id, pa.sample_id, ps.sample_time, pa.free_mem, ec.memsize
                from performance_sample ps
                join performance_aggregate pa on pa.sample_id = ps.id
                join entity_configuration ec on ec.entity_id = ps.uptimehost_id
                where ps.uptimehost_id = $element_id
                order by ps.sample_time";
    } else {
        die('Invalid query');
        }
        
    $result = mysqli_query($db, $sql);
    // Check query
    if (!$result) {
        die('Invalid query: ' . mysqli_error());
        }
    
    $from_time = strtotime("-" . (string)$time_frame . " seconds");
    
    // Get results 
    while ($row = mysqli_fetch_assoc($result)) {
        $sample_time = strtotime($row['sample_time']);
        if ($sample_time >= $from_time) {
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
                $y = (float)$row["$performance_monitor"];
                }
            $metric = array($x, $y);
            array_push($json, $metric);
            }
        }
    
    // Close the DB connection
    $result->close();
    // Echo results as JSON
    echo json_encode($json);
    }
    
// Unsupported request
else {
    echo "Error: Unsupported Request '$query_type'" . "</br>";
    echo "Acceptable types are 'elements', 'monitors', and 'metrics'" . "</br>";
    }

?>