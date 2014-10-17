<?php
/**
 * uptimeApi
 *
 * up.time Monitoring Station API access file for PHP
 *
 * @package    uptimeApi
 * @author     Joel Pereira <joel.pereira@uptimesoftware.com>
 * @copyright  2012 uptime software inc
 * @license    BSD License
 * @version    Release: 1.0
 * @link       http://support.uptimesoftware.com
 * 
 * Note: Requires CURL and JSON on the webserver/PHP.
 */
class uptimeApi {
    protected $cacheDuration = 30;  // duration of cache (in seconds)

    protected $apiSSL;
    protected $apiHostname;
    protected $apiPort;
    protected $apiUsername;
    protected $apiPassword;
    protected $apiVersion;
    
    // cache variables (so we don't have to make as many API calls
    protected $cacheGroups;
    protected $cacheGroupsLastCheck;
    protected $cacheElements;
    protected $cacheElementsLastCheck;
    protected $cacheMonitors;
    protected $cacheMonitorsLastCheck;


    public function __construct( $username, $password, $hostname = "localhost", $port = "9997", $version = "v1", $ssl = true ) {
        // initialize class with provided connection options
        // requires error checking
        
        // apiUsername
        $this->apiUsername = substr(trim($username), 0, 32);
        
        // apiPassword
        $this->apiPassword = substr($password, 0, 32);
        
        // apiHostname
        // pattern for hostname (http://stackoverflow.com/questions/1418423/the-hostname-regex)
        //$pattern = '/^(?=.{1,255}$)[0-9A-Za-z](?:(?:[0-9A-Za-z]|\b-){0,61}[0-9A-Za-z])?(?:\.[0-9A-Za-z](?:(?:[0-9A-Za-z]|\b-){0,61}[0-9A-Za-z])?)*\.?$/';
        //if ( preg_match($pattern, $hostname) ) {
            $this->apiHostname = trim($hostname);
        //}
        
        // apiPort
        $this->apiPort = intval(trim($port));
        if ($this->apiPort == 0) { $this->apiPort = 9997; } // set default, just in case
        
        // apiVersion
        $pattern = '/^[v][\d]+$/';
        if ( preg_match($pattern, trim($version)) ) {
            $this->apiVersion = trim($version);
        }
        else {
            $this->apiVersion = "v1";
        }
        
        // apiSSL
        if ($ssl) {
            $this->apiSSL = true;
        }
        elseif ( $ssl == false) {
            $this->apiSSL = false;
        }
        else {
            $this->apiSSL = false;
        }
    }

    public function setCredentials($username, $password) {
        // apiUsername
        $this->apiUsername = substr(trim($username), 0, 32);
        // apiPassword
        $this->apiPassword = substr($password, 0, 32);
    }
    
    public function getApiInfo(&$error = "") {
        // Access up.time API
        $output = $this->getJSON("");
        // Return the output
        return $output;
    }
    public function testAuth(&$error = "") {
        $rv = true;     // return value
        $apiRequest = $this->getGroups("", $error);     // "groups" seems to be the quickest API call
        if ( strlen($error) > 0 ) {
            // errors encountered!
            $rv = false;
        }
        return $rv;
    }
    public function getElements ( $filter = "", &$error = "" ) {
        // return either all of the elements, or the element provided by ID, or all elements with some filter applied
        
        $apiRequest = "/{$this->apiVersion}/elements";
        
        // Check if we have a recently cached copy first
        if (isset($this->cacheElements) && isset($this->cacheElementsLastCheck) && $this->lastCheckIsCurrent($this->cacheElementsLastCheck)) {
            $output = $this->cacheElements;
        }
        else {
            // Access up.time API
            $output = $this->getJSON($apiRequest, $error, false);
            // Save to cache
            $this->cacheElements = $output;
            $this->cacheElementsLastCheck = time();
        }
        // Apply filter
        $output = $this->runFilter($output, $filter);
        // Return the output
        return $output;
    }
    public function getMonitors ( $filter = "", &$error = "" ) {
        // return either all of the monitors, or the monitor provided by the ID, or all monitors with some filter applied
        
        $apiRequest = "/{$this->apiVersion}/monitors";
        
        // Check if we have a recently cached copy first
        if (isset($this->cacheMonitors) && isset($this->cacheMonitorsLastCheck) && $this->lastCheckIsCurrent($this->cacheMonitorsLastCheck)) {
            $output = $this->cacheMonitors;
        }
        else {
            // Access up.time API
            $output = $this->getJSON($apiRequest, $error, false);
            // Save to cache
            $this->cacheMonitors = $output;
            $this->cacheMonitorsLastCheck = time();
        }
        // Apply filter
        $output = $this->runFilter($output, $filter);
        // Return the output
        return $output;
    }
    public function getGroups ( $filter = "", &$error = "" ) {
        // return either all groups, or the group provided by the ID, of all groups with some filter applied

        $apiRequest = "/{$this->apiVersion}/groups";
        
        // Check if we have a recently cached copy first
        if (isset($this->cacheGroups) && isset($this->cacheGroupsLastCheck) && $this->lastCheckIsCurrent($this->cacheGroupsLastCheck)) {
            $output = $this->cacheGroups;
        }
        else {
            // Access up.time API
            $output = $this->getJSON($apiRequest, $error, false);
            // Save to cache
            $this->cacheGroups = $output;
            $this->cacheGroupsLastCheck = time();
        }
        // Apply filter
        $output = $this->runFilter($output, $filter);
        // Return the output
        return $output;
    }

    public function getElementStatus ( $id, &$error = "" ) {
        // return status for either all of the elements, or the element provided by ID, or all elements with some filter applied
        $cmd = "/{$this->apiVersion}/elements";
        $output = array();
        // verify the $id is a number
        $id = intval(trim($id));
        if ($id > 0) {
            $apiRequest = "{$cmd}/{$id}/status";
            // Access up.time API
            $output = $this->getJSON($apiRequest, $error, false);
        }
        else {
            // $id is not a valid number; so return empty array(?)
        }
        // Return the output
        return $output;
    }
    public function getMonitorStatus ( $id, &$error = "" ) {
        // return status for either all of the monitors, or the monitor provided by the ID, or all monitors with some filter applied
        $cmd = "/{$this->apiVersion}/monitors";
        $output = array();
        // verify the $id is a number
        $id = intval(trim($id));
        if ($id > 0) {
            $apiRequest = "{$cmd}/{$id}/status";
            // Access up.time API
            $output = $this->getJSON($apiRequest, $error, false);
        }
        else {
            // $id is not a valid number; so return empty array(?)
        }
        // Return the output
        return $output;
    }
    public function getGroupStatus ( $id, &$error = "" ) {
        // return status for either all of the groups, or the group provided by the ID, or all groups with some filter applied
        $cmd = "/{$this->apiVersion}/groups";
        $output = array();
        // verify the $id is a number
        $id = intval(trim($id));
        if ($id > 0) {
            $apiRequest = "{$cmd}/{$id}/status";
            // Access up.time API
            $output = $this->getJSON($apiRequest, $error, false);
        }
        else {
            // $id is not a valid number; so return empty array(?)
        }
        // Return the output
        return $output;
    }
    public function getAllMonitorStatus ( $filter = "", &$error = "" ) {
        // return status for either all of the monitors, or the monitor provided by the ID, or all monitors with some filter applied
        $output = array();
        
        // get all the elements
        $allElements = $this->getElements();
        // now let's get the element status for each one and add it to the elements array
        foreach ($allElements as &$element) {
            // get element status for each element (better than getting status for each monitor!)
            $elementStatus = $this->getElementStatus($element['id']);
            
            // get the list of monitors for each element
            $monitorsStatus = $elementStatus['monitorStatus'];
            // for each monitor, add it to the array
            if (count($monitorsStatus) > 0) {
                foreach ($monitorsStatus as $monitor) {
                    array_push($output, $monitor);
                }
            }
        }
        
        // apply filter before sorting
        $output = $this->runFilter($output, $filter);
        
        // calculate and add the length of time the monitors have been in their current state (lastTransitionTime)
        $this->addLengthOfOutages($output);
        // sort monitors appropriately
        $output = $this->sortMonitors($output);
        // Return the output
        return $output;
    }

    // Monitor Helper Functions
    // Add last transition times
    protected function addLengthOfOutages(&$monitors) {
        if (count($monitors) > 0) {
            $now = new DateTime();
            foreach ($monitors as &$monitor) {
                $status_since_ts = strtotime($monitor['lastTransitionTime']);   // unix timestamp
                
                $status_since = date_create($monitor['lastTransitionTime']);    // proper date variable
                
                $difference = date_diff($status_since, $now);
                // let's put the difference into the array object
                $monitor['statusDateDifference_y'] = $difference->y;    // years
                $monitor['statusDateDifference_m'] = $difference->m;    // months
                $monitor['statusDateDifference_d'] = $difference->d;    // days
                $monitor['statusDateDifference_h'] = $difference->h;    // hours
                $monitor['statusDateDifference_i'] = $difference->i;    // minutes
                $monitor['statusDateDifference_s'] = $difference->s;    // seconds
                
                $difference = 0;
                $difference = time() - $status_since_ts;
                $monitor['statusDifferenceInSeconds'] = $difference;
            }
        }
    }
    // Function to sort the monitors list
    function sortMonitors($monitors) {
        $new_monitors_list = array();
        // First sort by status, then by length of time
        // Sort order: crit, warn, ok, maint, unknown
        $crit = array();
        $warn = array();
        $ok = array();
        $maint = array();
        $unknown = array();
        foreach ($monitors as $monitor) {
            $status = $monitor['status'];
            // strip out the monitors that are not monitored and are hidden
            if ($monitor['isMonitored'] && ! $monitor['isHidden']) {
                switch ( strtolower(trim($status)) ) {
                    case 'ok':
                        array_push($ok, $monitor);
                    break;
                    case 'crit':
                        array_push($crit, $monitor);
                    break;
                    case 'warn':
                        array_push($warn, $monitor);
                    break;
                    case 'maint':
                        array_push($maint, $monitor);
                    break;
                    default:
                        array_push($unknown, $monitor);
                    break;
                }
            }
        }
        
        // now let's sort each status array by the newest outage (shortest "statusDifferenceInSeconds") to the top
        $crit = $this->sortByOutageLengthInSeconds($crit);
        $warn = $this->sortByOutageLengthInSeconds($warn);
        $ok = $this->sortByOutageLengthInSeconds($ok);
        $maint = $this->sortByOutageLengthInSeconds($maint);
        $unknown = $this->sortByOutageLengthInSeconds($unknown);
        // finally, let's build the new array with the proper sorting
        $this->addToArray($new_monitors_list, $crit);
        $this->addToArray($new_monitors_list, $warn);
        $this->addToArray($new_monitors_list, $ok);
        $this->addToArray($new_monitors_list, $maint);
        $this->addToArray($new_monitors_list, $unknown);
        // return the new sorted monitors list
        return $new_monitors_list;
    }
    function sortByOutageLengthInSeconds($arrMonitors) {
        // don't bother sorting if there's none or only one
        if (count($arrMonitors) > 1) {
            $oriArrMonitors = $arrMonitors; // copy of the original array
            $newArrMonitors = array();  // new array of monitors
            $arrOutageSeconds = array();    // hold the IDs and the length of the outages ("statusDifferenceInSeconds")
            
            //for ($i = 0; $i < count($arrMonitors); $i++) {
            foreach ($arrMonitors as $monitor) {
                //$monitor = $arrMonitors[$i];
                $lowest_key = 0;
                $lowest_val = -1;
                $oriCount = count($arrMonitors);
                for ($x = 0; $x < $oriCount; $x++) {
                    $mon = $arrMonitors[$x];
                    if ($lowest_val < 0) {
                        // first one, so let's just assign it as the lowest so far
                        $lowest_key = $x;
                        $lowest_val = $mon['statusDifferenceInSeconds'];
                    }
                    elseif ($mon['statusDifferenceInSeconds'] < $lowest_val) {
                        $lowest_key = $x;
                        $lowest_val = $mon['statusDifferenceInSeconds'];
                    }
                }
                // add lowest line and delete from main array
                array_push($newArrMonitors, $arrMonitors[$lowest_key]);
                unset($arrMonitors[$lowest_key]);
                // resort the table keys
                $arrMonitors = array_merge($arrMonitors);
            }
            return $newArrMonitors;
        }
        else {
            // just return the same (untouched) array
            return $arrMonitors;
        }
    }
    function addToArray(&$arrPile, $arrMore) {
        // add more to the pile (arrays)
        $baseId = count($arrPile);
        if (count($arrMore) > 0) {
            foreach ($arrMore as $more) {
                $arrPile[$baseId] = $more;
                $baseId++;
            }
        }
    }

    
    
    // Internal class functions
    
    // Make the call to the up.time API via JSON
    public function getJSON( $apiRequest, &$error = "", $dieOnError = true, $autoDecodeJSON = true ) {
        // clear error string
        $error = "";
        // initialize our curl session
        $session = curl_init();
        $proto = "https";
        if ( ! $this->apiSSL ) { $proto = "http"; }
        curl_setopt($session, CURLOPT_URL, "{$proto}://{$this->apiHostname}:{$this->apiPort}/api/{$apiRequest}" );
        // no need for authentication if we're just getting the API info
        if ( strlen($apiRequest) > 0) {
            curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($session, CURLOPT_USERPWD, "{$this->apiUsername}:{$this->apiPassword}" );
        }
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        // get around any SSL certificate restrictions
        // if SSL certificate is valid and purchased, change these to "true"
        if ( $this->apiSSL ) {  // SSL
            curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
        }

        // fetch our list of elements
        $output = curl_exec($session);
        // get HTTP result status (including HTTP code)
        $resultStatus = curl_getinfo($session);                                   
        // check for errors
        if (curl_error($session)) {
            // CURL error
            if ($dieOnError) {
                die( "Error Fetching Data => ".curl_error($session) );
            }
            else {
                // return error instead
                $error = curl_error($session);
            }
        }
        // check for HTTP/authentication errors
        elseif($resultStatus['http_code'] == 200) {
            // all good, so do nothing
        }
        else {
            // HTTP error (usually due to authentication)
            $err_code = $resultStatus['http_code'];
            if ($err_code == 401) {
                $error = "HTTP error {$err_code}: Unauthorized";
            }
            elseif ($err_code == 404) {
                $error = "HTTP error {$err_code}: Not Found";
            }
            else {
                $error = "HTTP error {$err_code}";
            }
        }
        curl_close($session);

        if ($autoDecodeJSON) {
            // parse json objects into array
            $output = json_decode ( $output, true );
        }

        // return parsed json output
        return $output;
    }
    // Read filter string and put it into an array
    // The valid filter string format is: "var1=x&var2=y"
    // It will trim away extra spaces properly
    protected function parseFilterString($filter) {
        $filter_arr = array();
        $tmp_arr = array($filter);
        // split filters by "&", if exists
        if ( preg_match('/\&/', $filter) ) {
            $tmp_arr = explode('&', $filter);
        }
        if ( is_array($tmp_arr) && count($tmp_arr) >= 1) {
            foreach($tmp_arr as $f) {
                // split key/value pairs by "="
                $cur_filter_arr = explode('=', trim($f), 2);
                // verify the key/value pair
                if ( count($cur_filter_arr) == 2 && strlen(trim($cur_filter_arr[0])) > 0 && strlen(trim($cur_filter_arr[1])) > 0) {
                    // let's add it to the filter array
                    $filter_arr[trim($cur_filter_arr[0])] = trim($cur_filter_arr[1]);
                }
            }
        }
        return $filter_arr;
    }
    // Run through all filters
    // The key is case sensitive, but the value will be checked as a regex with case-insensitivity
    protected function runFilter($output, $filter) {
        if ( strlen(trim($filter)) > 0 && strlen(trim($filter)) > 0 ) {
            $filter_arr = $this->parseFilterString($filter);
            // now that the key/value pairs are in an array ($filter_arr), let's check if any filter has been applied
            if ( count($filter_arr) > 0 && count($output) > 0 ) {
                $needToReset = false;
                // check the first level of the output array
                foreach ($output as $o_key => $line) {
                    foreach ($filter_arr as $filter_key => $filter_value) {
                        // check if the array key exists and if the value matches (case-insensitive)
                        $pattern = "/^{$filter_value}$/i";
                        //if ( property_exists($line, $filter_key) && preg_match($pattern, $line->$filter_key) ) {  // stdClass
                        if ( array_key_exists($filter_key, $line) && preg_match($pattern, $line[$filter_key]) ) {   // array
                            // passed the filter, so let's keep it
                        }
                        else {
                            // since it doesn't pass the filter, let's drop it
                            unset($output[$o_key]);
                            $needToReset = true;
                        }
                    }
                }
                // we also need to reset all the indexes to zero as PHP doesn't do this for us
                if ($needToReset) {
                    $output = array_merge($output);
                }
            }
        }
        return $output;
    }
    // Determine if the cache last check time (unix timestamp in seconds) is new enough (under the max cache duration time)
    // Returns "true" if all is OK, or if there is no lastCheck time (first time running)
    // Returns "false" if $lastCheck is older than the max cache duration time
    protected function lastCheckIsCurrent($lastCheck) {
        $rv = true;
        if (isset($lastCheck)) {
            $cacheLength = $lastCheck - time();
            if ($cacheLength >= $this->cacheDuration) {
                $rv = false;
            }
        }
        return $rv;
    }
}
?>