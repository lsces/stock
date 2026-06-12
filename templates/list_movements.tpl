{strip}
<div class="listing stock">
	<header>
		<div class="floaticon hidden-print">
			<button type="button" class="btn btn-link" onclick="window.print()">{biticon ipackage="icons" iname="document-print" iexplain="Print"}</button>
			{if $gBitUser->hasPermission('p_stock_create')}
				<a href="{$smarty.const.STOCK_PKG_URL}add_requisition.php">{biticon ipackage="icons" iname="list-add" iexplain="Add Requisition"}</a>
				<a href="{$smarty.const.STOCK_PKG_URL}edit_movement.php">{biticon ipackage="icons" iname="view-task-add" iexplain="Add Movement"}</a>
			{/if}
			<form class="minifind" action="{$smarty.const.STOCK_PKG_URL}list_movements.php" method="get">
				{if $componentContentId}<input type="hidden" name="component_content_id" value="{$componentContentId|escape}" />{/if}
				<div class="form-inline">
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
			</form>
		</div>
		<h1>{tr}Movements{/tr}{if $componentTitle} — {$componentTitle|escape}{/if}</h1>
	</header>

	<section class="body">

		{if $componentContentId}
			<p><a class="btn btn-xs btn-default" href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$componentContentId}">&larr; {tr}Back to component{/tr}</a></p>
		{/if}

		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>{smartlink ititle="Reference" isort="title"}</th>
					<th>{tr}Type{/tr}</th>
					{if $componentContentId}<th class="text-right">{tr}Qty{/tr}</th>{/if}
					<th>{smartlink ititle="Ordered" isort="ref_start_date" ifile="list_movements.php" ipackage="stock"}</th>
					<th>{smartlink ititle="Received" isort="event_time" ifile="list_movements.php" ipackage="stock"}</th>
					<th>{smartlink ititle="Date" isort="created_desc"}</th>
					<th>{tr}Creator{/tr}</th>
					{if $gBitUser->hasPermission('p_stock_update')}<th></th>{/if}
				</tr>
			</thead>
			<tbody>
				{foreach $movementList as $mov}
					<tr>
						<td><a href="{$mov.display_url|escape}">{$mov.title|escape}</a></td>
						<td>{$mov.ref_type|escape|default:'—'}</td>
						{if $componentContentId}
							<td class="text-right">{if $mov.cmp_qty_type eq 'PRT' && $partSize > 0}{math equation="q/p" q=$mov.cmp_qty p=$partSize format="%.2f"}{elseif $mov.cmp_qty_type eq 'SHT'}{$mov.cmp_qty|string_format:"%.2f"}{else}{$mov.cmp_qty|string_format:"%.0f"}{/if} {$mov.cmp_qty_type|escape}</td>
						{/if}
						<td>{if $mov.ref_start_date}{$mov.ref_start_date|bit_short_date}{else}—{/if}</td>
						<td>{if $mov.event_time}{$mov.event_time|bit_short_date}{else}—{/if}</td>
						<td>{$mov.created|bit_short_date}</td>
						<td>{$mov.real_name|default:$mov.login|escape}</td>
						{if $gBitUser->hasPermission('p_stock_update')}
							<td>
								<a href="{$smarty.const.STOCK_PKG_URL}edit_movement.php?content_id={$mov.content_id}">{biticon ipackage="icons" iname="edit" iexplain="Edit"}</a>
							</td>
						{/if}
					</tr>
				{foreachelse}
					<tr><td colspan="7" class="norecords">{tr}No movements found.{/tr}</td></tr>
				{/foreach}
			</tbody>
		</table>

		<nav>
			{pagination ref_type=$filterType find=$smarty.request.find|default:'' component_content_id=$componentContentId|default:'' assembly_content_id=$assemblyContentId|default:''}
		</nav>

	</section>
</div>
{/strip}
