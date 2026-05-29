{strip}
<div class="edit liberty">
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
				{formlabel label="Type"}
				{forminput}
					<p class="form-control-static">{$xrefInfo.source_title|escape}</p>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Component ID" for="xref"}
				{forminput}
					<input type="text" class="form-control input-small" name="xref" id="xref" value="{$xrefInfo.xref|escape}" />
					{formhelp note="component_id of the stock component"}
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Quantity" for="xkey"}
				{forminput}
					<input type="text" class="form-control input-small" name="xkey" id="xkey" value="{$xrefInfo.xkey|escape}" />
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
