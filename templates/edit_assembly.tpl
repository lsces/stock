{strip}
<div class="edit stock">
	<header>
		<div class="floaticon">
		</div>
		<h1>
			{if $gContent->getTitle()}
				{tr}Edit Assembly{/tr}: {$gContent->getTitle()|escape}
			{else}
				{tr}Create Assembly{/tr}
			{/if}
		</h1>
		{if $gContent->isValid()}
		<small><a href="{$smarty.const.STOCK_PKG_URL}view_assembly.php?content_id={$gContent->mContentId}">{$gContent->getTitle()|escape}</a></small>
		{else}
		<small><a href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php">{tr}Assemblies{/tr}</a></small>
		{/if}
	</header>

	<section class="body">
		{form id="editAssemblyForm" ipackage="stock" ifile="edit_assembly.php"}
			{formfeedback error=$errors success=$stockSuccess}

			<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}"/>

			<div class="form-group">
				{formlabel label="Title" for="assembly-title" mandatory="y"}
				{forminput}
					<input type="text" name="title" id="assembly-title" value="{$gContent->getTitle()|escape}" maxlength="160" size="50"/>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Description" for="assembly-desc"}
				{forminput}
					<textarea name="edit" id="assembly-desc" rows="4" cols="50">{$gContent->mInfo.data|default:''|escape}</textarea>
				{/forminput}
			</div>

			{if $gXrefInfo->mGroups}
				{jstabs}
					{foreach $gXrefInfo->mGroups as $xrefGroup}
						{include file=$gContent->getXrefListTemplate($xrefGroup->mTemplate)
							xrefGroup=$xrefGroup
							allow_add=true
							allow_edit=true}
					{/foreach}
				{/jstabs}
			{/if}

			{include file="bitpackage:liberty/edit_content_owner_inc.tpl"}

			<div class="form-group submit">
				{if $gContent->isValid()}
					<input type="submit" class="btn btn-default" name="cancelgallery" value="{tr}Cancel{/tr}"/>
				{/if}
				<input type="submit" class="btn btn-primary" name="savegallery" value="{tr}Save Assembly{/tr}"/>
			</div>
		{/form}

		{if $gContent->isValid()}
			{if isset($csvLoaded)}
				<div class="alert alert-info">
					{tr}Loaded{/tr}: <strong>{$csvLoaded}</strong>
					{if $csvSkipped} &nbsp; {tr}Skipped{/tr}: <strong>{$csvSkipped}</strong>{/if}
				</div>
				{if $csvErrors}
					<ul class="text-warning">
						{foreach from=$csvErrors item=msg}<li>{$msg|escape}</li>{/foreach}
					</ul>
				{/if}
			{/if}

			{form enctype="multipart/form-data" ipackage="stock" ifile="edit_assembly.php"}
				<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}"/>
				<div class="form-inline">
					<input type="file" name="csv_file" accept=".csv,text/csv"/>
					<input type="submit" class="btn btn-default" name="upload_bom_csv" value="{tr}Upload BOM{/tr}"/>
				</div>
				<p class="help-block">{tr}Columns: Component, Order, Quantity, Size (SGL/PRT/SHT/VOL), Ref designators{/tr}</p>
			{/form}
		{/if}

	</section>
</div>
{/strip}
