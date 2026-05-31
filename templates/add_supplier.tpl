{strip}
<div class="edit stock">
	<div class="header">
		<h1>{tr}Add Supplier{/tr}: {$gContent->getTitle()|escape}</h1>
	</div>

	<div class="body">
		{formfeedback error=$errors}

		{form id="addSupplierForm"}
			<input type="hidden" name="content_id" value="{$gContent->mContentId}" />

			<div class="form-group">
				{formlabel label="Supplier" for="supplier_content_id"}
				{forminput}
					<select name="supplier_content_id" id="supplier_content_id" class="form-control">
						<option value="">{tr}-- Select supplier --{/tr}</option>
						{foreach from=$supplierList item=sup}
							<option value="{$sup.content_id|escape}">{$sup.title|escape}</option>
						{/foreach}
					</select>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Part Number" for="part_number"}
				{forminput}
					<input type="text" class="form-control input-small" name="part_number" id="part_number" value="" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Price" for="price"}
				{forminput}
					<input type="text" class="form-control input-small" name="price" id="price" value="" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Note" for="note"}
				{forminput}
					<input type="text" class="form-control" name="note" id="note" value="" />
				{/forminput}
			</div>

			<div class="form-group submit">
				<input type="submit" class="btn btn-default" name="fCancel"      value="{tr}Cancel{/tr}" />
				<input type="submit" class="btn btn-primary" name="fAddSupplier" value="{tr}Save{/tr}" />
			</div>
		{/form}
	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
