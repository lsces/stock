{strip}

<div class="edit stock">
	<div class="header">
		<h1>{if $gContent->mContentId}{tr}Edit Component{/tr}: {$gContent->getTitle()|escape}{else}{tr}Add New Component{/tr}{/if}</h1>
	</div>

	<div class="body">
		{form}
			{formfeedback error=$errors}

			<input type="hidden" name="content_id" value="{$gContent->mContentId}"/>

			<div class="form-group">
				{formlabel label="Title" for="component-title"}
				{forminput}
					<input type="text" class="form-control input-xlarge" name="title" id="component-title" value="{$gContent->getTitle(0,0)|escape}" maxlength="160" size="50"/>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Description" for="component-desc"}
				{forminput}
					<textarea name="edit" class="form-control input-xlarge" id="component-desc" rows="4" cols="50">{$gContent->mInfo.data|escape}</textarea>
				{/forminput}
			</div>

			{include file="bitpackage:liberty/edit_services_inc.tpl" serviceFile="content_edit_mini_tpl"}

			{if $gContent->mInfo.stockcomponent_types}
				{jstabs}
					{section name=xrefGroup loop=$gContent->mInfo.stockcomponent_types}
						{include file=$gContent->getXrefListTemplate($gContent->mInfo.stockcomponent_types[xrefGroup].template)
							source=$gContent->mInfo.stockcomponent_types[xrefGroup].source
							source_title=$gContent->mInfo.stockcomponent_types[xrefGroup].title
							group=$gContent->mInfo.stockcomponent_types[xrefGroup].sort_order
							allow_add=true}
					{/section}
				{/jstabs}
			{/if}

			<div class="form-group submit">
				<input type="submit" class="btn btn-primary" name="save" value="{tr}Save Component{/tr}"/>
			</div>
		{/form}
	</div><!-- end .body -->
</div><!-- end .stock -->

{/strip}
