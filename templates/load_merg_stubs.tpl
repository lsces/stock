{strip}
<div class="display stock">
	<div class="header">
		<h1>MERG Stub Import — Group {$group|upper|escape}{if $dryRun} (dry run){/if}</h1>
	</div>
	<div class="body">
		<p>File: <code>{$csvFile|escape}</code></p>
		<p>
			<strong>{$created}</strong> {if $doUpdate}{if $dryRun}would be updated{else}updated{/if}{else}{if $dryRun}would be created{else}created{/if}{/if}.
			<strong>{$skipped}</strong> {if $doUpdate}already renamed (skipped){else}already exist (skipped){/if}.
		</p>
		{if $dryRun}
			<p><a href="?group={$group|escape:'url'}{if $doUpdate}&amp;update=1{/if}">Run for real (remove &amp;dry=1)</a></p>
		{/if}

		{if $errors}
			<h3>Errors</h3>
			<ul>
				{foreach $errors as $msg}<li>{$msg|escape}</li>{/foreach}
			</ul>
		{/if}

		{if $rows}
			<table class="table table-condensed">
				<thead>
					<tr>
						<th>Order code</th>
						<th>Component (from spreadsheet)</th>
						<th>Status</th>
						<th>content_id</th>
					</tr>
				</thead>
				<tbody>
					{foreach $rows as $r}
					<tr class="{if $r.status eq 'created' or $r.status eq 'would create'}success{elseif $r.status eq 'error'}danger{/if}">
						<td><code>{$r.code|escape}</code></td>
						<td>{$r.component|escape}</td>
						<td>{$r.status|escape}</td>
						<td>{if $r.content_id}<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$r.content_id}">{$r.content_id}</a>{else}—{/if}</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		{/if}
	</div>
</div>
{/strip}
