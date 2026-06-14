<small>
	<a href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php">{tr}Assemblies{/tr}</a>
	{assign var=breadCrumbs value=$gContent->getBreadcrumbLinks(1)}
	{foreach from=$breadCrumbs item=breadTitle key=breadId}
		{if $breadId != $gContent->mContentId}
			&rsaquo; <a href="{$smarty.const.STOCK_PKG_URL}view_assembly.php?content_id={$breadId}">{$breadTitle}</a>
		{/if}
	{/foreach}
</small>
