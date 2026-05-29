{strip}
<div class="edit stock">
	<div class="header">
		<h1>{tr}Edit BOM Entry{/tr}: {$gContent->getTitle()|escape}</h1>
	</div>
	<div class="body">
		{formfeedback error=$errors}
		{form id="editXrefForm"}
			<input type="hidden" name="content_id" value="{$xrefInfo.content_id|escape}" />
			<input type="hidden" name="xref_id"    value="{$xrefInfo.xref_id|escape}" />
			<input type="hidden" name="item"       value="{$xrefInfo.item|escape}" />

			<div class="form-group">
				{formlabel label="Component"}
				{forminput}
					<p class="form-control-static">
						<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?component_id={$xrefInfo.xref|escape}">{$xrefInfo.xref_title|default:$xrefInfo.xref|escape}</a>
					</p>
				{/forminput}
			</div>

			{if $xrefInfo.xref_data}
			<div class="form-group">
				{formlabel label="Description"}
				{forminput}
					<p class="form-control-static">{$xrefInfo.xref_data|escape}</p>
				{/forminput}
			</div>
			{/if}

			<div class="form-group">
				{formlabel label="Pieces required" for="xkey"}
				{forminput}
					<div class="form-inline">
						<input type="text" class="form-control input-small" name="xkey" id="xkey" value="{$xrefInfo.xkey|escape}" />
						{if $xrefInfo.pack_size} of {$xrefInfo.pack_size|escape}{if $xrefInfo.pack_size_ext} {$xrefInfo.pack_size_ext|escape}{/if}{/if}
					</div>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Ref designators" for="xkey_ext"}
				{forminput}
					<input type="text" class="form-control" name="xkey_ext" id="xkey_ext" value="{$xrefInfo.xkey_ext|escape}" />
				{/forminput}
			</div>

			<div class="form-group submit">
				<input type="submit" class="btn btn-default" name="fCancel"   value="{tr}Cancel{/tr}" />
				<input type="submit" class="btn btn-primary" name="fSaveXref" value="{tr}Save{/tr}" />
			</div>
		{/form}
	</div>
</div>
{/strip}
