{strip}
<div class="listing stock">
	<header>
		<h1>{tr}Stock Levels{/tr}{if $assemblyTitle} — {$assemblyTitle|escape}{/if}</h1>
	</header>

	<section class="body">

		{form ipackage="stock" ifile="list_stock.php" method="get"}
			<div class="form-inline" style="margin-bottom:1em">
				<div class="form-group">
					<select name="assembly_content_id" class="form-control input-sm">
						<option value="">{tr}All components{/tr}</option>
						{foreach from=$assemblyList item=asm}
							<option value="{$asm.content_id|escape}"
								{if $assemblyContentId == $asm.content_id} selected="selected"{/if}>
								{$asm.title|escape}
							</option>
						{/foreach}
					</select>
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
						min="1" step="1" style="width:5em"
						value="{$kitCount|escape}" />
				</div>
				{/if}
				<div class="form-group">
					<label class="checkbox-inline">
						<input type="checkbox" name="show_zero" value="1"
							{if $showZero} checked="checked"{/if} /> {tr}Show zero stock{/tr}
					</label>
				</div>
				<button type="submit" class="btn btn-default btn-sm">{tr}Go{/tr}</button>
			</div>
		{/form}

		{if $stockList}
		<table class="table table-striped table-hover table-condensed">
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
				{foreach from=$stockList item=comp}
					{foreach from=$comp.stock key=qtype item=row name=stockRow}
					<tr{if $row.level < 0} class="danger"{elseif $row.level == 0} class="warning"{/if}>
						{if $smarty.foreach.stockRow.first}
						<td rowspan="{$comp.stock|@count}">
							<a href="{$comp.display_url|escape}">{$comp.title|escape}</a>
						</td>
						<td rowspan="{$comp.stock|@count}">{$comp.data|escape}</td>
						<td rowspan="{$comp.stock|@count}">{$comp.part_number|escape}</td>
						{/if}
						{if $showBom}<td class="text-right">{math equation="b*k" b=$row.bom_qty k=$kitCount format="%.3g"}</td>{/if}
						<td>{$qtype|escape}</td>
						<td class="text-right">{$row.level|string_format:"%.3g"}</td>
						{if $showBom}
							{assign var=remaining value=$row.level-($row.bom_qty*$kitCount)}
							<td class="text-right{if $remaining < 0} text-danger{/if}">{$remaining|string_format:"%.3g"}</td>
						{/if}
					</tr>
					{/foreach}
				{/foreach}
			</tbody>
		</table>
		{else}
			<p class="muted">{tr}No stock records found.{/tr}</p>
		{/if}

	</section>
</div>
{/strip}
