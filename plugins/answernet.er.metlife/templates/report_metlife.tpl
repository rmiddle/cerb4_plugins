<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>{$translate->_('answernet.er.metlife.report1.text')}</h2>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="answernet.er.metlife.id.report1">
<input type="hidden" name="extid_a" id="extid_a" value="AnswernetMetlifeReportGroupReport1">

<div id="divCal" style="display:none;position:absolute;z-index:1;"></div>
<br>
<tr>
  <td width="0%" nowrap="nowrap">Week Number:</td>
  <td width="100%"><select name="start">
    <option value=""></option>
        {foreach from=$week_choices item=wc key=time}
          <option value="{$wc.time}">{$wc.text}</option>
        {/foreach}
      </select></td>
</tr>
<tr>
  <td width="0%" nowrap="nowrap">Groups:</td>
  <td width="100%"><select name="group">
    <option value="All">All Groups</option>
        {foreach from=$metlife_choices item=mc key=group}
          <option value="{$mc.group}">{$mc.text}</option>
        {/foreach}
      </select></td>
</tr>
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
