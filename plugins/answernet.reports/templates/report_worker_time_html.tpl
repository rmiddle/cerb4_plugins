{if $invalidDate}<font color="red"><b>{$translate->_('timetracking.ui.reports.invalid_date')}</b></font>{/if}
<br>
{if !empty($time_entries)}
	{foreach from=$time_entries item=worker_entry key=worker_id}
	{assign var=worker_name value=$workers.$worker_id->getName()}
		<div class="block">
		<table cellspacing="0" cellpadding="3" border="0">
			<tr>
				<td colspan="2">
				<h2>
				{if !empty($worker_name)}
				{$worker_name}
				{/if}
			</h2>
			</td>
			</tr>	
			
			{if $report_type > 0}
		
				{foreach from=$worker_entry.entries item=time_entry key=time_entry_id}
					{if is_numeric($time_entry_id)}
						{if empty($current_an)  or $current_an == "ZZZZZZ"}
							{assign var=current_an value=$time_entry.activity_name}
						{elseif $current_an != $time_entry.activity_name}
							<tr>
								<td colspan="2">
									<h2><b>{$translate->_('answernet.ui.reports.subtotal')} {$tagged_activity}: {$worker_entry.mins.$current_an} {$translate->_('common.minutes')|lower}</b></h2>
								</td>
							</tr>
							{assign var=current_an value=$time_entry.activity_name}
						{/if}
						
						{assign var=source_ext_id value=$time_entry.source_extension_id}
						{assign var=source_id value=$time_entry.source_id}
						{assign var=generic_worker value='timetracking.ui.generic_worker'|devblocks_translate}

					
						{if isset($worker_name)}
							{assign var=worker_name value=$worker_name}
						{else}
							{assign var=worker_name value=$generic_worker}
						{/if}
						{assign var=tagged_worker_name value="<B>"|cat:$worker_name|cat:"</B>"}
						{assign var=tagged_mins value="<B>"|cat:$time_entry.mins|cat:"</B>"}
						{assign var=tagged_activity value="<B>"|cat:$time_entry.activity_name|cat:"</B>"}
						{if $report_type > 2}
							<tr>
								<td>{$time_entry.log_date|date_format:"%Y-%m-%d"}</td>
								<td>
									{'timetracking.ui.tracked_desc'|devblocks_translate:$tagged_worker_name:$tagged_mins:$tagged_activity}
									{if !empty($source_ext_id)}
										{assign var=source value=$sources.$source_ext_id}
										{if !empty($source)}<small>(<a href="{$source->getLink($source_id)}">{$source->getLinkText($source_id)}</a>)</small>{/if}
									{/if}
								</td>
							</tr>
							{if !empty($time_entry.notes)}
								<tr>
									<td></td>
									<td><i>{$translate->_('answernet.ui.reports.notes')} {$time_entry.notes}</i></td>
								</tr>
							{/if}
						{/if}
					{/if}
				{/foreach}
				<tr>
					<td colspan="2">
						<h2><b>{$translate->_('answernet.ui.reports.subtotal')} {$tagged_activity}: {$worker_entry.mins.$current_an} {$translate->_('common.minutes')|lower}</b></h2>
					</td>
				</tr>	
				{assign var=current_an value="ZZZZZZ"}
			{/if}
			<tr>
				<td colspan="2">
				<h1><span style="margin-bottom:10px;"><b>{$translate->_('answernet.ui.reports.total')}</b>
				<span style="margin-bottom:10px;"><b>{$worker_entry.mins.total} {$translate->_('common.minutes')|lower}</b></span></h1>
				</td>
			</tr>	
		</table>
		</div>
		<br/>
	{/foreach}
{/if}

