{strip}
<div class="listing stock">
	<header>
		<div class="floaticon hidden-print">
			{if $gBitUser->hasPermission('p_stock_create')}
				<a href="{$smarty.const.STOCK_PKG_URL}edit_component.php">{biticon ipackage="icons" iname="kt-add-filters" iexplain="Create Component"}</a>
			{/if}
			<form class="minifind" action="{$smarty.const.STOCK_PKG_URL}list_components.php" method="get">
				<input type="hidden" name="filter_submitted" value="1" />
				<div class="form-inline">
					<div class="form-group">
						<input class="form-control input-sm" type="text" name="find" placeholder="{tr}Components{/tr}" value="{$smarty.request.find|escape}" />
					</div>
					<div class="form-group">
						<label class="checkbox-inline">
							<input type="checkbox" name="hide_kitlocker" value="1"{if $hideKitlocker} checked="checked"{/if} /> {tr}Hide kitlocker{/tr}
						</label>
					</div>
					<button type="submit" class="btn btn-default btn-sm">{tr}Search{/tr}</button>
				</div>
			</form>
		</div>
		<h1>{tr}Components{/tr}{if $gQueryUserId} {tr}by{/tr} {displayname user_id=$gQueryUserId}{/if}</h1>
	</header>

	<section class="body">
		{if $gBitSystem->isFeatureActive('stock_item_list_creator') || $gBitSystem->isFeatureActive('stock_item_list_date') || $gBitSystem->isFeatureActive('stock_item_list_hits')}
			<ul class="list-inline sortby">
				<li>{biticon ipackage="icons" iname="go-next" iexplain="sort by" iforce="icon"}</li>
				{if $gBitSystem->isFeatureActive('stock_item_list_creator')}
					<li>{smartlink ititle="Creator" isort=$gBitSystem->getConfig('users_display_name') icontrol=$listInfo}</li>
				{/if}
				{if $gBitSystem->isFeatureActive('stock_item_list_date')}
					<li>{smartlink ititle="Created" isort="created" icontrol=$listInfo}</li>
				{/if}
				{if $gBitSystem->isFeatureActive('stock_item_list_hits')}
					<li>{smartlink ititle="Hits" isort="hits" icontrol=$listInfo}</li>
				{/if}
			</ul>
		{/if}

		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>{smartlink ititle="Name" isort="title" idefault=1 icontrol=$listInfo}</th>
					{if $gBitSystem->isFeatureActive('stock_item_list_desc')}<th>{smartlink ititle="Description" isort="data" icontrol=$listInfo}</th>{/if}
					{if $gBitSystem->isFeatureActive('stock_item_list_date')}<th>{tr}Created{/tr}</th>{/if}
					{if $gBitSystem->isFeatureActive('stock_item_list_creator')}<th>{tr}Creator{/tr}</th>{/if}
					{if $gBitSystem->isFeatureActive('stock_item_list_hits')}<th>{tr}Hits{/tr}</th>{/if}
					{if $gBitUser->hasPermission('p_stock_update')}<th></th>{/if}
				</tr>
			</thead>
			<tbody>
				{foreach from=$componentList item=comp}
					<tr>
						<td><a href="{$comp.display_url|escape}">{$comp.title|escape}</a></td>
						{if $gBitSystem->isFeatureActive('stock_item_list_desc')}<td>{$comp.data|truncate:80|escape}</td>{/if}
						{if $gBitSystem->isFeatureActive('stock_item_list_date')}<td>{$comp.created|bit_short_date}</td>{/if}
						{if $gBitSystem->isFeatureActive('stock_item_list_creator')}<td>{displayname hash=$comp nolink=true}</td>{/if}
						{if $gBitSystem->isFeatureActive('stock_item_list_hits')}<td>{$comp.hits}</td>{/if}
						{if $gBitUser->hasPermission('p_stock_update')}<td><a href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$comp.content_id}">{biticon ipackage="icons" iname="edit" iexplain="Edit"}</a></td>{/if}
					</tr>
				{foreachelse}
					<tr><td colspan="5" class="norecords">{tr}No components found.{/tr}</td></tr>
				{/foreach}
			</tbody>
		</table>

		{pagination}

	</section>
</div>
{/strip}
