{strip}
<div class="listing stock">
	<header>
		<div class="floaticon">
		</div>
		<h1>{if $groupTitle}{$groupTitle|escape}{else}{tr}Kitlocker{/tr}{/if}</h1>
		{if $stgrp}<small><a href="{$smarty.const.STOCK_PKG_URL}view_kitlocker.php">&lsaquo; {tr}Kitlocker{/tr}</a></small>{/if}
	</header>

	<section class="body">

		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>{tr}Name{/tr}</th>
					<th>{tr}Type{/tr}</th>
					<th>{tr}KLID{/tr}</th>
					<th>{tr}Price{/tr}</th>
				</tr>
			</thead>
			<tbody>
				{foreach $kitlockerItems as $item}
				<tr>
					<td>
						{if $item.content_type_guid eq 'stockassembly'}
							<a href="{$smarty.const.STOCK_PKG_URL}view_assembly.php?content_id={$item.content_id}">{$item.title|escape}</a>
						{else}
							<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$item.content_id}">{$item.title|escape}</a>
						{/if}
						{if $item.parsed_data}<br/><small class="text-muted">{$item.parsed_data|strip_tags|truncate:120}</small>{/if}
					</td>
					<td>{if $item.content_type_guid eq 'stockassembly'}{tr}Assembly{/tr}{else}{tr}Component{/tr}{/if}</td>
					<td>{$item.klid|escape}</td>
					<td>{$item.klpr|escape}</td>
				</tr>
				{foreachelse}
				<tr><td colspan="4" class="norecords">{tr}No kitlocker items found.{/tr}</td></tr>
				{/foreach}
			</tbody>
		</table>
	</section>
</div>
{/strip}
