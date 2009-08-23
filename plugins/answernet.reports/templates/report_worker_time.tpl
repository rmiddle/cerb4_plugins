<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>{$translate->_('reports.ui.group.answernet.worker.time')}</h2>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="report.group.answernet.worker.time">
<input type="hidden" name="extid_a" id="extid_a" value="getTimeSpentWorkerReport">
{$translate->_('timetracking.ui.reports.from')} <input type="text" name="start" id="start" size="10" value="{$start}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.start);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
{$translate->_('timetracking.ui.reports.to')} <input type="text" name="end" id="end" size="10" value="{$end}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.end);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<button type="button" id="btnSubmit" onclick="genericAjaxPost('frmRange', 'report');">{$translate->_('common.refresh')|capitalize}</button>
<a href="{devblocks_url}{$href_filename}{/devblocks_url}">{$translate->_('answernet.ui.reports.download.csv')}</a>

<div id="divCal" style="display:none;position:absolute;z-index:1;"></div>
<br>
{$translate->_('timetracking.ui.reports.past')} <a href="javascript:;" onclick="document.getElementById('start').value='-1 year';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_year')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-6 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:6}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-3 months';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{'timetracking.ui.reports.filters.n_months'|devblocks_translate:3}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 month';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_month')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 week';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_week')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-1 day';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('timetracking.ui.reports.filters.1_day')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='today';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('common.today')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='-2 weeks Monday';document.getElementById('end').value='Last Monday';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.group.answernet.worker.time.last')|lower}</a>
| <a href="javascript:;" onclick="document.getElementById('start').value='Last Monday';document.getElementById('end').value='now';document.getElementById('btnSubmit').click();">{$translate->_('reports.ui.group.answernet.worker.time.this')|lower}</a>
<br>
{$translate->_('answernet.ui.reports.type')} <select name="report_type">
	<option value="0">{$translate->_('answernet.ui.reports.type.summary')}</option>
	<option value="2">{$translate->_('answernet.ui.reports.type.subgroup')}</option>
	<option value="4">{$translate->_('answernet.ui.reports.type.detail')}</option>
</select>

{$translate->_('timetracking.ui.worker')} <select name="worker_id">
	<option value="0">{$translate->_('timetracking.ui.reports.time_spent_org.all_workers')}</option>
{foreach from=$workers item=worker key=worker_id name=workers}
	<option value="{$worker_id}">{$worker->getName(true)}</option>
{/foreach}
</select>
</form>
<br>

{*<div id="myContainer" style="width:100%;height:0;background-color:rgb(255,255,255);"></div>*}

<div id="report" style="background-color:rgb(255,255,255);"></div>


<script language="javascript" type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	document.getElementById('btnSubmit').click();
});
{/literal}
</script>
