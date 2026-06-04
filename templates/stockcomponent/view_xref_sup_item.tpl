{strip}
<tr class="{cycle values="even,odd"}">
	<td>
		{if $gContent->mInfo.$source[xref].xref > 0}
			<a href="{$smarty.const.CONTACT_PKG_URL}display_contact.php?content_id={$gContent->mInfo.$source[xref].xref|escape}">{$gContent->mInfo.$source[xref].xref_title|default:$gContent->mInfo.$source[xref].xref|escape}</a>
		{else}
			&nbsp;
		{/if}
	</td>
	<td>{$gContent->mInfo.$source[xref].xkey|escape}</td>
	<td>{$gContent->mInfo.$source[xref].xkey_ext|escape}</td>
	<td>{$gContent->mInfo.$source[xref].data|escape}</td>
	{if $xrefAllowEdit}
		<td>{$gContent->mInfo.$source[xref].start_date|bit_short_date}</td>
		<td>{$gContent->mInfo.$source[xref].last_update_date|bit_short_date}</td>
	{/if}
	{if $xrefAllowEdit}
		<td>
			<span class="actionicon">
				{if $gContent->hasUpdatePermission() && $source ne 'history'}
					{smartlink ititle="Edit" ipackage="liberty" ifile="edit_xref.php" biticon="document-properties" content_id=$gContent->mInfo.content_id xref_id=$gContent->mInfo.$source[xref].xref_id}
				{/if}
				{if $gContent->hasExpungePermission()}
					{if $source eq 'history'}
						{smartlink ititle="Restore" ipackage="liberty" ifile="edit_xref.php" biticon="document-properties" content_id=$gContent->mInfo.content_id xref_id=$gContent->mInfo.$source[xref].xref_id expunge=-1}
					{else}
						{smartlink ititle="Delete" ipackage="liberty" ifile="edit_xref.php" biticon="edit-delete" content_id=$gContent->mInfo.content_id xref_id=$gContent->mInfo.$source[xref].xref_id expunge=1}
					{/if}
				{/if}
			</span>
		</td>
	{/if}
</tr>
{/strip}
