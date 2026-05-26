{strip}
<div class="listing stock">
	<header>
		<div class="floaticon">
			{minifind prompt="Components"}
		</div>
		<h1>{tr}Components{/tr}{if $gQueryUserId} {tr}by{/tr} {displayname user_id=$gQueryUserId}{/if}</h1>
	</header>

	<section class="body">
		<ul class="list-inline sortby">
			<li>{booticon iname="icon-circle-arrow-right" ipackage="icons" iexplain="sort by" iforce="icon"}</li>
			<li>{smartlink ititle="Name" isort="title"}</li>
			{if $gBitSystem->isFeatureActive('stock_item_list_creator')}
				<li>{smartlink ititle="Creator" isort=$gBitSystem->getConfig('users_display_name')}</li>
			{/if}
			{if $gBitSystem->isFeatureActive('stock_item_list_date')}
				<li>{smartlink ititle="Created" isort="created"}</li>
			{/if}
			{if $gBitSystem->isFeatureActive('stock_item_list_hits')}
				<li>{smartlink ititle="Hits" isort="hits"}</li>
			{/if}
		</ul>

		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>{tr}Name{/tr}</th>
					{if $gBitSystem->isFeatureActive('stock_item_list_desc')}<th>{tr}Description{/tr}</th>{/if}
					{if $gBitSystem->isFeatureActive('stock_item_list_date')}<th>{tr}Created{/tr}</th>{/if}
					{if $gBitSystem->isFeatureActive('stock_item_list_creator')}<th>{tr}Creator{/tr}</th>{/if}
					{if $gBitSystem->isFeatureActive('stock_item_list_hits')}<th>{tr}Hits{/tr}</th>{/if}
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
					</tr>
				{foreachelse}
					<tr><td colspan="5" class="norecords">{tr}No components found.{/tr}</td></tr>
				{/foreach}
			</tbody>
		</table>

		<nav>
			{pagination}
		</nav>

	</section>
</div>
{/strip}
