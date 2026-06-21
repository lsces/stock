{strip}
<div class="listing stock">
	<header>
		<div class="floaticon hidden-print">
			<button type="button" class="btn btn-link" onclick="window.print()">{biticon ipackage="icons" iname="document-print" iexplain="Print"}</button>
			{if $gBitUser->hasPermission('p_stock_create')}
				<a href="{$smarty.const.STOCK_PKG_URL}add_prebuild.php">{biticon ipackage="icons" iname="package-x-generic" iexplain="Add Prebuild"}</a>
				<a href="{$smarty.const.STOCK_PKG_URL}edit_movement.php">{biticon ipackage="icons" iname="view-task-add" iexplain="Add Movement"}</a>
			{/if}
			<form class="minifind" action="{$smarty.const.STOCK_PKG_URL}list_movements.php" method="get">
				{if $partContentId}<input type="hidden" name="part_content_id" value="{$partContentId|escape}" />{/if}
				{if $filterUserId}<input type="hidden" name="user_id" value="{$filterUserId|escape}" />{/if}
				<div class="form-inline">
					<div class="form-group">
						<select name="ref_type" class="form-control input-sm">
							<option value="">{tr}All types{/tr}</option>
							<option value="REQN"{if $filterType eq 'REQN'} selected="selected"{/if}>{tr}Requisition (out){/tr}</option>
							<option value="PBLD"{if $filterType eq 'PBLD'} selected="selected"{/if}>{tr}Prebuild (out){/tr}</option>
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
		<h1>{tr}Movements{/tr}{if $partTitle} — {$partTitle|escape}{/if}</h1>
		<small>
			<a href="{$smarty.const.STOCK_PKG_URL}list_movements.php">{tr}Movements{/tr}</a>
			{if $partTitle}&rsaquo; <a href="{$smarty.const.STOCK_PKG_URL}view_{$partType}.php?content_id={$partContentId}">{$partTitle|escape}</a>{/if}
			{if $filterUserName}&rsaquo; <a href="{$smarty.const.STOCK_PKG_URL}list_movements.php?user_id={$filterUserId}">{$filterUserName|escape}</a>{/if}
		</small>
	</header>

	<section class="body">

		{if $partContentId}
			<p><a class="btn btn-xs btn-default" href="{$smarty.const.STOCK_PKG_URL}view_{$partType}.php?content_id={$partContentId}">&larr; {if $partType eq 'assembly'}{tr}Back to assembly{/tr}{else}{tr}Back to component{/tr}{/if}</a></p>
		{/if}

		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>{smartlink ititle="Reference" isort="title" icontrol=$listInfo}</th>
					<th>{tr}Type{/tr}</th>
					{if $partContentId}<th class="text-right">{tr}Qty{/tr}</th>{/if}
					<th>{smartlink ititle="Ordered" isort="ref_start_date" ifile="list_movements.php" ipackage="stock" icontrol=$listInfo}</th>
					<th>{smartlink ititle="Received" isort="event_time" ifile="list_movements.php" ipackage="stock" icontrol=$listInfo}</th>
					<th>{smartlink ititle="Date" isort="created_desc" icontrol=$listInfo}</th>
					<th>{tr}Creator{/tr}</th>
					{if $gBitUser->hasPermission('p_stock_update')}<th></th>{/if}
				</tr>
			</thead>
			<tbody>
				{foreach $movementList as $mov}
					<tr>
						<td><a href="{$mov.display_url|escape}">{$mov.title|escape}</a></td>
						<td>{$mov.ref_type|escape|default:'—'}</td>
						{if $partContentId}
							{if $partType eq 'assembly'}
								<td class="text-right">{$mov.part_qty|string_format:"%.0f"}</td>
							{else}
								<td class="text-right">{if $mov.part_qty_type eq 'PRT' && $partSize > 0}{math equation="q/p" q=$mov.part_qty p=$partSize format="%.2f"}{elseif $mov.part_qty_type eq 'SHT'}{$mov.part_qty|string_format:"%.2f"}{else}{$mov.part_qty|string_format:"%.0f"}{/if} {$mov.part_qty_type|escape}</td>
							{/if}
						{/if}
						<td>{if $mov.ref_start_date}{$mov.ref_start_date|bit_short_date}{else}—{/if}</td>
						<td>{if $mov.event_time}{$mov.event_time|bit_short_date}{else}—{/if}</td>
						<td>{$mov.created|bit_short_date}</td>
						<td><a href="{$smarty.const.STOCK_PKG_URL}list_movements.php?user_id={$mov.user_id}">{$mov.real_name|default:$mov.login|escape}</a></td>
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
			{pagination ref_type=$filterType find=$smarty.request.find|default:'' part_content_id=$partContentId|default:'' user_id=$filterUserId|default:''}
		</nav>

	</section>
</div>
{/strip}
