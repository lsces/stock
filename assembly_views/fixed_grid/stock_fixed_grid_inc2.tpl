{strip}
{include file="bitpackage:stock/assembly_nav.tpl"}
<div class="display stock">
	<div class="header">
		{include file="bitpackage:stock/assembly_icons_inc.tpl"}
		<h1>{$gContent->getTitle()|escape}</h1>
	</div>

	{pagination content_id=$gContent->mContentId}

	<div class="body">
		{formfeedback success=$stockSuccess error=$stockErrors warning=$stockWarnings}

		{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$gContent->mInfo}
		{if $gContent->mInfo.data}
			<p>{$gContent->mInfo.data|escape}</p>
		{/if}

		{if !empty($cols_per_page) && $cols_per_page > 0}
			{assign var=tdWidth value="`100/$cols_per_page`"}
		{else}
			{assign var=tdWidth value="25"}
			{assign var=cols_per_page value="4"}
		{/if}
		<table class="thumbnailblock">
		{counter assign="itemCount" start="0" print=false}
		{foreach from=$gContent->mItems item=galItem key=itemContentId}
			{if $itemCount % $cols_per_page == 0}<tr>{/if}
			<td style="width:{$tdWidth}%; vertical-align:top;">
				{box class="box {$galItem->mInfo.content_type_guid}" style="margin-left:0;"}
					<h2><a href="{$galItem->getDisplayUrl()|escape}">{$galItem->mInfo.title|escape}</a></h2>
					{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$galItem->mInfo type=mini}
					{if $gBitSystem->isFeatureActive('stock_gallery_list_image_descriptions')}
						<p>{$galItem->mInfo.data|truncate:200:"..."|escape}</p>
					{/if}
				{/box}
			</td>
			{counter name=itemCount}
			{if $itemCount % $cols_per_page == 0}</tr>{/if}
		{foreachelse}
			<tr><td class="norecords">{tr}This assembly has no components.{/tr}</td></tr>
		{/foreach}
		{if $itemCount % $cols_per_page != 0}</tr>{/if}
		</table>
	</div><!-- end .body -->

	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='view' serviceHash=$gContent->mInfo}

	{if $gContent->getPreference('allow_comments') eq 'y'}
		{include file="bitpackage:liberty/comments.tpl"}
	{/if}
</div><!-- end .stock -->
{/strip}
