{strip}
{if $gContent->getLayout() == 'simple_list'}
	<table class="table data">
		<caption>{tr}Components{/tr} <span class="total">[ {$gContent->mItems|count|default:0} ]</span></caption>
		<tr>
			<th>{smartlink ititle=Name isort=title icontrol=$listInfo}</th>
			{if $gBitSystem->isFeatureActive( 'stock_item_list_date' ) || $gBitSystem->isFeatureActive( 'stock_item_list_creator' )}
				<th>{smartlink ititle=Created isort=created iorder=desc idefault=1 icontrol=$listInfo}</th>
			{/if}
			{if $gBitSystem->isFeatureActive( 'stock_item_list_hits' )}
				<th>{smartlink ititle=Views isort="lch.hits" icontrol=$listInfo}</th>
			{/if}
			<th>{tr}Actions{/tr}</th>
		</tr>
		{foreach from=$gContent->mItems item=galItem}
			<tr class="{cycle values="odd,even"}">
				<td>
					<h3><a href="{$galItem->getDisplayUrl()}">{$galItem->getTitle()|escape}</a></h3>
					{if $gBitSystem->isFeatureActive( 'stock_item_list_desc' ) && $galItem->mInfo.data}
						{$galItem->mInfo.parsed_data}
					{/if}
					{if $gBitSystem->isFeatureActive( 'stock_item_list_attid' )}
						<small>{$galItem->mInfo.wiki_plugin_link}</small>
					{/if}
				</td>
				{if $gBitSystem->isFeatureActive( 'stock_item_list_date' ) || $gBitSystem->isFeatureActive( 'stock_item_list_creator' )}
					<td>
						{if $gBitSystem->isFeatureActive( 'stock_item_list_date' )}
							{$galItem->mInfo.created|bit_short_date}<br />
						{/if}
						{if $gBitSystem->isFeatureActive( 'stock_item_list_creator' )}
							{tr}by{/tr}: {displayname hash=$galItem->mInfo}
						{/if}
					</td>
				{/if}
				{if $gBitSystem->isFeatureActive( 'stock_item_list_hits' )}
					<td class="text-right">{$galItem->mInfo.hits|default:"{tr}none{/tr}"}</td>
				{/if}
				<td class="actionicon">
					{if $gContent->isOwner( $galItem->mInfo ) || $gBitUser->isAdmin()}
						<a href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$galItem->mInfo.content_id}">{booticon iname="fa-pen-to-square" iexplain="Edit"}</a>
						<a href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$galItem->mInfo.content_id}&amp;delete=1">{booticon iname="fa-trash" iexplain="Remove"}</a>
					{/if}
				</td>
			</tr>
		{foreachelse}
			<tr><td class="norecords" colspan="4">{tr}This assembly has no components.{/tr}</td></tr>
		{/foreach}
	</table>
{else}
	<div class="component-list">
		{foreach from=$gContent->mItems item=galItem key=itemContentId}
			{box class="box {$galItem->mInfo.content_type_guid}"}
				{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$galItem->mInfo type=mini}
				<h2><a href="{$galItem->getDisplayUrl()|escape}">{$galItem->getTitle()|escape}</a></h2>
				{if $galItem->mInfo.data}
					<p>{$galItem->mInfo.data|escape|truncate:120}</p>
				{/if}
			{/box}
		{foreachelse}
			<div class="norecords">{tr}This assembly has no components.{/tr}</div>
		{/foreach}
	</div>
	<div class="clear"></div>
{/if}
{/strip}
