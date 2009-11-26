<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>{$translate->_('answernet.er.metlife.report.dr.text')}</h2>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="answernet.er.metlife.id.report.dr">
<input type="hidden" name="extid_a" id="extid_a" value="AnswernetMetlifeReportDR">

<div id="divCal" style="display:none;position:absolute;z-index:1;"></div>
<br>
{$translate->_('answernet.er.metlife.report.dr.daily.text')} <input type="text" name="start" id="start" size="10" value="{$start}"><button type="button" onclick="ajax.getDateChooser('divCal',this.form.start);">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
<button type="button" id="btnSubmit" onclick="genericAjaxPost('frmRange', 'report');">{$translate->_('answernet.er.metlife.generate.report')|capitalize}</button>
</form>

{*<div id="myContainer" style="width:100%;height:0;background-color:rgb(255,255,255);"></div>*}

<div id="report" style="background-color:rgb(255,255,255);"></div>


<script language="javascript" type="text/javascript">
{literal}
YAHOO.util.Event.addListener(window,'load',function(e) {
	document.getElementById('btnSubmit').click();
});
{/literal}
</script>
