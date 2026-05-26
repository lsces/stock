{literal}
<script>//<![CDATA[
function updateGalleryPagination() {
	BitBase.hideById('fixed_grid-pagination');
	BitBase.hideById('auto_flow-pagination');
	BitBase.hideById('position_number-pagination');
	BitBase.hideById('simple_list-pagination');

	var input = document.getElementById('gallery-pagination');
    var i  = input.selectedIndex;
    var select = input.options[i].value;
	BitBase.showById(select+'-pagination');
}
//]]></script>
{/literal}
{strip}

{form}
	{jstabs}
		{jstab title="Settings"}
			{legend legend="General Settings"}
				<div class="form-group">
					{formhelp note="To change the Image Processing engine, you need to change the setting in Liberty Settings" link="kernel/admin/index.php?page=liberty/Liberty Settings"}
				</div>

				{foreach from=$formGalleryGeneral key=item item=output}
					<div class="form-group">
						{if $output.type=='checkbox'}
							{forminput label="checkbox"}
								{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item} {$output.label}
								{formhelp note=$output.note}
							{/forminput}
						{else}
							{formlabel label=$output.label for=$item}
							{forminput}
								<input type="text" class="form-control" name="{$item}" id="{$item}" value="{$gBitSystem->getConfig($item)}"/>
								{formhelp note=$output.note}
							{/forminput}
						{/if}
					</div>
				{/foreach}
			{/legend}
		{/jstab}

		{jstab title="List"}
			{legend legend="Assembly List Options"}
				<div class="form-group">
					{formhelp note="The options below determine what information is shown on the List Assemblies page."}
				</div>

				{foreach from=$formGalleryListLists key=item item=output}
					<div class="form-group">
						{forminput label="checkbox"}
							{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item} {$output.label}
							{formhelp note=$output.note}
						{/forminput}
					</div>
				{/foreach}
			{/legend}
		{/jstab}

		{jstab title="Assemblies"}
			{legend legend="Assembly Display Settings"}
				<input type="hidden" name="page" value="{$page}" />
				<div class="form-group">
					{formhelp note="The options below determine what information is shown on an assembly display page."}
				</div>

				{foreach from=$formGalleryLists key=item item=output}
					<div class="form-group">
						{forminput label="checkbox"}
							{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item} {$output.label}
							{formhelp note=$output.note page=$output.page|default:0}
						{/forminput}
					</div>
				{/foreach}

				<div class="form-group">
					{formlabel label="Default sort order" for="stock_gallery_default_sort_mode"}
					{forminput}
						{html_options values=$sortOptions options=$sortOptions name="stock_gallery_default_sort_mode" id="stock_gallery_default_sort_mode" selected=$gBitSystem->getConfig('stock_gallery_default_sort_mode')}
						{formhelp note="Default component order when no manual position has been assigned."}
					{/forminput}
				</div>
			{/legend}

			{legend legend="Default Assembly Display Settings"}
				<div class="form-group">
					{formhelp note="The settings below determine what the default display options will be set to for new assemblies."}
				</div>

				<div class="form-group">
					{html_options id="gallery-pagination" name="default_gallery_pagination" id="gallery-pagination" options=$galleryPaginationTypes selected=$gBitSystem->getConfig('default_gallery_pagination',$smarty.const.STOCK_PAGINATION_FIXED_GRID) onchange="updateGalleryPagination();"}

					<div id="fixed_grid-pagination">
						<input type="text" id="gallery-rows-per-page" name="rows_per_page" size="2" maxlength="2" value="{$gBitSystem->getConfig('stock_gallery_default_rows_per_page')}"/> {tr}Rows per page{/tr}<br/>
						<input type="text" id="gallery-cols-per-page" name="cols_per_page" size="2" maxlength="2" value="{$gBitSystem->getConfig('stock_gallery_default_cols_per_page')}"/> {tr}Columns per page{/tr}
					</div>
					<div id="auto_flow-pagination">
						<input type="text" id="gallery-rows-per-page" name="total_per_page" size="2" maxlength="2" value="{$gBitSystem->getConfig('stock_gallery_default_rows_per_page')}"/> {tr}Total components per page{/tr}
					</div>
					<div id="position_number-pagination">
						{formhelp note="Components are ordered by package group and position within the package. Use the Component Order page to assign positions."}
					</div>
					<div id="simple_list-pagination">
						<input type="text" id="gallery-rows-per-page" name="lines_per_page" size="2" maxlength="2" value="{$gBitSystem->getConfig('stock_gallery_default_rows_per_page')}"/> {tr}Total lines per page{/tr}
					</div>
				</div>
			{/legend}
		{/jstab}

		{jstab title="Components"}
			{legend legend="Component Display Settings"}
				<div class="form-group">
					{formhelp note="The options below determine what information is displayed on the component display page."}
				</div>

				{foreach from=$formImageLists key=item item=output}
					<div class="form-group">
						{forminput label="checkbox"}
							{html_checkboxes name="$item" values="y" checked=$gBitSystem->getConfig($item) labels=false id=$item} {$output.label}
							{formhelp note=$output.note}
						{/forminput}
					</div>
				{/foreach}
			{/legend}
		{/jstab}
	{/jstabs}

		<div class="form-group submit">
			<input type="submit" class="btn btn-default" name="stockAdminSubmit" value="{tr}Change Preferences{/tr}" />
		</div>
{/form}

{/strip}
<script>//<![CDATA[
updateGalleryPagination();
//]]></script>
