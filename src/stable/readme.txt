
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

To get this working with Oracle backend you need to do the following:
1. Install unixODBC via your package manager (ie. yum install unixodbc) . This will install unixODBC into your '/usr/lib64/' directory.
2. Download and Install the latest version of the 'Oracle Instant Client Basic' package, available from Oracle here. Keep in mind that up.time 7.2 comes bundled with 64bit Apache/PHP so you will need the Linux x86-64 package. ie. oracle-instantclient12.1-basic-12.1.0.1.0-1.x86_64.rpm . (The 12.1 package works with 11g Databases as well). This package will install some of the requires binaries/drivers for Oracle into /usr/lib/oracle/<version>/client64/lib/
3. Download and install the ' ODBC: Additional libraries' package from Oracle (ie. oracle-instantclient12.1-odbc-12.1.0.1.0-1.x86_64.rpm  ). This provides the actual ODBC Driver we need from Oracle.
4. Edit the /etc/init.d/uptime_httpd script that starts up.time's apache, and add the below lines to set some of the required environment variables.
This should be towards the beginning of the script, immediately after the 'export MIBDIRS' line  ).
Make sure to change <version>  in the Oracle_HOME to the correct version for the path where the drivers were installed in Step 2 above.

export ORACLE_HOME=/usr/lib/oracle/<version>/client64
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:$ORACLE_HOME/lib
export ODBCINI=/usr/local/uptime/.odbc.ini
export ODBCSYSINI=/usr/local/uptime
 
5. Restart uptime_httpd service to pick up this change (ie. /etc/init.d/uptime_httpd stop  /etc/init.d/uptime_httpd start)

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
