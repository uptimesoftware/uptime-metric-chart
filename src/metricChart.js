    // Initialize variables
    var date = new Date();
    var uptimeOffset = date.getTimezoneOffset()*60;
    var debugMode = null;
    var interval = null;
    var requestString = null;
    var myChart = null;
    var myChartDimensions = null;
    var renderSuccessful = null;  //Add this later
    var metricType = null;
    var divsToDim = ['#widgetChart', '#widgetSettings'];
    var settings = {metricType: null, metricValue: null, elementValue: null,
            timeFrame: null, refreshInterval: null, chartType: null,
            chartTitle: null, seriesTitle: null};
    var gadgetInstanceId = uptimeGadget.getInstanceId();
    var currentURL = $("script#ownScript").attr("src");
    var getMetricsPath = currentURL.substr(0,$("script#ownScript").attr("src").lastIndexOf("/")+1) + 'getmetrics.php';
    var getDropDownsPath = currentURL.substr(0,$("script#ownScript").attr("src").lastIndexOf("/")+1) + 'getdropdowns.php';
    var timeFrameOptions = {"3600" : "hour", "21600" : "6 hours",
                "43200" : "12 hours", "86400" : "day",
                "172800" : "2 days", "604800" : "week",
                "1209600" : "2 weeks", "2592000" : "30 days"};
    var timeFrameSliderOptions = {"1" : "3600", "2" : "21600",  "3" : "43200",
                    "4" : "86400", "5" : "172800", "6" : "604800",
                    "7" : "1209600", "8" : "2592000"};
    var refreshIntervalOptions = {"10000" : "10 seconds", "30000" : "30 seconds",
                    "60000" : "minute", "300000" : "5 minutes",
                    "600000" : "10 minutes"};
    var refreshIntervalSliderOptions = {"1" : "10000", "2" : "30000", "3" : "60000",
                        "4" : "300000", "5" : "600000" };
    
    if (debugMode) {
        console.log('Metric Chart: Debug logging enabled');
    }
    
    if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Current path to getmetrics.php: ' + getMetricsPath);}
    
    // Initialize handlers
    uptimeGadget.registerOnEditHandler(showEditPanel);
    uptimeGadget.registerOnLoadHandler(function(onLoadData) {
        if (onLoadData.hasPreloadedSettings()) {
            goodLoad(onLoadData.settings);
        } else {
            uptimeGadget.loadSettings().then(goodLoad, onBadAjax);
        }
    });
    uptimeGadget.registerOnResizeHandler(resizeGadget);
    
    
    // Populate dropdowns
    populateDropdowns();
    
    // Unleash the popovers!
    var popOptions = {
        delay: {show: 1000}
        };
    // Enable multiselect
    var popTargets = [$("#metric-type-radio"), $("#service-monitor-metrics-div"),
            $("#service-monitor-elements-div"), $("#performance-metrics-div"),
            $("#performance-elements-div"), $("#timeFrameSliderAndLabel"),
            $("#refreshIntervalSliderAndLabel"), $("#chart-type-radio"),
            $("#chart-title-btn")];
    $.each (popTargets, function(index, target) {
            target.popover(popOptions);
            });
    
    // Clear alerts and save settings on configuration closure
    $("#closeSettings").click(function() {
        saveSettings();
        });
    $("#closeNoSave").click(function() {
        $("#widgetSettings").slideUp();
        });
    // Open config panel on double-click
    $("#widgetChart").dblclick(function() {
        showEditPanel();
        });
    // Toggle debug logging on double-click of 'eye' icon
    $("#visualOptionsIcon").dblclick(function() {
        if (debugMode === null) {
            debugMode = true;
            console.log('Gadget #' + gadgetInstanceId + ' - Debug logging enabled');
        } else if (debugMode === true) {
            debugMode = false;
            console.log('Gadget #' + gadgetInstanceId + ' - Debug logging disabled');
        }
    });
    
    // Metric type changed
    $("#metric-type-radio").on('change', function(evt, params) {

        
        
        
        if ($('#service-monitor-metrics-btn').is(':checked')) {
            if (settings.metricType !== 'servicemonitor'){
                $("#options-div").hide();
            }
            $("#performance-div").hide();
            $("#network-div").hide();
            $("#service-monitor-div").show();
            $("select.service-monitor-metrics").chosen();
            $("select.service-monitor-elements").chosen();
            $("select.service-monitor-ranged").chosen();
            
            my_params = Array();
            my_params.requestString = getDropDownsPath + '?uptime_offest=' + uptimeOffset + '&query_type=monitors';
            my_params.dropdownID = "service-monitor-metrics";
            if (typeof metricValue !== 'undefined') { my_params.savedValue = metricValue; } else { my_params.savedValue = undefined; }
            my_params.targetmetricType = 'servicemonitor';
            updateDropDown(my_params);

        }
        else if ($('#performance-metrics-btn').is(':checked')) {
            if (settings.metricType !== 'performance'){
                $("#options-div").hide();
            }
            $("#service-monitor-div").hide();
            $("#network-div").hide();
            $("#performance-div").show();
            $("select.performance-metrics").chosen();
            $("select.performance-elements").chosen();
            
            $("select.performance-metrics").empty();
            $("select.performance-metrics").append('<option value="cpu">CPU - Used (%)</option>');
            $("select.performance-metrics").append('<option value="memory">Memory - Used (%)</option>');
            $("select.performance-metrics").append('<option value="used_swap_percent">Swap - Used (%)</option>');
            $("select.performance-metrics").append('<option value="worst_disk_usage">Disk - Worst Disk Used (%)</option>');
            $("select.performance-metrics").append('<option value="worst_disk_busy">Disk - Worst Disk Busy (%)</option>');
            
            if (typeof metricValue !== 'undefined' && metricType == 'performance') {
                if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Setting performance monitor metric droptown to: ' + metricValue);}
                $("select.performance-metrics").val(metricValue).trigger("chosen:updated").trigger('change');
            } else {
                $("select.performance-metrics").trigger("chosen:updated").trigger('change');
            }
        }
        

        else if ($('#network-metrics-btn').is(':checked')) {
            if (settings.metricType !== 'network'){
                $("#options-div").hide();
            }
            $("#service-monitor-div").hide();
            $("#performance-div").hide();
            $("#network-div").show();
            $("select.network-metrics").chosen();
            $("select.network-elements").chosen();
            $("select.network-ports").chosen();

            $("select.network-metrics").empty();
            $("select.network-metrics").append('<option value="kbps_total_rate">Total Rate (Mbps)</option>');
            $("select.network-metrics").append('<option value="usage_percent">Usage (%)</option>');
            $("select.network-metrics").append('<option value="kbps_in_rate">In Rate (Mbps)</option>');
            $("select.network-metrics").append('<option value="usage_in_percent">In Usage (%)</option>');
            $("select.network-metrics").append('<option value="kbps_out_rate">Out Rate (Mbps)</option>');
            $("select.network-metrics").append('<option value="usage_out_percent">Out Usage (%)</option>');
            $("select.network-metrics").append('<option value="errors_total_rate">Errors (#/sec)</option>');
            $("select.network-metrics").append('<option value="errors_in_rate">In Errors (#/sec)</option>');
            $("select.network-metrics").append('<option value="errors_out_rate">Out Errors (#/sec)</option>');
            $("select.network-metrics").append('<option value="discards_total_rate">Discards (#/sec)</option>');
            $("select.network-metrics").append('<option value="discards_in_rate">In Discards (#/sec)</option>');
            $("select.network-metrics").append('<option value="discards_out_rate">Out Discards (#/sec)</option>');
            
            if (typeof metricValue !== 'undefined' && metricType == 'network') {
                if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Setting network monitor metric droptown to: ' + metricValue);}
                $("select.network-metrics").val(metricValue).trigger("chosen:updated").trigger('change');
                //$("select.network-ports").val(portValue).trigger("chosen:updated").trigger('change');
            } else {
                $("select.network-metrics").trigger("chosen:updated").trigger('change');
            }
        }
        
    });
    

    
    // Service monitor metric changed
    $("select.service-monitor-metrics").on('change', function(evt, params) {
        my_params = Array();
        my_params.requestString = getDropDownsPath + '?uptime_offest=' + uptimeOffset + '&query_type=elements_for_monitor&monitor=' + $("select.service-monitor-metrics").val();
        my_params.dropdownID = "service-monitor-elements";
        if (typeof elementValue !== 'undefined') { my_params.savedValue = elementValue; } else { my_params.savedValue = undefined; }
        my_params.targetmetricType = 'servicemonitor';
        updateDropDown(my_params);
    });
        


    // Service monitor element changed
    $("select.service-monitor-elements").on('change', function(evt, params) {
        launchDivs();
    });
    
    //plui
    // Ranged metric changed
    $("select.service-monitor-elements").on('change', function(evt, params) {
        
        if ($("select.service-monitor-metrics").val().slice(-1) == "6") {
            if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Ranged Data Selected');}
            $("#service-monitor-ranged-div").show();
        } else {
            $("#service-monitor-ranged-div").hide();
        }
        
        my_params = Array();
        my_params.requestString =  getDropDownsPath + '?uptime_offest=' + uptimeOffset + '&query_type=ranged_objects&element='
                                   + $("select.service-monitor-elements").val()
                                   + '&object_list=' + $("select.service-monitor-ranged").val();
        my_params.dropdownID = "service-monitor-ranged";
        if (typeof objectValue !== 'undefined') { my_params.savedValue = objectValue; } else { my_params.savedValue = undefined; }
        my_params.targetmetricType = 'servicemonitor';
        updateDropDown(my_params);
    });
    
    
    
    
    // Performance metric changed
    $("#performance-metrics-btn").on('change', function(evt, params) {
        my_params = Array();
        my_params.requestString = getDropDownsPath + '?uptime_offest=' + uptimeOffset + '&query_type=elements_for_performance';
        my_params.dropdownID = "performance-elements";
        if (typeof elementValue !== 'undefined') { my_params.savedValue = elementValue; } else { my_params.savedValue = undefined; }
        my_params.targetmetricType = 'performance';
        updateDropDown(my_params);
    });
    
    // Performance monitor element changed
    $("select.performance-elements").on('change', function(evt, params) {
        launchDivs();
    });
    
    
    
    
    
    
    
    
    
    
    
    // Network metric changed
    $("#network-metrics-btn").on('change', function(evt, params) {
        my_params = Array();
        my_params.requestString = getDropDownsPath + '?uptime_offest=' + uptimeOffset + '&query_type=listNetworkDevice';
        my_params.dropdownID = "network-elements";
        if (typeof elementValue !== 'undefined') { my_params.savedValue = elementValue; } else { my_params.savedValue = undefined; }
        my_params.targetmetricType = 'network';
        updateDropDown(my_params);
    });
    
    // Network element changed
    $("select.network-elements").on('change', function(evt, params) {
        my_params = Array();
        my_params.requestString = getDropDownsPath + '?uptime_offest=' + uptimeOffset + '&query_type=devicePort' + '&element=' + $("select.network-elements").val();
        my_params.dropdownID = "network-ports";
        if (typeof portValue !== 'undefined') {  my_params.savedValue = portValue; } else { my_params.savedValue = undefined; }
        my_params.targetmetricType = 'network';
        updateDropDown(my_params);
    });

    
    // Network port(s) changed
    $("select.network-ports").on('change', function(evt, params) {
        launchDivs();
    });
        
    // Key functions
    function populateDropdowns() {
        $("#timeFrameSlider").slider({
            range: "min", value: 4, min: 1, max: 8, animate: true,
            slide: function( event, ui ) {
                $("#timeFrameLabelContents").text('Last ' + timeFrameOptions[timeFrameSliderOptions[ui.value]]);
                }
            });
        
        $("#refreshIntervalSlider").slider({
            range: "max", value: 4, min: 1, max: 5, animate: true,
            slide: function( event, ui ) {
                $("#refreshIntervalLabelContents").text('Every ' + refreshIntervalOptions[refreshIntervalSliderOptions[ui.value]]);
                }
            });
        $("#options-div").hide();
    }

    //function to handle the repetitive tasks for updating dropdown based off of a requestString
    //need pass all the params inside of an array to avoid errors about savedValue or metricType being undefined
    function updateDropDown (params )
    {
        dropdownID = params.dropdownID;
        requestString = params.requestString;
        savedValue = params.savedValue;
        targetmetricType = params.targetmetricType;

        //build our selectors based on the dropdownID
        dropdownSelector = "select." + dropdownID;

        $(dropdownSelector).empty();
        $(dropdownSelector).trigger("chosen:updated");
        $.getJSON(requestString, function(data) {
        }).done(function(data) {
            $(dropdownSelector).empty();
            $.each(data, function(key, val) {
                $(dropdownSelector).append('<option value="' + val + '">' + key + '</option>');
            });
            if (typeof savedValue !== undefined && metricType != null) {

                if (metricType == targetmetricType)
                {
                    $(dropdownSelector).val(savedValue).trigger("chosen:updated").trigger('change');
                }
                else
                {
                    $(dropdownSelector).trigger("chosen:updated").trigger('change');
                }
            } else {
                $(dropdownSelector).trigger("chosen:updated").trigger('change');
            }
            
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.log('Gadget #' + gadgetInstanceId + ' - Request failed! ' + textStatus);
        }).always(function() {
            // console.log('Request completed.');
        });
    }
    
    function showEditPanel() {
        if (interval) {
            clearInterval(interval);
        }
        $("#widgetBody").slideDown(function() {
            $("#widgetSettings").slideDown();
        });
        $("#widgetChart").height($(window).height());
    }
    
    function saveSettings() {
        if ($("#service-monitor-metrics-radio").hasClass('active')) {
            settings.metricType = 'servicemonitor';
            settings.metricValue = $("select.service-monitor-metrics").val();
            settings.elementValue = $("select.service-monitor-elements").val();
            if ($("select.service-monitor-metrics").val().slice(-1) == "6") {
                settings.objectValue = $("select.service-monitor-ranged").val();
            }
            if ($("#chart-title-btn").hasClass('active')) {
                settings.chartTitle = $('select.service-monitor-metrics option:selected').text();
            } else {
                settings.chartTitle = "";
            }
            settings.seriesTitle = $('select.service-monitor-metrics option:selected').text();
        }
        else if ($("#performance-metrics-radio").hasClass('active')) {
            settings.metricType = 'performance';
            settings.metricValue = $("select.performance-metrics").val();
            settings.elementValue = $("select.performance-elements").val();
            if ($("#chart-title-btn").hasClass('active')) {
                settings.chartTitle = $('select.performance-metrics option:selected').text();
            } else {
                settings.chartTitle = "";
            }
            settings.seriesTitle = $('select.performance-metrics option:selected').text();
        }
        else if ($("#network-metrics-radio").hasClass('active')) {
            settings.metricType = 'network';
            settings.metricValue = $("select.network-metrics").val();
            settings.elementValue = $("select.network-elements").val();
            settings.portValue = $("select.network-ports").val();
            if ($("#chart-title-btn").hasClass('active')) {
                settings.chartTitle = $('select.network-metrics option:selected').text() + ' for ' + $('select.network-elements option:selected').text();
                            
            } else {
                settings.chartTitle = "";
            }
            settings.seriesTitle = $('select.network-metrics option:selected').text();
        }

        if ($("#toggle-legend-btn").hasClass('active')) {
            settings.showLegend = true;
        }
        else
        {
            settings.showLegend = false;
        }
        
        
        timeFrameIndex = $("#timeFrameSlider").slider("value");
        settings.timeFrame = timeFrameSliderOptions[timeFrameIndex];
        refreshIntervalIndex = $("#refreshIntervalSlider").slider("value");
        settings.refreshInterval = refreshIntervalSliderOptions[refreshIntervalIndex];
        
        var checkedButton = $("input[name='graph-type-options']:checked").val();
        if (checkedButton == 'areaspline'){
            settings.chartType = checkedButton;
        }
        else if (checkedButton == 'spline'){
            settings.chartType = checkedButton;
        }
        else {
            settings.chartType = 'spline';
        }
    
        console.log('Gadget #' + gadgetInstanceId + ' - Saved settings: ' + printSettings(settings));
        uptimeGadget.saveSettings(settings).then(onGoodSave, onBadAjax);
    }
    
    function loadSettings(settings) {
        console.log('Gadget #' + gadgetInstanceId + ' - Loaded settings: ' + printSettings(settings));
    
        showEditPanel();
        
        metricType = settings.metricType;
        chartType = settings.chartType;
        metricValue = settings.metricValue;
        objectValue = settings.objectValue;
        elementValue = settings.elementValue;
        portValue = settings.portValue;
        timeFrame = settings.timeFrame;
        refreshInterval = settings.refreshInterval;
        chartTitle = settings.chartTitle;

        //in case people are upgrading from an earlier version
        // where you couldn't toggle the legend, we'll set this to true if undefined 
        if (typeof settings.showLegend !== undefined)
        {
            showLegend = settings.showLegend;
        }
        else
        {
            settings.showLegend = true;
            showLegend = true;
        }
        
        if (metricType == "servicemonitor") {
            if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Setting metric type to: ' + metricType);}
            $("#service-monitor-metrics-btn").trigger('click');
        }
        if (metricType == "performance") {
            if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Setting metric type to: ' + metricType);}
            $("#performance-metrics-btn").trigger('click');
        }
        if (metricType == "network") {
            if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Setting metric type to: ' + metricType);}
            $("#network-metrics-btn").trigger('click');
        }
        if (chartTitle === "") {
            $("#chart-title-btn").removeClass('active');
        }
        if (showLegend === false)
        {
            $("#toggle-legend-btn").removeClass('active');
        }
    }
    
    function goodLoad(settings) {
        clearStatusBar();
        if (settings) {
            loadSettings(settings);
            displayChart(settings);
        } else if (uptimeGadget.isOwner()) { // What does this do?
            $('#widgetChart').hide();
            showEditPanel();
        }
    }
    
    function onGoodSave() {
        clearStatusBar();
        displayChart(settings);
    }
    
    function launchDivs() {
        $("#options-div").show();
        
        if (typeof timeFrame !== 'undefined') {
            if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Setting time frame droptown to: ' + timeFrame);}
            $.each(timeFrameSliderOptions, function(k, v) {
                if (v == timeFrame) {
                    if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Setting time frame to: '
                            + timeFrame + ' and timeFrameSlider to '
                            + timeFrameOptions[timeFrameSliderOptions[k]]);}
                    $("#timeFrameSlider").slider("option", "value", k);
                    $("#timeFrameLabelContents").text('Last ' + timeFrameOptions[timeFrameSliderOptions[k]]);
                }
            });
        }
        if (typeof refreshInterval !== 'undefined') {
            if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Setting refresh interval droptown to: ' + refreshInterval);}
            $.each(refreshIntervalSliderOptions, function(k, v) {
                if (v == refreshInterval) {
                    if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Setting refresh rate to: '
                            + timeFrame + ' and refreshRateSlider to '
                            + refreshIntervalOptions[refreshIntervalSliderOptions[k]])};
                    $("#refreshIntervalSlider").slider("option", "value", k);
                    $("#refreshIntervalLabelContents").text('Every ' + refreshIntervalOptions[refreshIntervalSliderOptions[k]]);
                }
            });
        }
        if (typeof chartType !== 'undefined') {
            if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Setting chart type to: ' + chartType);}
            if (chartType == 'areaspline') {
                $("#area-btn").trigger('click');
            }
            if (chartType == 'spline') {
                $("#line-btn").trigger('click');
            }
        }
        
        $("#buttonDiv").show();
    }
    
    function printSettings(settings) {
        var printString = 'metricType: ' + settings.metricType + ', metricValue: ' + settings.metricValue
                + ', elementValue: ' + settings.elementValue + ', portValue: ' + settings.portValue
                + ', objectValue: ' + settings.objectValue + ', timeFrame: ' + settings.timeFrame
                + ', refreshInterval: ' + settings.refreshInterval + ', chartType: ' + settings.chartType
                + ', chartTitle: ' + settings.chartTitle + ', seriesTitle: ' + settings.seriesTitle;
        return printString;
    }

    function displayChart(settings) {
        if (myChart) {
            //myChart.stopTimer();
            myChart.destroy();
            myChart = null;
        }
    
        $("#widgetChart").show();
        $("#widgetChart").empty();
        $("#graph-div").show("fade", 600);
        
        if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Graph refresh rate: ' + (settings.refreshInterval / 1000) + ' seconds');}
        
        renderChart(settings);
        clearInterval(interval);
        interval = setInterval(function() {
            getChartData(settings, false);
            }, settings.refreshInterval);
    }

    function getChartData(settings, initialLoad)
    {
        //build our request string from the contents of settings
        if (initialLoad)
        {
            requestString = getMetricsPath + '?uptime_offest=' + uptimeOffset + '&query_type=' + settings.metricType
                + '&monitor=' + settings.metricValue + '&element=' + settings.elementValue
                + '&port=' + settings.portValue
                + '&object_list=' + settings.objectValue
                + '&time_frame=' + settings.timeFrame ;
        }
        else
        {
            console.log('refreshing');
            timeFrame = settings.refreshInterval / 1000;
            requestString = getMetricsPath + '?uptime_offest=' + uptimeOffset + '&query_type=' + settings.metricType
                + '&monitor=' + settings.metricValue + '&element=' + settings.elementValue
                + '&port=' + settings.portValue
                + '&object_list=' + settings.objectValue
                + '&time_frame=' + timeFrame ;
        }

        $.ajax({url: requestString,
            dataType: 'json'},
            function(data) {})
            .done (function( data ) {
            
                if (data.length < 1 && initialLoad === true) {
                    errorMessage = "There isn't enough monitoring data available for this metric and time period.";
                    displayError(errorMessage,requestString);
                    showEditPanel();
                    $("#closeSettings").button('reset');
                    $("#widgetBody").slideDown();
                    $("#loading-div").hide('fade');
                } else if ( initialLoad === true) {
                    //first time loading series
                    if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Response: ' + JSON.stringify(data));}
                                
                    $.each(data, function(index, value) {

                        myChart.addSeries({
                            name: value[0],
                            data: value[1]
                        });

                   });

                   myChart.options.title.text = settings.chartTitle;

                   myChart.render();
                } else if ( initialLoad === false) {
                    //adding new points to the existing series
                    $.each(data, function(index,value) {
                        console.log(value[0] + " - " + value[1]);
                        $.each(myChart.series,function(index, myseries) {
                            
                            if ( myseries.name == value[0])
                            {
                                console.log("Series Matched: " + value[0]);
                                $.each(value[1], function(index,point) {
                                    myseries.addPoint(point, false, true);
                                });
                                return false;//break from the each loop
                            }
                        });
                    });
                    myChart.redraw();
                }
                

                 
                
                
                })
            .fail (function(jqXHR, textStatus, errorThrown) {
                //only display errors during the initial Load
                if (initialLoad === true) {
                    errorMessage = textStatus + ' - ' + errorThrown;
                    displayError(errorMessage,requestString);
                    showEditPanel();
                    $("#closeSettings").button('reset');
                    $("#loading-div").hide('fade');
                }
            });

    }
    
    function renderChart(settings) {
        $("#closeSettings").button('loading');
        $("#widgetBody").slideUp();
        $("#loading-div").show('fade');
        
        if (typeof settings.showLegend === "undefined")
        {
            settings.showLegend = true;
        }

        var options = {
            chart: {renderTo: 'widgetChart',
                defaultSeriesType: settings.chartType,
                style: {fontFamily: 'Arial',
                    fontSize: '9px'},
                spacingTop: 10,
                spacingBottom: 10},
            title: {text: ""},
            credits: {enabled: false},
            xAxis: {type: 'datetime',
                title: {enabled: true,
                    text: ""}},
            yAxis: {min: 0,
                title: {enabled: false,
                    text: ""}},
            plotOptions: {spline: {marker: {enabled: false}},
                    areaspline: {marker: {enabled: false}}},
            series: [],
            legend: {
                enabled: settings.showLegend
            }};


 
        if (debugMode) {console.log('Gadget #' + gadgetInstanceId + ' - Requesting: ' + requestString);}

        myChart = new Highcharts.Chart(options);
                    $("#closeSettings").button('reset');
                    $("#loading-div").hide('fade');
                    $("#widgetSettings").slideUp();
                    $("#alertModal").modal('hide');

        getChartData(settings, true);
    }
    
    function displayError(errorMessage,requestString) {
        console.log('Gadget #' + gadgetInstanceId + ' - Error: ' + errorMessage);
        $("#alertModalBody").empty();
        $("#alertModalBody").append('<p class="text-danger"><strong>Error:</strong> ' + errorMessage + '</p>'
                        + 'Here is the request string which has resulted in this error:'
                        + '<br><blockquote>' + requestString + '</blockquote>');
        $("#alertModal").modal('show');
    }
    // Static functions
    function displayStatusBar(msg) {
        gadgetDimOn();
        var statusBar = $("#statusBar");
        statusBar.empty();
        var errorBox = uptimeErrorFormatter.getErrorBox(msg);
        errorBox.appendTo(statusBar);
        statusBar.slideDown();
    }
    function clearStatusBar() {
        gadgetDimOff();
        var statusBar = $("#statusBar");
        statusBar.slideUp().empty();
    }
    function resizeGadget(dimensions) {
        myChartDimensions = toMyChartDimensions(dimensions);
        if (myChart) {
            myChart.setSize(myChartDimensions.width, myChartDimensions.height);
        }
        $("body").height($(window).height());

    }
    function toMyChartDimensions(dimensions) {
        return new UPTIME.pub.gadgets.Dimensions(Math.max(100, dimensions.width - 5), Math.max(100, dimensions.height));
    }
    function onBadAjax(error) {
        displayStatusBar(error, "Error Communicating with up.time");
    }
    function gadgetDimOn() {
        $.each(divsToDim, function(i, d) {
            var div = $(d);
            if (div.is(':visible') && div.css('opacity') > 0.6) {
                div.fadeTo('slow', 0.3);
            }
        });
    }
    function gadgetDimOff() {
        $.each(divsToDim, function(i, d) {
            var div = $(d);
            if (div.is(':visible') && div.css('opacity') < 0.6) {
                div.fadeTo('slow', 1);
            }
        });
    }
