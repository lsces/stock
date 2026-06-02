{strip}
<div class="listing stock">
	<header>
		<div class="floaticon">
			{if $gBitUser->hasPermission('p_stock_create')}
				<a class="btn btn-default btn-sm" href="{$smarty.const.STOCK_PKG_URL}edit_movement.php">{tr}Add Movement{/tr}</a>
			{/if}
		</div>
		<h1>{tr}Movements{/tr}{if $componentTitle} — {$componentTitle|escape}{/if}</h1>
	</header>

	<section class="body">

		{if $componentContentId}
			<p><a class="btn btn-xs btn-default" href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$componentContentId}">&larr; {tr}Back to component{/tr}</a></p>
			<input type="hidden" name="component_content_id" value="{$componentContentId}" />
		{/if}

		{form ipackage="stock" ifile="list_movements.php" method="get"}
			<div class="form-inline" style="margin-bottom:1em">
				<div class="form-group">
					<select name="ref_type" class="form-control input-sm">
						<option value="">{tr}All types{/tr}</option>
						<option value="REQN"{if $filterType eq 'REQN'} selected="selected"{/if}>{tr}Requisition (out){/tr}</option>
						<option value="TRANS"{if $filterType eq 'TRANS'} selected="selected"{/if}>{tr}Transfer (in){/tr}</option>
						<option value="ORDER"{if $filterType eq 'ORDER'} selected="selected"{/if}>{tr}Order (in){/tr}</option>
					</select>
				</div>
				<div class="form-group">
					<input type="text" class="form-control input-sm" name="find"
						placeholder="{tr}reference...{/tr}"
						value="{$smarty.request.find|escape}" />
				</div>
				<button type="submit" class="btn btn-default btn-sm">{tr}Go{/tr}</button>
			</div>
		{/form}

		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>{smartlink ititle="Reference" isort="title"}</th>
					<th>{tr}Type{/tr}</th>
					<th>{tr}Ref{/tr}</th>
					{if $componentContentId}<th class="text-right">{tr}Qty{/tr}</th>{/if}
					<th>{tr}Received{/tr}</th>
					<th>{smartlink ititle="Date" isort="created_desc"}</th>
					<th>{tr}Creator{/tr}</th>
					{if $gBitUser->hasPermission('p_stock_create')}<th></th>{/if}
				</tr>
			</thead>
			<tbody>
				{foreach $movementList as $mov}
					<tr>
						<td><a href="{$mov.display_url|escape}">{$mov.title|escape}</a></td>
						<td>{$mov.ref_type|escape|default:'—'}</td>
						<td>{$mov.ref_key|escape}</td>
						{if $componentContentId}
							<td class="text-right">{$mov.cmp_qty|string_format:"%.0f"} {$mov.cmp_qty_type|escape}</td>
						{/if}
						<td>{if $mov.event_time}{$mov.event_time|bit_short_date}{else}—{/if}</td>
						<td>{$mov.created|bit_short_date}</td>
						<td>{$mov.real_name|default:$mov.login|escape}</td>
						{if $gBitUser->hasPermission('p_stock_create')}
							<td>
								<a class="btn btn-xs btn-default"
									href="{$smarty.const.STOCK_PKG_URL}edit_movement.php?content_id={$mov.content_id}">{tr}Edit{/tr}</a>
							</td>
						{/if}
					</tr>
				{foreachelse}
					<tr><td colspan="7" class="norecords">{tr}No movements found.{/tr}</td></tr>
				{/foreach}
			</tbody>
		</table>

		<nav>
			{pagination}
		</nav>

	</section>
</div>
{/strip}
