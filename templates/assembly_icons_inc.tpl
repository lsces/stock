<div class="floaticon">
	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='icon' serviceHash=$gContent->mInfo}
	{if $gContent->hasUpdatePermission()}
		<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit.php?assembly_id={$gContent->mAssemblyId}">{booticon iname="fa-pen-to-square" ipackage="icons" iexplain="Edit"}</a>
		<a title="{tr}Component Order{/tr}" href="{$smarty.const.STOCK_PKG_URL}component_order.php?assembly_id={$gContent->mAssemblyId}">{booticon iname="fa-sort" iexplain="Component Order"}</a>
		<a title="{tr}Add Component{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?assembly_id={$gContent->mAssemblyId}">{booticon iname="fa-plus" ipackage="icons" iexplain="Add Component"}</a>
	{/if}
	{if $gContent->hasAdminPermission()}
		<a title="{tr}Delete Assembly{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit.php?assembly_id={$gContent->mAssemblyId}&amp;delete=1">{booticon iname="fa-trash" ipackage="icons" iexplain="Delete Assembly"}</a>
	{/if}
</div>
