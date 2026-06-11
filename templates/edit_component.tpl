{strip}
<div class="edit stock">
	<header>
		<div class="floaticon">
		</div>
		<h1>{if $gContent->mContentId}{tr}Edit Component{/tr}: {$gContent->getTitle()|escape}{else}{tr}Add New Component{/tr}{/if}</h1>
		{if $gContent->isValid()}
		<small><a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$gContent->mContentId}">{$gContent->getTitle()|escape}</a></small>
		{else}
		<small><a href="{$smarty.const.STOCK_PKG_URL}list_components.php">{tr}Components{/tr}</a></small>
		{/if}
	</header>

	<section class="body">
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

			{if $gXrefInfo->mGroups}
				{jstabs}
					{assign var=klGroup value=null}
					{foreach $gXrefInfo->mGroups as $xrefGroup}
						{if $xrefGroup->mXGroup eq 'kitlocker'}
							{assign var=klGroup value=$xrefGroup}
						{else}
							{include file=$gContent->getXrefListTemplate($xrefGroup->mTemplate)
								xrefGroup=$xrefGroup allow_add=true allow_edit=true}
						{/if}
					{/foreach}
					{if $isKitlocker && $klGroup}
						{include file=$gContent->getXrefListTemplate($klGroup->mTemplate)
							xrefGroup=$klGroup allow_add=true allow_edit=true}
					{/if}
				{/jstabs}
			{/if}

			<div class="form-group submit">
				<input type="submit" class="btn btn-primary" name="save" value="{tr}Save Component{/tr}"/>
			</div>
		{/form}
	</section>
</div>
{/strip}
