<?php

class uptimeDB
{

	//Example ODBC Driver Strings for both Oracle & SQL Server
	//If your intending to use this with an Oracle or SQL Server based datastore, 
	//then you will need to uncomment the appropriate ODBC driver for your server
	//Examples are provided for both of the common ODBC driver locations/Names for Windows & Linux

	//Mysql Datastores use the mysqli functions instead, and don't require any additional drivers.


	//Linux Oracle ODBC Driver
	//private $ORACLE_ODBC_DRIVER = "/usr/lib/oracle/12.1/client64/lib/libsqora.so.12.1";

	//Linux MSSQL ODBC Driver 
	//private $MSSQL_ODBC_DRIVER = "/usr/lib64/libtdsodbc.so.0";

	//Windows Oracle ODBC Driver
	//private $ORACLE_ODBC_DRIVER = "{Oracle in OraClient12Home1}";

	//Windows MSSQL ODBC Driver
	//private $MSSQL_ODBC_DRIVER = "{SQL Server}";


	public $dbType;
	private $dbHost;
	private $dbPort;
	private $dbName;
	private $dbUsername;
	private $dbPassword;
	private $DB;

	public function  __construct()
	{
		$uptime_dir = substr(getenv("MIBDIRS"), 0, -4 );
		$this->readUptimeConf($uptime_dir . "uptime.conf");
	}


	public function readUptimeConf($UPTIME_CONF)
	{
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
										$this->dbType = rtrim($data[1]);
								}
								if (preg_match("/^dbHostname.*/", $buffer)) // Check for string.
								{
										$data = preg_split("/^dbHostname=/", $buffer);
										$this->dbHost = rtrim($data[1]);
								}
								if (preg_match("/^dbPort.*/", $buffer)) // Check for string.
								{
										$data = preg_split("/^dbPort=/", $buffer);
										$this->dbPort = rtrim($data[1]);
								}
								if (preg_match("/^dbName.*/", $buffer)) // Check for string.
								{
										$data = preg_split("/^dbName=/", $buffer);
										$this->dbName = rtrim($data[1]);
								}
								if (preg_match("/^dbUsername.*/", $buffer)) // Check for string.
								{
										$data = preg_split("/^dbUsername=/", $buffer);
										$this->dbUsername = rtrim($data[1]);
								}
								if (preg_match("/^dbPassword.*/", $buffer)) // Check for string.
								{
										$data = preg_split("/^dbPassword=/", $buffer);
										$this->dbPassword = rtrim($data[1]);
								}
						}
						fclose($handle); // Close the file.
						return True;
				}
		} else {
				echo "$UPTIME_CONF does not exist.  Please enter appropriate path to uptime.conf";
				return False;
		}
	}

	public function printDBinfo()
	{
		print "dbType: " . $this->dbType . "\n";
		print "dbHost: " . $this->dbHost . "\n";
		print "dbPort: " . $this->dbPort . "\n";
		print "dbName: " . $this->dbName . "\n";
		print "dbUsername: " . $this->dbUsername . "\n";
		print "dbPassword: " . $this->dbPassword . "\n";

	}


	public function connectDB()
	{
		if ($this->dbType == "mysql")
		{
			$this->DB = mysqli_connect($this->dbHost.":".$this->dbPort, $this->dbUsername, $this->dbPassword, $this->dbName);
			if (mysqli_connect_errno()) {
				printf("Connection failed: %s</br>", mysqli_connect_error());
				return false;
			}
			else {
				return true;
			}
		}
		elseif ($this->dbType == "oracle")
		{
			$this->DB = odbc_connect("Driver=" . $this->ORACLE_ODBC_DRIVER . ";DBq=".$this->dbHost.":".$this->dbPort."/".$this->dbName, $this->dbUsername, $this->dbPassword);
			if (!$this->DB)
			{
				printf("ODBC Connection Failed: " . odbc_errormsg());
				return false;
			}
			else {
				return true;
			}
		}
		elseif ($this->dbType == "mssql")
		{
			$this->DB = odbc_connect("Driver=" . $this->MSSQL_ODBC_DRIVER . ";TDS_Version=8.0;Server=".$this->dbHost.";Database=". $this->dbName.";port=". $this->dbPort, $this->dbUsername, $this->dbPassword);
			if (!$this->DB)
			{
				printf("ODBC Connection Failed: " . odbc_errormsg());
				return false;
			}
			else {
				return true;
			}
		}
		else
		{
			print "No dbType set, unable to connect!";
			return false;
		}



	}

	public function execQuery($sql)
	{
		if ($this->dbType == "mysql")
		{
			return $this->execMysqlQuery($sql);
		}
		if ($this->dbType == "oracle")
		{
			return $this->execOracleQuery($sql);
		}
		if ($this->dbType == "mssql")
		{
			return $this->execMssqlQuery($sql);
		}
		return false;

	}

	private function execMysqlQuery($sql)
	{
			$output = array();
			$result = mysqli_query($this->DB, $sql);
			if (!$result)
			{
				die('Invalid query: ' . mysqli_error($this->DB));
			}
			else
			{
				while($row = mysqli_fetch_assoc($result))
				{
					$row = array_change_key_case($row, CASE_UPPER);
					array_push($output, $row);
				}
				return $output;
			}
	}

	private function execOracleQuery($sql)
	{
			$output = array();
			$result = odbc_exec($this->DB, $sql);
			if (!$result) {
				die("Invalid Query: " . odbc_errormsg());
			}
			else
			{
				while($row = odbc_fetch_array($result))
				{
					$row = array_change_key_case($row, CASE_UPPER);
					array_push($output, $row);
				}
				return $output;
			}

	}

	private function execMssqlQuery($sql)
	{
			$output = array();
			$result = odbc_exec($this->DB, $sql);
			if (!$result) {
				die("Invalid Query: " . odbc_errormsg());
			}
			else
			{
				while($row = odbc_fetch_array($result))
				{
					$row = array_change_key_case($row, CASE_UPPER);
					array_push($output, $row);
				}
				return $output;
			}

	}



}




?>
