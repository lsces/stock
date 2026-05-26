{strip}
{if $thumbnailList || $showEmpty}
	<div class="listing stock">
		<div class="header">
			<h2>{$stock_center_params.title|default:"{tr}Components{/tr}"}</h2>
		</div>

		<div class="body">
			{foreach from=$thumbnailList key=componentId item=component}
				<div><a href="{$component.display_url}">{$component.title|escape}</a></div>
			{foreachelse}
				{tr}No records found{/tr}
			{/foreach}
			<p><a href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php?user_id={$gQueryUserId}">{tr}View More{/tr}...</a></p>
		</div><!-- end .body -->
	</div><!-- end .stock -->
{/if}
{/strip}
