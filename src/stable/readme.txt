
Metric Chart v2.0
------------------
The Metric Chart gadget makes it possible to graph metrics collected by up.time in just a few clicks.  You can now finally visualize those important metrics on your custom dashboard in the form of beautiful line or area graphs!  As a bonus, we're also throwing in some additional dashboard layouts, well suited to house your new graphs.


Features: 

* Serves up metrics as line or area graphs
* Leverages latest web technologies for light-weight and beautiful results
* Provides an intuitive configuration wizard
* Offers a configurable time frame and refresh interval
* Sleak in-control refresh
* Equipped with robust condition and error handling
* Enables extensive visual and console logging for easy troubleshooting
* Supported on Windows and Linux up.time monitoring stations
* Tell us which features you would like to see in a future release!

Updates:
v2.0 - Supports Oracle backend as well the times should now be displayed in the client timezone rather then UTC.


Usage Instructions:

1. Pick a metric type
2. Select a desired monitor and metric
3. Choose the element and monitor instance
4. Select the time frame and refresh interval
5. Pick a chart type, and chose whether the title should br displayed
6. Graph it!

* Open the configuration dialog by double-clicking on the chart
* Double-click on the eye icon to toggle verbose logging on and off

Installation Instructions:
--------------------------
1. Install using Plug-in Manager, following the standard process. 
2. Refresh dashboard layouts to see the new ones. 
3. Refresh the gadgets to see Metric Chart. 
4. Have fun!

Additional Oracle Install Steps:
--------------------------------
In order to use this gadget with an Oracle datastore, you will need to install the Oracle Instant Client & ODBC Drivers on your up.time monitoring.
The process for this varies for Windows & Linux. Once the appropriate ODBC Driver has been installed, you will need to edit the new getmetrics.php page on your 
monitoring station ( <uptime_dir>/GUI/gadgets/metricchart/getmetrics.php ) and uncomment one of the odbc_connect strings in the 'Oracle Connection details' section of the script(Near lines 112 to 125). 

Oracle ODBC Driver Linux Install Steps:
--------------------
1. Install unixODBC via your package manager (ie. yum install unixodbc) . This will install unixODBC into your '/usr/lib64/' directory.

2. Download and Install the latest version of the 'Oracle Instant Client Basic' package, available from Oracle (http://www.oracle.com/technetwork/database/features/instant-client/index-097480.html). Keep in mind that up.time 7.2 comes bundled with 64bit Apache/PHP so you will need the Linux x86-64 package. ie. oracle-instantclient12.1-basic-12.1.0.1.0-1.x86_64.rpm . (The 12.1 package works with 11g Databases as well). This package will install some of the required binaries/drivers for Oracle into /usr/lib/oracle/<version>/client64/lib/

3. Download and install the ' ODBC: Additional libraries' package from Oracle (ie. oracle-instantclient12.1-odbc-12.1.0.1.0-1.x86_64.rpm  ). This provides the actual ODBC Driver we need from Oracle.

4. Edit the /etc/init.d/uptime_httpd script that starts up.time's apache, and add the following lines to set some of the required environment variables(This should be towards the beginning of the script add the end of to the other export commands ie. export PATH , export MIBDIRS etc). Make sure to change <version> to the correct version for the path where the drivers were installed in Step 2 above.

export ORACLE_HOME=/usr/lib/oracle/<version>/client64
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:$ORACLE_HOME/lib

 
5. Restart uptime_httpd service to pick up this change (ie. /etc/init.d/uptime_httpd stop  /etc/init.d/uptime_httpd start)

6. Make sure to uncomment one of the odbc_connect lines in <uptime_dir>/GUI/gadgets/metricchart/getmetrics.php as explained above.


Oracle ODBC Driver Windows Install Steps:
-----------------------------------------

1. Install the Oracle Instant Client drivers or have the Oracle Client installed on the monitoring station. To get the Instant Client Download for Oracle, download the 64 bit drivers:

http://www.oracle.com/technetwork/database/features/instant-client/index.html

ie. instantclient-basic-windows.x64-12.1.0.1.0.zip

2. Create a new directory C:\Oracle. Unzip the downloaded file into the new directory. You should now have C:\Oracle\instantclient_12_1 which contains a bunch of .dll & .sym files.

3. Download the 'Instant Client Package - ODBC' from the same page above. ie. instantclient-odbc-windows.x64-12.1.0.1.0.zip . Extract this zip into the same C:\Oracle\instantclient_12_1 path.

4. Open a command prompt in the C:\Oracle\instantclient_12_1 directory and run the odbc_install.exe which will install the Oracle ODBC drivers and setup the required Environment variables.

5. Run the 'Data Sources (ODBC)' utility from the Windows 'Administrative Tools'. Click on the 'Drivers' tab, and confirm that you have an 'Oracle in instantclient' driver listed, and note the name of the driver, as this is required as the 'ODBC Driver Name' when setting up the service monitor. (Likely it will be 'Oracle in instantclient_12_1' or 'Oracle in OraClient12Home1').

6. Make sure to uncomment one of the odbc_connect lines in <uptime_dir>/GUI/gadgets/metricchart/getmetrics.php as explained above.

If your having trouble with installing just the Oracle InstantClient & ODBC , another option is to install these drivers as part of the 'Oracle Data Access Components' which is a bundle of Oracle drivers full fleged installer compared to the zips mentioned above. This bundle can be found on the Oracle website here: http://www.oracle.com/technetwork/database/windows/downloads/index.html

