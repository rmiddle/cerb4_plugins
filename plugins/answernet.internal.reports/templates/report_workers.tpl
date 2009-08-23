<script language="javascript" type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	drawChart();
});

function drawChart() {{/literal}
	YAHOO.widget.Chart.SWFURL = "{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/charts/assets/charts.swf{/devblocks_url}?v={$smarty.const.APP_BUILD}";
	{literal}
	var cObj = YAHOO.util.Connect.asyncRequest('GET', "{/literal}{devblocks_url}ajax.php?c=reports&a=action&extid=report.workers.my_workers_list&extid_a=getWorkerOpenTickets{/devblocks_url}{literal}", {
		success: function(o) {
			var resultArr = new Array();
			
			//parse the tab delimited file data from the server into an array of javascript objects
			var trimmedText =  o.responseText.replace(/^\s+|\s+$/g, '') ;
			var rows = trimmedText.split("\n");
			for(i=0; i < rows.length; i++) {
				var cols = rows[i].split("\t");
				resultArr[i] = new Object();
				resultArr[i].worker = cols[0];
				resultArr[i].ticketcount = cols[1];
			}

			//instance a yahoo charts datasource based on our result array
			var myDataSource = new YAHOO.util.DataSource(resultArr, {responseType: YAHOO.util.DataSource.TYPE_JSARRAY});
			//for the response schema, just specify the fields from the javascript object we will use for our chart
			myDataSource.responseSchema =
			{
			    fields: [ "worker","ticketcount" ]
			};

			//set the chart size based on the number of records we got from the server
			myChart.style.cssText = 'width:100%;height:'+(30+30*resultArr.length);

			//create the chart
			var chart = new YAHOO.widget.BarChart( "myChart", myDataSource,
			{
				xField: "ticketcount",
				yField: "worker",
				wmode: "opaque"
				//polling: 1000
			});

		},
		failure: function(o) {},
		argument:{caller:this}
		}
	);
}{/literal}

</script> 
<h2>My Workers Report</h2>
<br/>
<div id="myChart" style="width:100%;height:400;"></div>
<br/>

{foreach from=$workers item=worker}
{$worker->first_name} {$worker->last_name}<br/>
{/foreach}

