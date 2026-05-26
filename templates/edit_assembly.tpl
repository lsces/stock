{literal}
<script>//<![CDATA[
function updateGalleryPagination() {
	var paginationIds = ['fixed_grid','auto_flow','position_number','simple_list'];
	paginationIds.forEach(function(id) {
		var div = document.getElementById(id+'-pagination');
		div.style.display = 'none';
		div.querySelectorAll('input, select').forEach(function(el) { el.disabled = true; });
	});
	var input = document.getElementById('editGalleryForm').gallery_pagination;
	var select = input.options[input.selectedIndex].value;
	var activeDiv = document.getElementById(select+'-pagination');
	activeDiv.style.display = 'block';
	activeDiv.querySelectorAll('input, select').forEach(function(el) { el.disabled = false; });
}
document.addEventListener('DOMContentLoaded', updateGalleryPagination);
//]]></script>
{/literal}
{strip}
<div class="edit stock">
	<div class="header">
		<h1>
			{if $gContent->getTitle()}
				{tr}Edit Assembly{/tr}: {$gContent->getTitle()|escape}
			{else}
				{tr}Create Assembly{/tr}
			{/if}
		</h1>
	</div>

	<div class="body">
		{form id="editGalleryForm" ipackage="stock" ifile="edit.php"}
			{formfeedback error=$errors warning=$stockWarnings success=$stockSuccess}

			<input type="hidden" name="gallery_id" value="{$galleryId|escape}"/>

			<div class="form-group">
				{formlabel label="Title" for="gallery-title" mandatory="y"}
				{forminput}
					<input type="text" name="title" id="gallery-title" value="{$gContent->getTitle()|escape}" maxlength="160" size="50"/>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Description" for="gallery-desc"}
				{forminput}
					<textarea name="edit" id="gallery-desc" rows="4" cols="50">{$gContent->mInfo.data|default:''|escape}</textarea>
				{/forminput}
			</div>

			{if $gBitUser->hasPermission('p_stock_create_public_gal')}
				<div class="form-group">
					{forminput label="checkbox"}
						<input type="checkbox" name="is_public" id="is_public" value="y" {if $gContent->getPreference('is_public') eq 'y'}checked="checked"{/if} />{tr}Public Assembly{/tr}
						{formhelp note="Allow other users to add components to this assembly."}
					{/forminput}
				</div>
			{/if}

			<div class="form-group">
				{formlabel label="Gallery Pagination" for="gallery-pagination"}
				{forminput}
					{html_options name="gallery_pagination" id="gallery-pagination" options=$galleryPaginationTypes selected=$gContent->getPreference('gallery_pagination',$gBitSystem->getConfig('default_gallery_pagination',$smarty.const.STOCK_PAGINATION_FIXED_GRID)) onchange="updateGalleryPagination();"}
					<div id="fixed_grid-pagination">
						<input type="text" id="gallery-rows-per-page" name="rows_per_page" size="2" maxlength="2" value="{$gContent->mInfo.rows_per_page|default:$gBitSystem->getConfig('stock_gallery_default_rows_per_page')}"/> {tr}Rows per page{/tr}<br/>
						<input type="text" id="gallery-cols-per-page" name="cols_per_page" size="2" maxlength="2" value="{$gContent->mInfo.cols_per_page|default:$gBitSystem->getConfig('stock_gallery_default_cols_per_page')}"/> {tr}Columns per page{/tr}
					</div>
					<div id="auto_flow-pagination">
						<input type="text" id="gallery-total-per-page" name="total_per_page" size="3" maxlength="3" value="{$gContent->getPreference('total_per_page', $gContent->mInfo.rows_per_page|default:20)}"/> {tr}Total components per page{/tr}
					</div>
					<div id="position_number-pagination">
						{formhelp note="Components are ordered by package group (the integer part of the position number) and position within the package (the decimal part). Use the Component Order page to assign positions."}
					</div>
					<div id="simple_list-pagination">
						<input type="text" id="gallery-total-per-page-list" name="total_per_page" size="3" maxlength="3" value="{$gContent->getPreference('total_per_page', $gContent->mInfo.rows_per_page|default:20)}"/> {tr}Total lines per page{/tr}
					</div>
				{/forminput}
			</div>

			<div class="form-group">
				{forminput label="checkbox"}
					<input type="checkbox" name="allow_comments" id="allow_comments" value="y" {if !$gContent->isValid() || $gContent->getPreference('allow_comments') eq 'y'}checked="checked"{/if} />{tr}Allow Comments{/tr}
					{formhelp note="Allow posting comments on components in this assembly."}
				{/forminput}
			</div>

			{include file="bitpackage:liberty/edit_services_inc.tpl" serviceFile="content_edit_mini_tpl"}

			{if $gContent->mInfo.stockassembly_types}
				{jstabs}
					{section name=xrefGroup loop=$gContent->mInfo.stockassembly_types}
						{include file="bitpackage:liberty/list_xref.tpl"
							source=$gContent->mInfo.stockassembly_types[xrefGroup].source
							source_title=$gContent->mInfo.stockassembly_types[xrefGroup].title
							group=$gContent->mInfo.stockassembly_types[xrefGroup].sort_order
							allow_add=true}
					{/section}
				{/jstabs}
			{/if}

			{if $galleryTree}
				{legend legend="Assembly Memberships"}
					<p>{tr}If you would like this assembly to be a sub-assembly, check the parent assembly below.{/tr}</p>
					<div class="form-group">
						<div class="gallerytree">
							{$galleryTree}
						</div>
					</div>
				{/legend}
			{/if}

			<div class="form-group submit">
				{if $gContent->isValid()}
					<input type="submit" class="btn btn-default" name="cancelgallery" value="{tr}Cancel{/tr}"/>
				{/if}
				<input type="submit" class="btn btn-primary" name="savegallery" value="{tr}Save Assembly{/tr}"/>
			</div>
		{/form}

	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
