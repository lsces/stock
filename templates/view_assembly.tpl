{assign var=galLayout value=$gContent->getLayout()}
{if $gContent->hasUpdatePermission()}
	<div class="floaticon">
		{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='icon' serviceHash=$gContent->mInfo}
		<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_assembly.php?content_id={$gContent->mContentId}">{booticon iname="fa-pen-to-square" iexplain="Edit Assembly"}</a>
		<a title="{tr}Component Order{/tr}" href="{$smarty.const.STOCK_PKG_URL}component_order.php?content_id={$gContent->mContentId}">{booticon iname="fa-sort" iexplain="Component Order"}</a>
		<a title="{tr}View Stock{/tr}" href="{$smarty.const.STOCK_PKG_URL}list_stock.php?assembly_content_id={$gContent->mContentId}">{booticon iname="fa-boxes-stacked" iexplain="View Stock"}</a>
		<a title="{tr}View Movements{/tr}" href="{$smarty.const.STOCK_PKG_URL}list_movements.php?assembly_content_id={$gContent->mContentId}">{booticon iname="fa-truck" iexplain="View Movements"}</a>
		{if $gContent->hasAdminPermission()}
			<a title="{tr}Delete Assembly{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_assembly.php?content_id={$gContent->mContentId}&amp;delete=1">{booticon iname="fa-trash" iexplain="Delete Assembly"}</a>
		{/if}
	</div>
{/if}
{include file="`$smarty.const.STOCK_PKG_PATH`assembly_views/`$galLayout`/stock_`$galLayout`_inc.tpl"}

{if $gContent->mInfo.stockassembly_types}
	{jstabs}
		{section name=xrefGroup loop=$gContent->mInfo.stockassembly_types}
			{include file=$gContent->getXrefListTemplate($gContent->mInfo.stockassembly_types[xrefGroup].template)
				source=$gContent->mInfo.stockassembly_types[xrefGroup].source
				source_title=$gContent->mInfo.stockassembly_types[xrefGroup].title
				group=$gContent->mInfo.stockassembly_types[xrefGroup].sort_order
				allow_edit=false}
		{/section}
	{/jstabs}
{/if}
