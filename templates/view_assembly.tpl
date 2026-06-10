{include file="bitpackage:stock/stock_simple_list_inc.tpl"}

{if $gXrefInfo->mGroups}
	{jstabs}
		{foreach $gXrefInfo->mGroups as $xrefGroup}
			{include file=$gContent->getXrefListTemplate($xrefGroup->mTemplate)
				xrefGroup=$xrefGroup
				allow_edit=false}
		{/foreach}
	{/jstabs}
{/if}
