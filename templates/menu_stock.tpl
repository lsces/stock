{strip}
{if !empty($packageMenuTitle)}<a class="dropdown-toggle" data-toggle="dropdown" href="#"> {tr}{$packageMenuTitle}{/tr} <b class="caret"></b></a>{/if}
<ul class="{$packageMenuClass}">
	{if $gBitUser->hasPermission('p_stock_view')}
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}view_kitlocker.php">{biticon ipackage="icons" iname="view-grid-symbolic" iexplain="Kitlocker" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php">{biticon ipackage="icons" iname="view-group" iexplain="List Assemblies" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}list_components.php">{biticon ipackage="icons" iname="view-list-tree" iexplain="List Components" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}list_movements.php">{biticon ipackage="icons" iname="view-list-details" iexplain="List Movements" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}list_stock.php">{biticon ipackage="icons" iname="view-form-table" iexplain="Stock Levels" ilocation=menu}</a></li>
	{/if}
	{if $gBitUser->hasPermission('p_stock_create')}
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}edit_component.php">{biticon ipackage="icons" iname="kt-add-filters" iexplain="Create a Component" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}edit_assembly.php">{biticon ipackage="icons" iname="view-list-icons" iexplain="Create an Assembly" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}edit_movement.php">{biticon ipackage="icons" iname="view-task-add" iexplain="Add Movement" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}add_prebuild.php">{biticon ipackage="icons" iname="package-x-generic" iexplain="Create Prebuild" ilocation=menu}</a></li>
	{/if}
</ul>
{/strip}
