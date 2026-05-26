{strip}
{if $gBitSystem->isPackageActive('stock')}
	{bitmodule title="$moduleTitle" name="stock_navigation"}
		<div class="d-grid gap-1">
			{if $gBitUser->hasPermission('p_stock_view')}
				<a class="btn btn-default btn-block" href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php">{booticon iname="fa-list" iexplain="List Assemblies"} {tr}Assemblies{/tr}</a>
				<a class="btn btn-default btn-block" href="{$smarty.const.STOCK_PKG_URL}list_components.php">{booticon iname="fa-tags" iexplain="List Components"} {tr}Components{/tr}</a>
			{/if}
			{if $gBitUser->hasPermission('p_stock_create')}
				<a class="btn btn-primary btn-block" href="{$smarty.const.STOCK_PKG_URL}edit.php">{booticon iname="fa-plus" iexplain="Add Assembly"} {tr}Add Assembly{/tr}</a>
				<a class="btn btn-primary btn-block" href="{$smarty.const.STOCK_PKG_URL}edit_component.php">{booticon iname="fa-plus" iexplain="Add Component"} {tr}Add Component{/tr}</a>
			{/if}
			{if $gContent && $gContent->isValid() && $gContent->hasUpdatePermission()}
				{if $gContent->mAssemblyId}
					<a class="btn btn-warning btn-block" href="{$smarty.const.STOCK_PKG_URL}edit.php?assembly_id={$gContent->mAssemblyId}">{booticon iname="fa-pen-to-square" iexplain="Edit"} {tr}Edit Assembly{/tr}</a>
					<a class="btn btn-default btn-block" href="{$smarty.const.STOCK_PKG_URL}component_order.php?assembly_id={$gContent->mAssemblyId}">{booticon iname="fa-sort" iexplain="Order"} {tr}Component Order{/tr}</a>
				{elseif $gContent->mComponentId}
					<a class="btn btn-warning btn-block" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$gContent->mContentId}">{booticon iname="fa-pen-to-square" iexplain="Edit"} {tr}Edit Component{/tr}</a>
				{/if}
			{/if}
		</div>
	{/bitmodule}
{/if}
{/strip}
