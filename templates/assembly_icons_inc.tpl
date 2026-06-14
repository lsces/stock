<div class="floaticon">
	{if $gContent->hasUpdatePermission()}
		<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_assembly.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="edit" iexplain="Edit Assembly"}</a>
		<a title="{tr}Component Order{/tr}" href="{$smarty.const.STOCK_PKG_URL}component_order.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="view-sort-ascending" iexplain="Component Order"}</a>
	{/if}
	<a title="{tr}Print Parts List{/tr}" href="{$smarty.const.STOCK_PKG_URL}print_bom.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="document-print" iexplain="Print Parts List"}</a>
	<a title="{tr}View Stock{/tr}" href="{$smarty.const.STOCK_PKG_URL}list_stock.php?assembly_content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="package-x-generic" iexplain="View Stock"}</a>
	<a title="{tr}View Movements{/tr}" href="{$smarty.const.STOCK_PKG_URL}list_movements.php?part_content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="go-next" iexplain="View Movements"}</a>
	{if $gContent->hasAdminPermission()}
		<a title="{tr}Delete Assembly{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_assembly.php?content_id={$gContent->mContentId}&amp;delete=1">{biticon ipackage="icons" iname="user-trash" iexplain="Delete Assembly"}</a>
	{/if}
</div>
