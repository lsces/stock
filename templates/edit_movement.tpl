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

			{if !$isReqn}
			{if $refTypes}
			<div class="form-group">
				{formlabel label="Movement Type" mandatory="y"}
				{forminput}
					{foreach from=$refTypes key=item item=label}
						<label class="radio-inline">
							<input type="radio" name="movement_type" value="{$item|escape}"
								{if ($gContent->mInfo.ref_type|default:'TRANS') eq $item} checked="checked"{/if} /> {$label|escape}
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
					<div style="position:relative">
						<input type="text" class="form-control" name="ref_from" id="ref_from"
							autocomplete="off"
							value="{if $gContent->mInfo.ref_contact_name}{$gContent->mInfo.ref_contact_name|escape}{else}{$gContent->mInfo.ref_from_data|default:''|escape}{/if}"
							maxlength="160" placeholder="Type to search contacts…" />
						<ul id="contact_dropdown" class="dropdown-menu"
							style="display:none;position:absolute;width:100%;z-index:1000;max-height:220px;overflow-y:auto"></ul>
					</div>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Ref Key" for="ref_key"}
				{forminput}
					<input type="text" class="form-control" name="ref_key" id="ref_key"
						value="{$gContent->mInfo.ref_key|default:''|escape}" maxlength="160" />
				{/forminput}
			</div>
			{/if}

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

			{if $gXrefInfo->mGroups}
				{jstabs}
					{foreach $gXrefInfo->mGroups as $xrefGroup}
						{if $xrefGroup->mXGroup neq 'reference' && ($xrefGroup->mXGroup neq 'assembly' || $isReqn)}
							{include file=$gContent->getXrefListTemplate($xrefGroup->mTemplate)
								xrefGroup=$xrefGroup
								allow_add=true
								allow_edit=true}
						{/if}
					{/foreach}
				{/jstabs}
			{/if}

			{if !$isReqn}
			{* ── Upload CSV (orders/transfers only) ── *}
			<h4>{tr}Upload CSV{/tr}</h4>
			{form enctype="multipart/form-data" ipackage="stock" ifile="edit_movement.php"}
				<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}" />
				<div class="form-inline">
					<input type="file" name="csv_file" accept=".csv,text/csv" />
					<input type="submit" class="btn btn-default" name="upload_csv" value="{tr}Upload{/tr}" />
				</div>
			{/form}
			{/if}

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

		{/if}

	</div><!-- end .body -->
</div><!-- end .stock -->
<script>
(function($) {
	var timer, contacts = [];
	var $input = $('#ref_from');
	var $hidden = $('#ref_contact_id');
	var $dd = $('#contact_dropdown');

	$input.on('input', function() {
		var q = $(this).val();
		clearTimeout(timer);
		$dd.hide().empty();
		contacts = [];
		if (q.length < 2) return;
		timer = setTimeout(function() {
			$.getJSON('{$contactLookupUrl}', {ldelim}q: q{rdelim}, function(data) {
				contacts = data;
				if (!data.length) return;
				$.each(data, function(i, row) {
					var label = row.title + (row.scref ? ' (' + row.scref + ')' : '');
					$dd.append($('<li>').append(
						$('<a>').attr('href','#').data('id', row.content_id).data('label', label).text(label)
					));
				});
				$dd.show();
			});
		}, 250);
	});

	$(document).on('mousedown', '#contact_dropdown a', function(e) {
		e.preventDefault();
		$input.val($(this).data('label'));
		$hidden.val($(this).data('id'));
		$dd.hide().empty();
		contacts = [];
	});

	$input.on('blur', function() {
		setTimeout(function() { $dd.hide(); }, 150);
	});

	$input.on('keydown', function(e) {
		if (!$dd.is(':visible')) return;
		var $items = $dd.find('a');
		var idx = $dd.find('li.active a').length ? $items.index($dd.find('li.active a')) : -1;
		if (e.key === 'ArrowDown') {
			e.preventDefault();
			$items.parent().removeClass('active');
			$items.eq(idx + 1 < $items.length ? idx + 1 : 0).parent().addClass('active');
		} else if (e.key === 'ArrowUp') {
			e.preventDefault();
			$items.parent().removeClass('active');
			$items.eq(idx > 0 ? idx - 1 : $items.length - 1).parent().addClass('active');
		} else if (e.key === 'Enter') {
			var $active = $dd.find('li.active a');
			if ($active.length) { e.preventDefault(); $active.trigger('mousedown'); }
		} else if (e.key === 'Escape') {
			$dd.hide();
		}
	});
}(jQuery));
</script>
{/strip}
