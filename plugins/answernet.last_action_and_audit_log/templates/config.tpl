
<table cellpadding="0" cellspacing="0" border="0">
  <tr>
  </tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="configActivity" name="configActivity" >
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleTabAction">
<input type="hidden" name="tab" value="answernet.last_action_and_audit_log.config.tab">
<input type="hidden" name="action" value="saveAnswernet">

<h2>{$translate->_('answernet.last_action_and_audit_log.config.tab.comment')}</h2><br>
{$translate->_('answernet.last_action_and_audit_log.config.tab.comment.audit')}
<label><input type="radio" name="enabled_comments" value="1" {if $enabled_comments}checked="checked"{/if}> Enabled</label>
<label><input type="radio" name="enabled_comments" value="0" {if !$enabled_comments}checked="checked"{/if}> Disabled</label>
<br>

{$translate->_('answernet.last_action_and_audit_log.config.tab.comment.last_action')} <select name="update_comment">
  <option value="0" {if $update_comment==0}selected="selected"{/if}>{$translate->_('answernet.last_action_and_audit_log.config.tab.default')}</option>
  <option value="1" {if $update_comment==1}selected="selected"{/if}>{$translate->_('answernet.last_action_and_audit_log.config.tab.update')}</option>
  <option value="2" {if $update_comment==2}selected="selected"{/if}>{$translate->_('answernet.last_action_and_audit_log.config.tab.custom.field')}</option>
</select>
<br>
<br>
<button type="button" id="btnSubmit" onclick="genericAjaxPost('configActivity', 'feedback');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>
<br>
<br>
<div id="feedback" style="background-color:rgb(255,255,255);"></div>
