{if $invalidDate}<font color="red"><b>{$translate->_('timetracking.ui.reports.invalid_date')}</b></font>{/if}
<br>
{if !empty($time_entries)}
	{foreach from=$time_entries item=id_entry key=id}
		<div class="block">
		<table cellspacing="0" cellpadding="3" border="0">
			<tr><td colspan="2"><h2>Mask # {$id_entry.mask}<br>Ticket # {$id}</h2></td></tr>
			
			{assign var=tagged_mins value="<B>"|cat:$id_entry.mins|cat:"</B>"}
			<tr><td>Client Name: <b>{$id_entry.client}</b></td></tr>
			<tr><td>Asset Name: <b>{$id_entry.asset}</b></td></tr>
			<tr><td>Site Name: <b>{$id_entry.sitename}</b></td></tr>
			<tr><td>Requestor: <b>{$id_entry.email}</b></td></tr>
			<tr><td>Subject: <b>{$id_entry.subject}</b></td></tr>
			<tr><td>Created: <b>{$id_entry.created_date|date_format:"%Y-%m-%d"}</b></td></tr>
			<tr><td>Updated: <b>{$id_entry.updated_date|date_format:"%Y-%m-%d"}</b></td></tr>
			<tr><td>Group: <b>{$id_entry.group}</b></td></tr>
			<tr><td>Bucket: <b>{$id_entry.bucket}</b></td></tr>
			<tr><td>Status: <b>{$id_entry.status}</b></td></tr>
			<tr><td>Total Minutes: <b>{$id_entry.mins} {$translate->_('common.minutes')|lower}</b></td></tr>
		</table>
		</div>
		<br/>
	{/foreach}
{/if}

