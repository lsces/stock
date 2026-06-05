{strip}
{if !empty($packageMenuTitle)}<a class="dropdown-toggle" data-toggle="dropdown" href="#"> {tr}{$packageMenuTitle}{/tr} <b class="caret"></b></a>{/if}
<ul class="{$packageMenuClass}">
	{if $gBitUser->hasPermission('p_stock_view')}
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php">{biticon ipackage="icons" iname="view-list-text" iexplain="List Assemblies" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}list_components.php">{biticon ipackage="icons" iname="view-list-text" iexplain="List Components" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}list_movements.php">{biticon ipackage="icons" iname="view-list-text" iexplain="List Movements" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}list_stock.php">{biticon ipackage="icons" iname="view-list-text" iexplain="Stock Levels" ilocation=menu}</a></li>
	{/if}
	{if $gBitUser->hasPermission('p_stock_create')}
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}edit_component.php">{biticon ipackage="icons" iname="camera-photo" iexplain="Create a Component" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}edit_assembly.php">{biticon ipackage="icons" iname="camera-photo" iexplain="Create an Assembly" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}edit_movement.php">{biticon ipackage="icons" iname="list-add" iexplain="Add Movement" ilocation=menu}</a></li>
		<li><a class="item" href="{$smarty.const.STOCK_PKG_URL}add_requisition.php">{biticon ipackage="icons" iname="list-add" iexplain="Create Requisition" ilocation=menu}</a></li>
	{/if}
</ul>
{/strip}
