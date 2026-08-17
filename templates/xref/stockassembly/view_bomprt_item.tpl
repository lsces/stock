{strip}
<tr class="{cycle values="even,odd"}">
	<td>
		{if $xrefInfo.xref > 0}
			<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$xrefInfo.xref|escape}">{$xrefInfo.linked_title|default:$xrefInfo.xref|escape}</a>
		{else}
			&nbsp;
		{/if}
	</td>
	<td>{$xrefInfo.linked_data|escape}</td>
	<td>{$xrefInfo.xkey|escape}{if $xrefInfo.part_size} of {$xrefInfo.part_size|escape}{/if}</td>
	<td>{$xrefInfo.xkey_ext|escape}</td>
	{include file="bitpackage:liberty/xref/dates_cell.tpl"}
	{include file="bitpackage:liberty/xref/action_icons.tpl"}
</tr>
{/strip}
