{strip}
<div class="edit stock">
	<div class="header">
		<h1>{tr}Edit Item{/tr}: {$gContent->getTitle()|escape}</h1>
	</div>
	<div class="body">
		{formfeedback error=$errors}
		{form id="editXrefForm"}
			<input type="hidden" name="content_id" value="{$xrefInfo.content_id|escape}" />
			<input type="hidden" name="xref_id"    value="{$xrefInfo.xref_id|escape}" />
			<input type="hidden" name="item"       value="{$xrefInfo.item|escape}" />
			<input type="hidden" name="xorder"     value="{$xrefInfo.xorder|escape}" />

			<div class="form-group">
				{formlabel label="Component"}
				{forminput}
					<p class="form-control-static">
						<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$xrefInfo.xref|escape}">{$xrefInfo.xref_title|default:$xrefInfo.xref|escape}</a>
					</p>
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

			<div class="form-group">
				{formlabel label="Note" for="edit"}
				{forminput}
					<input type="text" class="form-control" name="edit" id="edit" value="{$xrefInfo.data|escape}" />
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
