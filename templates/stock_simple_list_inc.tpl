{strip}
{include file="bitpackage:stock/assembly_nav.tpl"}
<div class="display stock">
	<div class="header">
		{include file="bitpackage:stock/assembly_icons_inc.tpl"}
		<h1>{$gContent->getTitle()|escape}</h1>
	</div>

	<div class="body">
		{formfeedback success=$stockSuccess error=$stockErrors warning=$stockWarnings}

		{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$gContent->mInfo}
		{if $gContent->mInfo.data}
			<p>{$gContent->mInfo.data|escape}</p>
		{/if}

		<table class="table data">
			<caption>{tr}Components{/tr} <span class="total">[ {$listInfo.total_records|default:0} ]</span></caption>
			<tr>
				<th>{smartlink ititle=Name isort=title icontrol=$listInfo}</th>
				{if $gBitSystem->isFeatureActive('stock_item_list_date') || $gBitSystem->isFeatureActive('stock_item_list_creator')}
					<th>{smartlink ititle=Created isort=created iorder=desc idefault=1 icontrol=$listInfo}</th>
				{/if}
				{if $gBitSystem->isFeatureActive('stock_item_list_hits')}
					<th>{smartlink ititle=Views isort="lch.hits" icontrol=$listInfo}</th>
				{/if}
				<th>{tr}Actions{/tr}</th>
			</tr>
			{foreach from=$gContent->mItems item=galItem}
				<tr class="{cycle values="odd,even"}">
					<td>
						<h3><a href="{$galItem->getDisplayUrl()}">{$galItem->getTitle()|escape}</a></h3>
						{if $gBitSystem->isFeatureActive('stock_item_list_desc') && $galItem->mInfo.data}
							{$galItem->mInfo.parsed_data}
						{/if}
						{if $gBitSystem->isFeatureActive('stock_item_list_attid')}
							<small>{$galItem->mInfo.wiki_plugin_link}</small>
						{/if}
					</td>
					{if $gBitSystem->isFeatureActive('stock_item_list_date') || $gBitSystem->isFeatureActive('stock_item_list_creator')}
						<td>
							{if $gBitSystem->isFeatureActive('stock_item_list_date')}
								{$galItem->mInfo.created|bit_short_date}<br />
							{/if}
							{if $gBitSystem->isFeatureActive('stock_item_list_creator')}
								{tr}by{/tr}: {displayname hash=$galItem->mInfo}
							{/if}
						</td>
					{/if}
					{if $gBitSystem->isFeatureActive('stock_item_list_hits')}
						<td class="text-right">{$galItem->mInfo.hits|default:"{tr}none{/tr}"}</td>
					{/if}
					<td class="actionicon">
						<a href="{$galItem->getDisplayUrl()}">{biticon ipackage="icons" iname="folder-open" iexplain="View"}</a>
						{if $gContent->isOwner($galItem->mInfo) || $gBitUser->isAdmin()}
							<a href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$galItem->mInfo.content_id}">{biticon ipackage="icons" iname="edit" iexplain="Edit"}</a>
							<a href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$galItem->mInfo.content_id}&amp;delete=1">{biticon ipackage="icons" iname="user-trash" iexplain="Remove"}</a>
						{/if}
					</td>
				</tr>
			{/foreach}
		</table>

	</div><!-- end .body -->

	{pagination content_id=$gContent->mContentId}

	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='view' serviceHash=$gContent->mInfo}

	{if $gContent->getPreference('allow_comments') eq 'y'}
		{include file="bitpackage:liberty/comments.tpl"}
	{/if}
</div><!-- end .stock -->
{/strip}
