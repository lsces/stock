{if $gContent->hasUpdatePermission()}
	<a title="{tr}Add Component{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="list-add" iexplain="Add Component"}</a>
{/if}
