{strip}
<div class="display stock">
	<div class="floaticon">
		{if $gBitUser->hasPermission('p_stock_create')}
			<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_movement.php?movement_id={$gContent->mMovementId}">{booticon iname="fa-pen-to-square" iexplain="Edit Movement"}</a>
		{/if}
	</div>

	<div class="header">
		<h1>{$gContent->getTitle()|escape}</h1>
	</div>

	<div class="body">

		<dl class="dl-horizontal">
			<dt>{tr}Direction{/tr}</dt>
			<dd>{if $gContent->mInfo.direction eq 'I'}{tr}IN{/tr}{else}{tr}OUT{/tr}{/if}</dd>
			<dt>{tr}Status{/tr}</dt>
			<dd>{$gContent->mInfo.status|escape|capitalize}</dd>
			<dt>{tr}Created{/tr}</dt>
			<dd>{$gContent->mInfo.created|bit_short_datetime} {tr}by{/tr} {$gContent->mInfo.creator|escape}</dd>
			{if $gContent->mInfo.last_modified neq $gContent->mInfo.created}
				<dt>{tr}Modified{/tr}</dt>
				<dd>{$gContent->mInfo.last_modified|bit_short_datetime} {tr}by{/tr} {$gContent->mInfo.editor|escape}</dd>
			{/if}
		</dl>

		{if $gContent->mInfo.items}
			<table class="table table-condensed table-striped">
				<thead>
					<tr>
						<th>{smartlink ititle="Pos"       isort="item_position" ifile="view_movement.php" ipackage="stock" idefault=1 movement_id=$gContent->mMovementId}</th>
						<th>{smartlink ititle="Component" isort="title"         ifile="view_movement.php" ipackage="stock"            movement_id=$gContent->mMovementId}</th>
						<th>{tr}Qty{/tr}</th>
						<th>{tr}Type{/tr}</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$gContent->mInfo.items item=item}
						<tr>
							<td>{$item.item_position|escape}</td>
							<td>{$item.title|escape}</td>
							<td>{$item.quantity_value|escape}</td>
							<td>{$item.quantity_item|escape}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{else}
			<p class="muted">{tr}No items.{/tr}</p>
		{/if}

	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
