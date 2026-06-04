{strip}
{if $gBitSystem->isPackageActive('stock')}
	{bitmodule title="$moduleTitle" name="stock_navigation"}
		<div class="d-grid gap-1">
			{if $gBitUser->hasPermission('p_stock_view')}
				<a class="btn btn-default btn-block" href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php">{biticon ipackage="icons" iname="text-x-generic" iexplain="List Assemblies"} {tr}Assemblies{/tr}</a>
				<a class="btn btn-default btn-block" href="{$smarty.const.STOCK_PKG_URL}list_components.php">{biticon ipackage="icons" iname="emblem-favorite" iexplain="List Components"} {tr}Components{/tr}</a>
			{/if}
			{if $gBitUser->hasPermission('p_stock_create')}
				<a class="btn btn-primary btn-block" href="{$smarty.const.STOCK_PKG_URL}edit_assembly.php">{biticon ipackage="icons" iname="list-add" iexplain="Add Assembly"} {tr}Add Assembly{/tr}</a>
				<a class="btn btn-primary btn-block" href="{$smarty.const.STOCK_PKG_URL}edit_component.php">{biticon ipackage="icons" iname="list-add" iexplain="Add Component"} {tr}Add Component{/tr}</a>
			{/if}
			{if $gContent && $gContent->isValid() && $gContent->hasUpdatePermission()}
				{if $gContent->mContentId && $gContent->mContentTypeGuid == $smarty.const.STOCKASSEMBLY_CONTENT_TYPE_GUID}
					<a class="btn btn-warning btn-block" href="{$smarty.const.STOCK_PKG_URL}edit_assembly.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="document-properties" iexplain="Edit"} {tr}Edit Assembly{/tr}</a>
					<a class="btn btn-default btn-block" href="{$smarty.const.STOCK_PKG_URL}component_order.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="view-sort-ascending" iexplain="Order"} {tr}Component Order{/tr}</a>
				{elseif $gContent->mContentId && $gContent->mContentTypeGuid == $smarty.const.STOCKCOMPONENT_CONTENT_TYPE_GUID}
					<a class="btn btn-warning btn-block" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="document-properties" iexplain="Edit"} {tr}Edit Component{/tr}</a>
				{/if}
			{/if}
		</div>
	{/bitmodule}
{/if}
{/strip}
