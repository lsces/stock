{assign var=galLayout value=$gContent->getLayout()}
{if $gBitUser->hasPermission('p_stock_update')}
	<div class="floaticon">
		<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit.php?content_id={$gContent->mContentId}">{booticon iname="fa-pen-to-square" iexplain="Edit Assembly"}</a>
	</div>
{/if}
{include file="`$smarty.const.STOCK_PKG_PATH`assembly_views/`$galLayout`/stock_`$galLayout`_inc.tpl"}

{if $gContent->mInfo.stockassembly_types}
	{jstabs}
		{section name=xrefGroup loop=$gContent->mInfo.stockassembly_types}
			{include file="bitpackage:liberty/list_xref.tpl"
				source=$gContent->mInfo.stockassembly_types[xrefGroup].source
				source_title=$gContent->mInfo.stockassembly_types[xrefGroup].title
				group=$gContent->mInfo.stockassembly_types[xrefGroup].sort_order
				allow_edit=false}
		{/section}
	{/jstabs}
{/if}
