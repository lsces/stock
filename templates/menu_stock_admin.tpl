{strip}
<ul class="{$packageMenuClass}">
	<li><a class="nosubmenu" href="{$smarty.const.KERNEL_PKG_URL}admin/index.php?page=stock">{tr}{$smarty.const.STOCK_PKG_DIR|capitalize}{/tr}</a></li>
	<li><a class="item" href="{$smarty.const.LIBERTY_PKG_URL}admin/admin_xref_groups.php?content_type_guid=stockassembly">{tr}Assembly Xref Groups{/tr}</a></li>
	<li><a class="item" href="{$smarty.const.LIBERTY_PKG_URL}admin/admin_xref_sources.php?content_type_guid=stockassembly">{tr}Assembly Xref Sources{/tr}</a></li>
	<li><a class="item" href="{$smarty.const.LIBERTY_PKG_URL}admin/admin_xref_groups.php?content_type_guid=stockcomponent">{tr}Component Xref Groups{/tr}</a></li>
	<li><a class="item" href="{$smarty.const.LIBERTY_PKG_URL}admin/admin_xref_sources.php?content_type_guid=stockcomponent">{tr}Component Xref Sources{/tr}</a></li>
</ul>
{/strip}