{strip}
<div class="listing stock">
	<header>
		<div class="floaticon hidden-print">
			<button type="button" class="btn btn-link" onclick="window.print()">{biticon ipackage="icons" iname="document-print" iexplain="Print"}</button>
			{if $showShortages}
			<a class="btn btn-link"
				href="{$smarty.const.STOCK_PKG_URL}list_stock.php?shortages=1{if $assemblyContentId}&amp;assembly_content_id={$assemblyContentId|escape:'url'}&amp;kit_count={$kitCount|escape:'url'}{/if}{if $filterUserId}&amp;user_id={$filterUserId|escape:'url'}{/if}&amp;format=csv">{biticon ipackage="icons" iname="text-csv" iexplain="Download CSV"}</a>
			{if $gBitUser->hasPermission('p_stock_create')}
			<a class="btn btn-link"
				href="{$smarty.const.STOCK_PKG_URL}add_order.php?shortages=1{if $assemblyContentId}&amp;assembly_content_id={$assemblyContentId|escape:'url'}&amp;kit_count={$kitCount|escape:'url'}{/if}{if $find}&amp;find={$find|escape:'url'}{/if}{if $filterUserId}&amp;user_id={$filterUserId|escape:'url'}{/if}">{biticon ipackage="icons" iname="view-task-add" iexplain="Create Order"}</a>
			{/if}
			{/if}
		<form class="minifind" action="{$smarty.const.STOCK_PKG_URL}list_stock.php" method="get">
			{if $filterUserId}<input type="hidden" name="user_id" value="{$filterUserId|escape}" />{/if}
			<div class="form-inline">
				<div class="form-group">
					<input type="hidden" name="assembly_content_id" id="ls_asm_id" value="{$assemblyContentId|default:''|escape}" />
					<div style="position:relative;display:inline-block;vertical-align:top">
						<input type="text" class="form-control input-sm" id="ls_asm_search"
							autocomplete="off" placeholder="{tr}All components{/tr}"
							value="{$assemblyTitle|escape}" />
						<ul id="ls_asm_dropdown" class="dropdown-menu"
							style="display:none;position:absolute;width:100%;z-index:1000;max-height:220px;overflow-y:auto"></ul>
					</div>
				</div>
				<div class="form-group">
					<input type="text" class="form-control input-sm" name="find"
						placeholder="{tr}component name...{/tr}"
						value="{$find|escape}" />
				</div>
				{if $showBom}
				<div class="form-group">
					<label>{tr}Kits{/tr}</label>
					<input type="number" class="form-control input-sm" name="kit_count"
						min="0" step="1" style="width:5em"
						value="{$kitCount|escape}" />
				</div>
				{/if}
				<div class="form-group">
					<label class="checkbox-inline">
						<input type="checkbox" name="shortages" value="1"
							{if $showShortages} checked="checked"{/if} /> {tr}Shortages only{/tr}
					</label>
				</div>
				<button type="submit" class="btn btn-default btn-sm">{tr}Go{/tr}</button>
				{if $showBom && $gBitUser->hasPermission('p_stock_create')}
					<a class="btn btn-warning btn-sm"
						href="{$smarty.const.STOCK_PKG_URL}add_prebuild.php?assembly_content_id={$assemblyContentId}&amp;kit_count={$kitCount}{if $filterUserId}&amp;user_id={$filterUserId}{/if}">{tr}Create Prebuild{/tr}</a>
				{/if}
			</div>
		</form>
		</div>
		<h1>{tr}Stock Levels{/tr}{if $assemblyTitle} — {$assemblyTitle|escape}{/if}</h1>
		<small>
			<a href="{$smarty.const.STOCK_PKG_URL}list_stock.php">{tr}Stock Levels{/tr}</a>
			{if $filterUserName}&rsaquo; <a href="{$smarty.const.STOCK_PKG_URL}list_stock.php?user_id={$filterUserId}">{$filterUserName|escape}</a>{/if}
		</small>
	</header>

	<section class="body">

		{if $stockList}
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>{tr}Component{/tr}</th>
					<th>{tr}Description{/tr}</th>
					<th>{tr}Part No.{/tr}</th>
					{if $showBom}<th class="text-right">{tr}BOM Qty{/tr}</th>{/if}
					<th>{tr}Type{/tr}</th>
					<th class="text-right">{tr}Stock{/tr}</th>
					{if $showBom}<th class="text-right">{tr}Remaining{/tr}</th>{/if}
				</tr>
			</thead>
			<tbody>
				{assign var=lastBomGroup value=-1}
				{foreach from=$stockList item=comp}
					{if $showBom && !$showShortages && $comp.bom_group !== $lastBomGroup}
					<tr class="active">
						<th colspan="10">{tr}Group{/tr} {$comp.bom_group}</th>
					</tr>
					{assign var=lastBomGroup value=$comp.bom_group}
					{/if}
					{foreach from=$comp.stock key=qtype item=row name=stockRow}
					{if $showBom}{assign var=remaining value=$row.level-($row.bom_qty*$kitCount)}{/if}
					<tr{if $row.level < 0 || ($showBom && $remaining < 0)} class="danger"{elseif $row.level == 0} class="warning"{/if}>
						{if $smarty.foreach.stockRow.first}
						<td rowspan="{$comp.stock|@count}">
							<a href="{$comp.display_url|escape}">{$comp.title|escape}</a>
						</td>
						<td rowspan="{$comp.stock|@count}">{$comp.data|escape}</td>
						<td rowspan="{$comp.stock|@count}">{$comp.part_number|escape}</td>
						{/if}
						{if $showBom}<td class="text-right">{if $qtype eq 'PRT' && $row.part_size > 0}{math equation="b*k/p" b=$row.bom_qty k=$kitCount p=$row.part_size format="%.2f"}{elseif $qtype eq 'SHT'}{math equation="b*k" b=$row.bom_qty k=$kitCount format="%.2f"}{else}{math equation="b*k" b=$row.bom_qty k=$kitCount format="%.0f"}{/if}</td>{/if}
						<td>{$qtype|escape}</td>
						<td class="text-right">{if $qtype eq 'PRT' && $row.part_size > 0}{math equation="l/p" l=$row.level p=$row.part_size format="%.2f"}{elseif $qtype eq 'SHT'}{$row.level|string_format:"%.2f"}{else}{$row.level|string_format:"%.0f"}{/if}</td>
						{if $showBom}
							<td class="text-right{if $remaining < 0} text-danger{/if}">{if $qtype eq 'PRT' && $row.part_size > 0}{math equation="r/p" r=$remaining p=$row.part_size format="%.2f"}{elseif $qtype eq 'SHT'}{$remaining|string_format:"%.2f"}{else}{$remaining|string_format:"%.0f"}{/if}</td>
						{/if}
					</tr>
					{/foreach}
				{/foreach}
			</tbody>
		</table>
		{else}
			<p class="muted">{tr}No stock records found.{/tr}</p>
		{/if}
		{pagination user_id=$filterUserId|default:'' find=$find|default:''}

	</section>
</div>
{/strip}
<script>
(function($) {
	var items   = {$assemblyListJson};
	var $input  = $('#ls_asm_search');
	var $hidden = $('#ls_asm_id');
	var $dd     = $('#ls_asm_dropdown');

	$input.on('input', function() {
		var q = this.value.toLowerCase().trim();
		$hidden.val('');
		$dd.hide().empty();
		if (!q) return;
		var matched = items.filter(function(i) {
			return i.text.toLowerCase().indexOf(q) !== -1 || (i.klid && i.klid.toLowerCase().indexOf(q) !== -1);
		});
		if (!matched.length) return;
		matched.forEach(function(i) {
			var label = i.text + (i.klid ? ' (' + i.klid + ')' : '');
			$dd.append($('<li>').append($('<a>').attr('href','#').data('id', i.id).data('text', i.text).text(label)));
		});
		$dd.show();
	});

	$(document).on('mousedown', '#ls_asm_dropdown a', function(e) {
		e.preventDefault();
		$input.val($(this).data('text'));
		$hidden.val($(this).data('id'));
		$dd.hide().empty();
	});

	$input.on('blur', function() { setTimeout(function() { $dd.hide(); }, 150); });

	$input.on('keydown', function(e) {
		if (!$dd.is(':visible')) return;
		var $links = $dd.find('a'), idx = $links.index($dd.find('li.active a'));
		if (e.key === 'ArrowDown') { e.preventDefault(); $links.parent().removeClass('active'); $links.eq(idx + 1 < $links.length ? idx + 1 : 0).parent().addClass('active'); }
		else if (e.key === 'ArrowUp') { e.preventDefault(); $links.parent().removeClass('active'); $links.eq(idx > 0 ? idx - 1 : $links.length - 1).parent().addClass('active'); }
		else if (e.key === 'Enter') { var $a = $dd.find('li.active a'); if ($a.length) { e.preventDefault(); $a.trigger('mousedown'); } }
		else if (e.key === 'Escape') { $dd.hide(); }
	});
}(jQuery));
</script>
