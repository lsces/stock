{strip}
<td>
	{assign var=asmTitle value=$xrefInfo.linked_title|default:$xrefInfo.xkey_ext}
	{if $xrefInfo.xref}
		<a href="{if $xrefInfo.data eq 'stockassembly'}{$smarty.const.STOCK_PKG_URL}view_assembly.php?content_id={$xrefInfo.xref}{else}{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$xrefInfo.xref}{/if}">{$asmTitle|escape}</a>
	{else}
		{$asmTitle|escape}
	{/if}
	{if $xrefInfo.linked_desc}
		<div class="small text-muted">{$xrefInfo.linked_desc|strip_tags|truncate:120|escape}</div>
	{/if}
</td>
<td>{if $xrefInfo.data eq 'stockassembly'}{tr}Assembly{/tr}{else}{tr}Component{/tr}{/if}</td>
<td>{$xrefInfo.xkey|escape}</td>
{if $xrefAllowEdit|default:false}
<td>
	<span class="actionicon">
		{if $gContent->hasExpungePermission()}
			{smartlink ititle="Remove" ipackage="liberty" ifile="edit_xref.php" biticon="user-trash" content_id=$gContent->mInfo.content_id xref_id=$xrefInfo.xref_id expunge=1}
		{/if}
	</span>
</td>
{/if}
{/strip}
