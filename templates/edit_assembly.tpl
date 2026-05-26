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
		{form id="editAssemblyForm" ipackage="stock" ifile="edit.php"}
			{formfeedback error=$errors warning=$stockWarnings success=$stockSuccess}

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
