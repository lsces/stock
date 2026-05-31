{if $gContent->hasUpdatePermission()}
	<a title="{tr}Add Component{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$gContent->mContentId}">{booticon iname="fa-plus" ipackage="icons" iexplain="Add Component"}</a>
{/if}
