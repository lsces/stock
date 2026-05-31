{strip}
{include file="bitpackage:stock/assembly_nav.tpl"}
<div class="display stock container">
	<div class="header col-xs-12">
		{include file="bitpackage:stock/assembly_icons_inc.tpl"}
		<h1>{$gContent->getTitle()|escape}</h1>
	</div>

	<div class="body">
		{formfeedback success=$stockSuccess error=$stockErrors warning=$stockWarnings}

		{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$gContent->mInfo}
		{if $gContent->mInfo.data}
			<p>{$gContent->mInfo.data|escape}</p>
		{/if}

		<table class="thumbnailblock">
		{counter assign="itemCount" start="0" print=false}
		{foreach from=$gContent->mItems item=galItem key=itemContentId}
			{if $itemCount % 4 == 0}<tr>{/if}
			<td style="width:25%; vertical-align:top;">
				{box class="box {$galItem->mInfo.content_type_guid}"}
					<h4><a href="{$galItem->getDisplayUrl()|escape}">{$galItem->mInfo.title|escape}</a></h4>
					{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$galItem->mInfo type=mini}
					{if $gBitSystem->isFeatureActive('stock_gallery_list_image_descriptions')}
						<p>{$galItem->mInfo.data|escape}</p>
					{/if}
				{/box}
			</td>
			{counter name=itemCount}
			{if $itemCount % 4 == 0}</tr>{/if}
		{foreachelse}
			<tr><td class="norecords">{tr}This assembly has no components.{/tr}</td></tr>
		{/foreach}
		{if $itemCount % 4 != 0}</tr>{/if}
		</table>

		{pagination content_id=$gContent->mContentId}
	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
