{strip}
<div class="edit stock">
	<div class="header">
		<h1>
			{if $gContent->isValid()}
				{tr}Edit Movement{/tr}: {$gContent->getTitle()|escape}
			{else}
				{tr}Create Movement{/tr}
			{/if}
		</h1>
	</div>

	<div class="body">
		{formfeedback error=$errors}

		{form id="editMovementForm" enctype="multipart/form-data" ipackage="stock" ifile="edit_movement.php"}
			<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}" />

			<div class="form-group">
				{formlabel label="Reference" for="title" mandatory="y"}
				{forminput}
					<input type="text" class="form-control input-xlarge" name="title" id="title"
						value="{$gContent->getTitle()|escape}" maxlength="160" />
				{/forminput}
			</div>

			{if $refTypes}
			<div class="form-group">
				{formlabel label="Movement Type" mandatory="y"}
				{forminput}
					{foreach from=$refTypes key=item item=label}
						<label class="radio-inline">
							<input type="radio" name="movement_type" value="{$item|escape}"
								{if $refRow.item eq $item} checked="checked"
								{elseif !$refRow && $smarty.foreach.default.first} checked="checked"{/if} /> {$label|escape}
						</label>
					{/foreach}
				{/forminput}
			</div>
			{/if}

			<div class="form-group">
				{formlabel label="From" for="ref_from"}
				{forminput}
					<input type="hidden" name="ref_contact_id" id="ref_contact_id"
						value="{$gContent->mInfo.ref_contact_id|default:''|escape}" />
					<input type="text" class="form-control" name="ref_from" id="ref_from"
						autocomplete="off" list="contact_suggestions"
						value="{if $gContent->mInfo.ref_contact_name}{$gContent->mInfo.ref_contact_name|escape}{else}{$refRow.data|default:''|escape}{/if}"
						maxlength="160" placeholder="Type to search contacts…" />
					<datalist id="contact_suggestions"></datalist>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Ref Key" for="ref_key"}
				{forminput}
					<input type="text" class="form-control" name="ref_key" id="ref_key"
						value="{$refRow.xkey|default:''|escape}" maxlength="160" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Ordered" for="ordered_date"}
				{forminput}
					<input type="text" class="form-control input-small" name="ordered_date" id="ordered_date"
						placeholder="dd/mm/yyyy" value="{$orderedDateVal|escape}" maxlength="10" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Received" for="received_date"}
				{forminput}
					<input type="text" class="form-control input-small" name="received_date" id="received_date"
						placeholder="dd/mm/yyyy" value="{$receivedDateVal|escape}" maxlength="10" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Note" for="edit"}
				{forminput}
					<textarea class="form-control" name="edit" id="edit" rows="2">{$gContent->mInfo.data|default:''|escape}</textarea>
				{/forminput}
			</div>

			{if !$gContent->isValid()}
			<div class="form-group">
				{formlabel label="Load CSV"}
				{forminput}
					<input type="file" name="csv_file" accept=".csv,text/csv" />
					{formhelp note="Optional — upload movement CSV at creation time"}
				{/forminput}
			</div>
			{/if}

			<div class="form-group submit">
				<input type="submit" class="btn btn-primary" name="fSave" value="{tr}Save{/tr}" />
				{if $gContent->isValid() && $gBitUser->hasPermission('p_stock_expunge')}
					<input type="submit" class="btn btn-danger pull-right" name="delete" value="{tr}Delete{/tr}" />
				{/if}
			</div>
		{/form}

		{if $gContent->isValid()}

			{* ── Upload CSV ── *}
			<h4>{tr}Upload CSV{/tr}</h4>
			{form enctype="multipart/form-data" ipackage="stock" ifile="edit_movement.php"}
				<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}" />
				<div class="form-inline">
					<input type="file" name="csv_file" accept=".csv,text/csv" />
					<input type="submit" class="btn btn-default" name="upload_csv" value="{tr}Upload{/tr}" />
				</div>
			{/form}

			{* ── Upload results ── *}
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

			{* ── Xref tabs — quantity BOM only ── *}
			{if $gContent->mInfo.movement_xref_groups}
				{jstabs}
					{section name=xrefGroup loop=$gContent->mInfo.movement_xref_groups}
						{include file=$gContent->getXrefListTemplate($gContent->mInfo.movement_xref_groups[xrefGroup].template)
							source=$gContent->mInfo.movement_xref_groups[xrefGroup].source
							source_title=$gContent->mInfo.movement_xref_groups[xrefGroup].title
							group=$gContent->mInfo.movement_xref_groups[xrefGroup].sort_order
							allow_add=true
							allow_edit=true}
					{/section}
				{/jstabs}
			{/if}

		{/if}

	</div><!-- end .body -->
</div><!-- end .stock -->
<script>
(function($) {
	var timer, contacts = [];
	$('#ref_from').on('input', function() {
		var q = $(this).val();
		clearTimeout(timer);
		if (q.length < 2) { $('#contact_suggestions').empty(); contacts = []; return; }
		timer = setTimeout(function() {
			$.getJSON('{$contactLookupUrl}', {ldelim}q: q{rdelim}, function(data) {
				contacts = data;
				var dl = $('#contact_suggestions').empty();
				$.each(data, function(i, row) {
					var label = row.title + (row.scref ? ' (' + row.scref + ')' : '');
					dl.append($('<option>').val(label).attr('data-id', row.content_id));
				});
			});
		}, 250);
	}).on('change', function() {
		var val = $(this).val(), found = null;
		$.each(contacts, function(i, row) {
			var label = row.title + (row.scref ? ' (' + row.scref + ')' : '');
			if (label === val || row.title === val || row.scref === val) { found = row; return false; }
		});
		$('#ref_contact_id').val(found ? found.content_id : '');
	});
}(jQuery));
</script>
{/strip}
