<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<script language="javascript" type="text/javascript">
{literal}
function drawChart(start, end) {{/literal}
	YAHOO.widget.Chart.SWFURL = "{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/charts/assets/charts.swf{/devblocks_url}?v={$smarty.const.APP_BUILD}";
	{literal}
	var worker = document.frmRange.worker_id.value;
	var group = document.frmRange.group_select.value;

	if(start==null || start=="") {
		start='-5 year'
	}
	if(end==null || end=="") {
		end='now';
	}
	if(worker==null || worker=="") {
		worker=0;
	}
	if(group==null || group=="") {
		group=0;
	}
	start=escape(start);
	end=escape(end);
	//[mdf] first let the server tell us how many records to expect so we can make sure the chart height is high enough
	var cObj = YAHOO.util.Connect.asyncRequest('GET', "{/literal}{devblocks_url}ajax.php?c=reports&a=action&extid=report.group.answernet.stats&extid_a=getTicketAssignmentChart{/devblocks_url}{literal}&countonly=1&worker_id="+worker+"&group_id="+group+"&start="+start+"&end="+end, {
		success: function(o) {
			var groupCount = o.responseText;
			//[mdf] set the chart size based on the number of records we will get from the datasource
			myContainer.style.cssText = 'width:100%;height:'+(30+30*groupCount);;
				
			var myXHRDataSource = new YAHOO.util.DataSource("{/literal}{devblocks_url}ajax.php?c=reports&a=action&extid=report.group.answernet.stats&extid_a=getTicketAssignmentChart{/devblocks_url}{literal}&worker_id="+worker+"&group_id="+group+"&start="+start+"&end="+end);
			myXHRDataSource.responseType = YAHOO.util.DataSource.TYPE_TEXT; 
			myXHRDataSource.responseSchema = {
				recordDelim: "\n",
				fieldDelim: "\t",
				fields: [
					"group",
					{key:"total", parser:"number"}
				]
			};
			
			var myChart = new YAHOO.widget.BarChart( "myContainer", myXHRDataSource,
			{
			    xField: "total",
			    yField: "group",
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

<h2>{$translate->_('reports.ui.group.answernet.stats')}</h2>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="report.group.answernet.stats">
<input type="hidden" name="extid_a" value="getTicketAssignmentReport">
{$translate->_('timetracking.ui.reports.from')} <input type="text" name="start" id="start" size="10" value="{$start}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.start);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
{$translate->_('timetracking.ui.reports.to')} <input type="text" name="end" id="end" size="10" value="{$end}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.end);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>

<button type="button" id="btnSubmit" onclick="genericAjaxPost('frmRange', 'report');drawChart(document.getElementById('start').value, document.getElementById('end').value);">{$translate->_('common.refresh')|capitalize}</button>

<div id="divCal" style="display:none;position:absolute;z-index:1;"></div>
<br>
{$translate->_('timetracking.ui.worker')} <select name="worker_id">
	<option value="0">{$translate->_('timetracking.ui.reports.time_spent_org.all_workers')}</option>
{foreach from=$workers item=worker key=worker_id name=workers}
	<option value="{$worker_id}">{$worker->getName(true)}</option>
{/foreach}
</select>

<tr>
	<td width="0%" nowrap="nowrap">Group:</td>
	<td width="100%"><select name="group_select">
		<option value="0">{$translate->_('answernet.ui.group.answernet.ticket.all_group')}</option>
     		<optgroup label="Group:">
     		{foreach from=$teams item=team}
     			<option value="a{$team->id}">{$team->name}</option>
     		{/foreach}
     		</optgroup>

     		{foreach from=$team_categories item=categories key=teamId}
     			{assign var=team value=$teams.$teamId}
     			{if !empty($active_worker_memberships.$teamId)}
      			<optgroup label="{$team->name}">
    				<option value="t{$team->id}">Inbox</option>
      			{foreach from=$categories item=category}
    				<option value="c{$category->id}">{$category->name}</option>
    			{/foreach}
    			</optgroup>
   			{/if}
    		{/foreach}
     	</select></td>
</tr>
</form>
{$translate->_('timetracking.ui.reports.past')} <a href="javascript:;" onclick="document.getElementById('start').value='-5 year';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('answernet.ui.group.answernet.ticket.5_year')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 year';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-6 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-3 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 month';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 week';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 day';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='today';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('common.today')|lower}</a>
<br>

<div id="myContainer" style="width:100%;height:0;background-color:rgb(255,255,255);"></div>

<div id="report" style="background-color:rgb(255,255,255);"></div>

<script language="javascript" type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	document.getElementById('btnSubmit').click();
});
{/literal}
</script>
