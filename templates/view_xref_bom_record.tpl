{strip}
<td></td>
<td>
	{if $gContent->mInfo.$source[xref].xref > 0}
		<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?component_id={$gContent->mInfo.$source[xref].xref|escape}">{$gContent->mInfo.$source[xref].xref_title|default:$gContent->mInfo.$source[xref].xref|escape}</a>
	{else}
		&nbsp;
	{/if}
</td>
<td>{$gContent->mInfo.$source[xref].xkey|escape}</td>
<td>{$gContent->mInfo.$source[xref].xkey_ext|escape}</td>
<td>{if $gContent->hasUpdatePermission()}{$gContent->mInfo.$source[xref].start_date|bit_short_date}{/if}</td>
<td>{if $gContent->hasUpdatePermission()}{$gContent->mInfo.$source[xref].last_update_date|bit_short_date}{/if}</td>
<td>
	<span class="actionicon">
		{if $xrefAllowEdit|default:true && $gContent->hasUpdatePermission() && $source ne 'history'}
			{smartlink ititle="Edit" ipackage="liberty" ifile="edit_xref.php" booticon="icon-note-edit" content_id=$gContent->mInfo.content_id xref_id=$gContent->mInfo.$source[xref].xref_id}
		{/if}
		{if $xrefAllowEdit|default:true && $gContent->hasExpungePermission()}
			{smartlink ititle="Delete" ipackage="liberty" ifile="edit_xref.php" booticon="icon-note-delete" content_id=$gContent->mInfo.content_id xref_id=$gContent->mInfo.$source[xref].xref_id expunge=1}
		{/if}
	</span>
</td>
{/strip}
