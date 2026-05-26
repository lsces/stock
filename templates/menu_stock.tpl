{strip}
{if !empty($packageMenuTitle)}<a class="dropdown-toggle" data-toggle="dropdown" href="#"> {tr}{$packageMenuTitle}{/tr} <b class="caret"></b></a>{/if}
<ul class="{$packageMenuClass}">
	{if $gBitUser->hasPermission('p_stock_view')}
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php">{booticon iname="icon-list" iexplain="List Assemblies" ilocation=menu}</a></li>
	{/if}
	{if $gBitUser->hasPermission('p_stock_create')}
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php?user_id={$gBitUser->mUserId}">{booticon iname="icon-picture" iexplain="My Assemblies" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}edit.php">{booticon iname="icon-camera" iexplain="Create an Assembly" ilocation=menu}</a></li>
	{/if}
</ul>
{/strip}
