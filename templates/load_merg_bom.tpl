{strip}
<div class="display stock">
	<div class="header">
		<h1>MERG BOM Import — Group {$group|upper|escape}{if $dryRun} (dry run){/if}</h1>
	</div>
	<div class="body">
		<p>File: <code>{$csvFile|escape}</code></p>

		{if $assemblies}
			<table class="table table-condensed">
				<thead>
					<tr>
						<th>Assembly</th>
						<th>content_id</th>
						{if $doClear}<th>Cleared</th>{/if}
						<th>{if $dryRun}Would add{else}Added{/if}</th>
						<th>Skipped</th>
					</tr>
				</thead>
				<tbody>
					{foreach $assemblies as $asmId => $asm}
					<tr>
						<td><a href="{$smarty.const.STOCK_PKG_URL}view_assembly.php?content_id={$asmId}">{$asm.title|escape}</a></td>
						<td>{$asmId}</td>
						{if $doClear}<td>{$asm.cleared}</td>{/if}
						<td class="{if $asm.loaded gt 0}success{/if}">{$asm.loaded}</td>
						<td>{$asm.skipped}</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		{/if}

		{if $dryRun}
			<p>
				<a href="?group={$group|escape:'url'}">Run for real</a>
				&nbsp;|&nbsp;
				<a href="?group={$group|escape:'url'}&amp;clear=1">Run with clear first</a>
			</p>
		{/if}

		{if $errors}
			<h3>Errors / warnings</h3>
			<ul>
				{foreach $errors as $msg}<li>{$msg|escape}</li>{/foreach}
			</ul>
		{/if}
	</div>
</div>
{/strip}
