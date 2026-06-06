{strip}
<tr class="{cycle values="even,odd"}">
	<td>
		{if $xrefInfo.xref > 0}
			<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$xrefInfo.xref|escape}">{$xrefInfo.xref_title|default:$xrefInfo.xref|escape}</a>
		{else}
			&nbsp;
		{/if}
	</td>
	<td>{$xrefInfo.xref_data|escape}</td>
	<td>{$xrefInfo.xkey|escape}{if $xrefInfo.pack_size} of {$xrefInfo.pack_size|escape}{if $xrefInfo.pack_size_ext} {$xrefInfo.pack_size_ext|escape}{/if}{/if}</td>
	<td>{$xrefInfo.xkey_ext|escape}</td>
	{if $xrefAllowEdit}
		<td>{$xrefInfo.start_date|bit_short_date}</td>
		<td>{$xrefInfo.last_update_date|bit_short_date}</td>
		<td>
			<span class="actionicon">
				{if $gContent->hasUpdatePermission() && !$isHistory}
					{smartlink ititle="Edit" ipackage="liberty" ifile="edit_xref.php" biticon="edit" content_id=$gContent->mInfo.content_id xref_id=$xrefInfo.xref_id}
				{/if}
				{if $gContent->hasExpungePermission()}
					{smartlink ititle="Delete" ipackage="liberty" ifile="edit_xref.php" biticon="user-trash" content_id=$gContent->mInfo.content_id xref_id=$xrefInfo.xref_id expunge=1}
				{/if}
			</span>
		</td>
	{/if}
</tr>
{if $xrefInfo.data}
<tr>
	<td colspan="{if $xrefAllowEdit}7{else}4{/if}" class="xref-note">{$xrefInfo.data|escape}</td>
</tr>
{/if}
{/strip}
