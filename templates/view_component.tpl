{strip}
{if empty($liberty_preview)}
	{include file="bitpackage:stock/assembly_nav.tpl"}
{/if}

<div class="display stock">
	{if empty($liberty_preview)}
		<div class="floaticon">
			{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='icon' serviceHash=$gContent->mInfo}
			{if $gContent->hasUpdatePermission()}
				<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="document-properties" iexplain="Edit Component"}</a>
				<a title="{tr}Delete{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$gContent->mContentId}&amp;delete=1">{biticon ipackage="icons" iname="user-trash" iexplain="Delete Component"}</a>
			{/if}
		</div>
	{/if}

	{formfeedback hash=$feedback}
	<div class="header">
		<h1>{$gContent->getTitle()|escape}</h1>
	</div>

	<div class="body">
		{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$gContent->mInfo}
		{if $gContent->mInfo.data ne ''}
			<p class="description">{$gContent->mInfo.parsed_data}</p>
		{/if}

		{jstabs}
			{if $gContent->mInfo.stockcomponent_types}
				{section name=xrefGroup loop=$gContent->mInfo.stockcomponent_types}
					{include file=$gContent->getXrefListTemplate($gContent->mInfo.stockcomponent_types[xrefGroup].template)
						source=$gContent->mInfo.stockcomponent_types[xrefGroup].source
						source_title=$gContent->mInfo.stockcomponent_types[xrefGroup].title
						group=$gContent->mInfo.stockcomponent_types[xrefGroup].sort_order
						allow_edit=false}
				{/section}
			{/if}

			{jstab title="{tr}Stock{/tr}"}
			<table class="table table-condensed">
				<thead>
					<tr>
						<th>{tr}Type{/tr}</th>
						<th class="text-right">{tr}Level{/tr}</th>
					</tr>
				</thead>
				<tbody>
					{if $componentStockLevels}
						{foreach from=$componentStockLevels key=qtype item=level}
						<tr{if $level < 0} class="danger"{elseif $level == 0} class="warning"{/if}>
							<td>{$qtype|escape}</td>
							<td class="text-right">{$level|string_format:"%.0f"}</td>
						</tr>
						{/foreach}
					{else}
						<tr class="norecords"><td colspan="2">{tr}No stock movements recorded{/tr}</td></tr>
					{/if}
				</tbody>
			</table>
			<a class="btn btn-default btn-xs" href="{$smarty.const.STOCK_PKG_URL}list_movements.php?component_content_id={$gContent->mContentId}">{tr}Stock history{/tr}</a>
			{/jstab}
		{/jstabs}
	</div><!-- end .body -->

	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='view' serviceHash=$gContent->mInfo}

	{if $gGallery && $gGallery->isCommentable()}
		{include file="bitpackage:liberty/comments.tpl"}
	{/if}

</div><!-- end .stock -->
{/strip}
