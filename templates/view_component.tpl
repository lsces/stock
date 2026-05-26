{strip}
{if empty($liberty_preview)}
	{include file="bitpackage:stock/assembly_nav.tpl"}
{/if}

<div class="display stock">
	{if empty($liberty_preview)}
		<div class="floaticon">
			{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='icon' serviceHash=$gContent->mInfo}
			{if $gContent->hasUpdatePermission()}
				<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$gContent->mContentId}">{booticon iname="fa-pen-to-square" iexplain="Edit Component"}</a>
				<a title="{tr}Delete{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$gContent->mContentId}&amp;delete=1">{booticon iname="fa-trash" iexplain="Delete Component"}</a>
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

		{if $gContent->mInfo.stockcomponent_types}
			{jstabs}
				{section name=xrefGroup loop=$gContent->mInfo.stockcomponent_types}
					{include file="bitpackage:liberty/list_xref.tpl"
						source=$gContent->mInfo.stockcomponent_types[xrefGroup].source
						source_title=$gContent->mInfo.stockcomponent_types[xrefGroup].title
						group=$gContent->mInfo.stockcomponent_types[xrefGroup].sort_order}
				{/section}
			{/jstabs}
		{/if}
	</div><!-- end .body -->

	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='view' serviceHash=$gContent->mInfo}

	{if $gGallery && $gGallery->isCommentable()}
		{include file="bitpackage:liberty/comments.tpl"}
	{/if}

</div><!-- end .stock -->
{/strip}
