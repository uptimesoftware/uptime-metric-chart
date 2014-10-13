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



require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/classLoader.inc";


session_start();

$user_name =  $_SESSION['current_user']->getName();
$user_pass = $_SESSION['current_user']->getPassword();

session_write_close();


// Set the JSON header
header("Content-type: text/json");


include("uptimeDB.php");
include("uptimeApi.php");

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

if (isset($_GET['elements'])){
    $elementList = explode(",", $_GET['elements']);
}
if (isset($_GET['groups'])){
    $groupList = explode(",", $_GET['groups']);
}

if (isset($_GET['views'])){
    $viewList = explode(",", $_GET['views']);
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





$uptime_api_username = $user_name;
$uptime_api_password = $user_pass;
$uptime_api_hostname = "localhost";     // up.time Controller hostname (usually localhost, but not always)
$uptime_api_port = 9997;
$uptime_api_version = "v1";
$uptime_api_ssl = true;



// Enumerate elements with PPGs   
if ($query_type == "elements_for_performance") {

    // Create API object
    $uptime_api = new uptimeApi($uptime_api_username, $uptime_api_password, $uptime_api_hostname, $uptime_api_port, $uptime_api_version, $uptime_api_ssl);



    $elements = $uptime_api->getElements("type=Server&isMonitored=1");

    foreach ($elements as $d) {
        if ($d['typeSubtype'] != "VcenterHostSystem")
        {
            $has_ppg = False;
            foreach($d['monitors'] as $monitor)
            {
                if($monitor['name'] == "Platform Performance Gatherer")
                {
                    $has_ppg = True;
                    break;
                }
            }
            if ($has_ppg)
            {
                $json[$d['name']] = $d['id'];
            }
        }
    }

    //sort alphabeticaly on name instead of their order on ID
    ksort($json);  

    echo json_encode($json);
}

//Groups for Performance Monitors  
elseif ($query_type == "groups_for_performance") {

    // Create API object
    $uptime_api = new uptimeApi($uptime_api_username, $uptime_api_password, $uptime_api_hostname, $uptime_api_port, $uptime_api_version, $uptime_api_ssl);


    $groups = $uptime_api->getGroups();

    foreach ($groups as $d) {
        $json[$d['name']] = $d['id'];
    }

    //sort alphabeticaly on name instead of their order on ID
    ksort($json);  

    echo json_encode($json);
}

//Views for Performance Monitors  
elseif ($query_type == "views_for_performance") {
    
    $db = setupDB();


    $sql = "select id, name from entity_view order by name asc;
            ";
            
        $result = $db->execQuery($sql);
        
        foreach ($result as $row) {
            $json[$row['NAME']] = $row['ID'];
        }
    
    //sort alphabeticaly on name instead of their order on ID
    ksort($json);    

    // Echo results as JSON
    echo json_encode($json);
}

// Enumerate monitors   
elseif ($query_type == "monitors") {

    $db = setupDB();


    $sql = "select distinct erp.ERDC_PARAMETER_ID as erdc_param, eb.name, ep.short_description as short_desc, ep.parameter_type, ep.units, ep.data_type_id, description
            from erdc_retained_parameter erp
            join erdc_configuration ec on erp.configuration_id = ec.id
            join erdc_base eb on ec.erdc_base_id = eb.erdc_base_id
            join erdc_parameter ep on ep.erdc_parameter_id = erp.erdc_parameter_id
            join erdc_instance ei on ec.id = ei.configuration_id
            where ei.entity_id is not null
            order by name, description;
            ";

    $result = $db->execQuery($sql);
    foreach ($result as $row) {

            $my_data_type_id = $row['DATA_TYPE_ID'];
            if ($my_data_type_id == 2 or $my_data_type_id == 3 ) {              
                if ($row['UNITS'] == "") {
                    $k = $row['ERDC_PARAM'] . "-" . $row['DATA_TYPE_ID'];
                    $v = $row['NAME'] . " - " . $row['SHORT_DESC'];
                    $json[$k] = $v;

                } else {
                    $k = $row['ERDC_PARAM'] . "-" . $row['DATA_TYPE_ID'] ;
                    $v = $row['NAME'] . " - " . $row['SHORT_DESC'] . " (" . $row['UNITS'] . ")";
                    $json[$k] = $v;
                }
            }

    }
    // Echo results as JSON
    echo json_encode($json);
}


//Enumerate elements and monitor instance namesand associate with a particular monitor
elseif ($query_type == "elements_for_monitor") {

    $db = setupDB();
    $sql = "select distinct e.entity_id, e.name, e.display_name, erp.ERDC_PARAMETER_ID as erdc_param, ei.erdc_instance_id as erdc_instance, ei.name monitor_name 
            from erdc_retained_parameter erp
            join erdc_instance ei on erp.CONFIGURATION_ID = ei.configuration_id
            join entity e on e.ENTITY_ID = ei.ENTITY_ID
            where erp.ERDC_PARAMETER_ID = $erdc_parameter_id;
            ";

        $result = $db->execQuery($sql);
        
        foreach ($result as $row) {
            $v = $row['ENTITY_ID'] . "-" . $row['ERDC_INSTANCE'];
            $k = $row['DISPLAY_NAME'] . " - " . $row['MONITOR_NAME'];
            $json[$k] = $v;
            }
        
    // Echo results as JSON
    echo json_encode($json);
}

elseif ($query_type == "groups_for_monitor") {

    // Create API object
    $uptime_api = new uptimeApi($uptime_api_username, $uptime_api_password, $uptime_api_hostname, $uptime_api_port, $uptime_api_version, $uptime_api_ssl);


    $groups = $uptime_api->getGroups();

    foreach ($groups as $d) {
        $json[$d['name']] = $d['id'];
    }

    //sort alphabeticaly on name instead of their order on ID
    ksort($json);  

    echo json_encode($json);
}

//Views for Performance Monitors  
elseif ($query_type == "views_for_monitor") {
    
    $db = setupDB();


    $sql = "select id, name from entity_view order by name asc;
            ";
            
        $result = $db->execQuery($sql);
        
        foreach ($result as $row) {
            $json[$row['NAME']] = $row['ID'];
        }
    
    //sort alphabeticaly on name instead of their order on ID
    ksort($json);    

    // Echo results as JSON
    echo json_encode($json);
}


elseif ($query_type == "ranged_objects") {
    
    $db = setupDB();

    $i = 0;
    foreach ($elementList as $element_id_and_erdc_id) {
        $ids = explode("-", $element_id_and_erdc_id);
        $element_id = $ids[0];
        $erdc_instance_id = $ids[1];
    

        $sql = "select * 
                from ranged_object ro
                where ro.instance_id = $erdc_instance_id               
                ";

        $result = $db->execQuery($sql);
        
        foreach ($result as $row) {
            $json[$row['INSTANCE_ID']. "-" . $row['ID']]
             = $row['OBJECT_NAME'];
        }
    }
    // Echo results as JSON
    echo json_encode($json);
                
}

elseif ($query_type == "listNetworkDevice") {

    $db = setupDB();

    $sql = "select e.entity_id, e.display_name from entity e 
            join entity_subtype es on es.entity_subtype_id = e.entity_subtype_id
            where es.name = 'Network Device' 
            order by es.name";
            
            
    $result = $db->execQuery($sql);
    foreach ($result as $row) {
        $json[$row['DISPLAY_NAME']] = $row['ENTITY_ID'];
    }
    
    
    // Echo results as JSON
    echo json_encode($json);
}

elseif ($query_type == "devicePort") {

    $db = setupDB();
    
    // Only supports 1 network device for now
    $sql = "select if_index, if_name 
            from net_device_port_config pc 
            where pc.entity_id = $elementList[0]";
            
    $result = $db->execQuery($sql);
    foreach($result as $row) {
            $json[$row['IF_NAME']]
                = $row['IF_INDEX'];
            }
    
    // Echo results as JSON
    echo json_encode($json);
}


// Unsupported request
else {
    echo "Error: Unsupported Request '$query_type'" . "</br>";
    echo "Acceptable types are 'elements', 'monitors', and 'metrics'" . "</br>";
    }

function setupDB()
{
    $db = new uptimeDB;
    if ($db->connectDB())
    {
        return $db;
    }
    else
    {
        echo "unable to connect to DB exiting";    
        exit(1);
    }

}


?>