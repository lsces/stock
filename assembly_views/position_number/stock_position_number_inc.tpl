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

		{assign var=lastPackage value=-1}
		{foreach from=$gContent->mItems item=galItem key=itemContentId}
			{assign var=thisPackage value=$galItem->getField('item_position')|default:0|floor}
			{if $thisPackage != $lastPackage}
				{if $lastPackage >= 0}</ul>{/if}
				<h3>
					{if $thisPackage > 0}
						{tr}Package{/tr} {$thisPackage}
					{else}
						{tr}Unassigned{/tr}
					{/if}
				</h3>
				<ul class="component-list">
				{assign var=lastPackage value=$thisPackage}
			{/if}
			<li>
				<a href="{$galItem->getDisplayUrl()|escape}">{$galItem->getTitle()|escape}</a>
				{if $gBitSystem->isFeatureActive('stock_item_list_desc') && $galItem->mInfo.data}
					<span class="text-muted"> — {$galItem->mInfo.data|truncate:120|escape}</span>
				{/if}
				{if $gContent->isOwner($galItem->mInfo) || $gBitUser->isAdmin()}
					<a class="actionicon" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$galItem->mInfo.content_id}">{biticon ipackage="icons" iname="edit" iexplain="Edit"}</a>
				{/if}
			</li>
		{foreachelse}
			<p class="norecords">{tr}This assembly has no components.{/tr}</p>
		{/foreach}
		{if $lastPackage >= 0}</ul>{/if}

	</div><!-- end .body -->

	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='view' serviceHash=$gContent->mInfo}

	{if $gContent->getPreference('allow_comments') eq 'y'}
		{include file="bitpackage:liberty/comments.tpl"}
	{/if}
</div><!-- end .stock -->
{/strip}
