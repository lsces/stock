{strip}
<div class="display stock">
	<div class="header">
		<h1>{tr}MERG Parts Import{/tr}{if !$apply} ({tr}dry run{/tr}){/if}</h1>
	</div>
	<div class="body">
		<p>{tr}File{/tr}: <code>{$csvFile|escape}</code></p>

		{if $result.summary}
			<p>
				<strong>{$result.summary.stub}</strong> {if $apply}{tr}stub components created{/tr}{else}{tr}stub components would be created{/tr}{/if},
				<strong>{$result.summary.attach}</strong> {tr}existing components get supplier lines{/tr},
				<strong>{$result.summary.lines}</strong> {tr}supplier lines added{/tr},
				<strong>{$result.summary.unchanged}</strong> {tr}already up to date{/tr},
				<strong>{$result.summary.ambiguous}</strong> {tr}ambiguous (skipped){/tr}{if $result.summary.failed}, <strong>{$result.summary.failed}</strong> {tr}failed{/tr}{/if}.
				{if $result.summary.nolines}{$result.summary.nolines} {tr}parts have no usable supplier line{/tr}.{/if}
			</p>
			{if !$apply}
				<p><a class="btn btn-warning" href="?apply=1" onclick="return confirm('{tr}Import the parts list?{/tr}');">{tr}Import for real{/tr}</a></p>
			{/if}
		{/if}

		{if $result.contacts}
			<h3>{tr}Supplier contacts{/tr}</h3>
			<ul>{foreach $result.contacts as $name => $status}<li>{$name|escape}: {$status|escape}</li>{/foreach}</ul>
		{/if}

		{if $result.skippedSuppliers}
			<h3>{tr}Supplier lines skipped (no supplier set up){/tr}</h3>
			<ul>{foreach $result.skippedSuppliers as $name => $n}<li>{$name|escape}: {$n}</li>{/foreach}</ul>
		{/if}

		{if $result.errors}
			<h3>{tr}Errors{/tr}</h3>
			<ul>{foreach $result.errors as $msg}<li>{$msg|escape}</li>{/foreach}</ul>
		{/if}

		{if $result.rows}
			<table class="table table-condensed">
				<thead><tr><th>{tr}Part (long name){/tr}</th><th>{tr}Status{/tr}</th><th>{tr}Library component{/tr}</th><th>{tr}Lines added{/tr}</th></tr></thead>
				<tbody>
					{foreach $result.rows as $r}
					<tr class="{if $r.status eq 'stub'}success{elseif $r.status eq 'ambiguous' || $r.status eq 'FAILED'}danger{/if}">
						<td>{$r.part|escape}</td>
						<td>{$r.status|escape}</td>
						<td>{if $r.content_id}<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$r.content_id}">{$r.target|default:$r.content_id|escape}</a>{else}{$r.target|escape}{/if}</td>
						<td>{$r.added}/{$r.lines}{if $r.held} <small>({$r.held} {tr}held by another component{/tr})</small>{/if}</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		{/if}
	</div>
</div>
{/strip}
