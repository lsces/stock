{strip}
<tr class="{cycle values="even,odd"}">
	<td>
		{if $gContent->mInfo.$source[xref].xref > 0}
			<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$gContent->mInfo.$source[xref].xref|escape}">{$gContent->mInfo.$source[xref].xref_title|default:$gContent->mInfo.$source[xref].xref|escape}</a>
		{else}
			&nbsp;
		{/if}
	</td>
	<td>{$gContent->mInfo.$source[xref].xref_data|escape}</td>
	<td>{$gContent->mInfo.$source[xref].xkey|escape}{if $gContent->mInfo.$source[xref].pack_size} of {$gContent->mInfo.$source[xref].pack_size|escape}{if $gContent->mInfo.$source[xref].pack_size_ext} {$gContent->mInfo.$source[xref].pack_size_ext|escape}{/if}{/if}</td>
	<td>{$gContent->mInfo.$source[xref].xkey_ext|escape}</td>
	{if $xrefAllowEdit}
		<td>{$gContent->mInfo.$source[xref].start_date|bit_short_date}</td>
		<td>{$gContent->mInfo.$source[xref].last_update_date|bit_short_date}</td>
		<td>
			<span class="actionicon">
				{if $gContent->hasUpdatePermission() && $source ne 'history'}
					{smartlink ititle="Edit" ipackage="liberty" ifile="edit_xref.php" biticon="document-properties" content_id=$gContent->mInfo.content_id xref_id=$gContent->mInfo.$source[xref].xref_id}
				{/if}
				{if $gContent->hasExpungePermission()}
					{smartlink ititle="Delete" ipackage="liberty" ifile="edit_xref.php" biticon="edit-delete" content_id=$gContent->mInfo.content_id xref_id=$gContent->mInfo.$source[xref].xref_id expunge=1}
				{/if}
			</span>
		</td>
	{/if}
</tr>
{if $gContent->mInfo.$source[xref].data}
<tr>
	<td colspan="8" class="xref-note">{$gContent->mInfo.$source[xref].data|escape}</td>
</tr>
{/if}
{/strip}
