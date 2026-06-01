{strip}
<div class="listing stock">
	<header>
		<h1>{tr}Stock Levels{/tr}</h1>
	</header>

	<section class="body">

		{form ipackage="stock" ifile="list_stock.php" method="get"}
			<div class="form-inline" style="margin-bottom:1em">
				<div class="form-group">
					<input type="text" class="form-control input-sm" name="find"
						placeholder="{tr}component name...{/tr}"
						value="{$find|escape}" />
				</div>
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
					<th>{tr}Type{/tr}</th>
					<th class="text-right">{tr}Stock{/tr}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$stockList item=comp}
					{foreach from=$comp.stock key=qtype item=level name=stockRow}
					<tr{if $level < 0} class="danger"{elseif $level == 0} class="warning"{/if}>
						{if $smarty.foreach.stockRow.first}
						<td rowspan="{$comp.stock|@count}">
							<a href="{$comp.display_url|escape}">{$comp.title|escape}</a>
						</td>
						<td rowspan="{$comp.stock|@count}">{$comp.data|escape}</td>
						<td rowspan="{$comp.stock|@count}">{$comp.part_number|escape}</td>
						{/if}
						<td>{$qtype|escape}</td>
						<td class="text-right">{$level|string_format:"%.3g"}</td>
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
