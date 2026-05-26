{assign var=galLayout value=$gContent->getLayout()}
{include file="`$smarty.const.STOCK_PKG_PATH`assembly_views/`$galLayout`/stock_`$galLayout`_inc.tpl"}

{if $gContent->mInfo.stockassembly_types}
	{jstabs}
		{section name=xrefGroup loop=$gContent->mInfo.stockassembly_types}
			{include file="bitpackage:liberty/list_xref.tpl"
				source=$gContent->mInfo.stockassembly_types[xrefGroup].source
				source_title=$gContent->mInfo.stockassembly_types[xrefGroup].title
				group=$gContent->mInfo.stockassembly_types[xrefGroup].sort_order}
		{/section}
	{/jstabs}
{/if}
