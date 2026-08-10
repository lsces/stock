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
<td>
	{if $xrefAllowEdit|default:false}
		{form ipackage="stock" ifile="edit_movement.php"}
			<input type="hidden" name="content_id" value="{$gContent->mInfo.content_id|escape}" />
			<input type="hidden" name="xref_id" value="{$xrefInfo.xref_id|escape}" />
			<input type="hidden" name="fAdjustAssembly" value="1" />
			<div class="form-inline">
				<input type="number" name="new_kit_count" value="{$xrefInfo.xkey|escape}" min="1" step="1"
					class="form-control input-sm" style="width:5em" />
				<button type="submit" class="btn btn-xs btn-default">{tr}Save{/tr}</button>
			</div>
		{/form}
	{else}
		{$xrefInfo.xkey|escape}
	{/if}
</td>
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
