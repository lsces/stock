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

			<div class="form-group submit">
				{if $gContent->isValid()}
					<input type="submit" class="btn btn-default" name="cancelgallery" value="{tr}Cancel{/tr}"/>
				{/if}
				<input type="submit" class="btn btn-primary" name="savegallery" value="{tr}Save Assembly{/tr}"/>
			</div>
		{/form}

		{if $gContent->isValid()}
			{* CSV upload results *}
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

			{* ── Upload BOM CSV ── *}
			{form enctype="multipart/form-data" ipackage="stock" ifile="edit_assembly.php"}
				<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}"/>
				<div class="form-inline">
					<input type="file" name="csv_file" accept=".csv,text/csv"/>
					<input type="submit" class="btn btn-default" name="upload_bom_csv" value="{tr}Upload BOM{/tr}"/>
				</div>
				<p class="help-block">{tr}Columns: Component, Order, Quantity, Size (SGL/PCK/SHT/VOL), Ref designators{/tr}</p>
			{/form}
		{/if}

	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
