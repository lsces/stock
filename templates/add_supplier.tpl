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
				{formlabel label="Supplier" for="sup_search"}
				{forminput}
					<input type="hidden" name="supplier_content_id" id="supplier_content_id" value="" />
					<div style="position:relative">
						<input type="text" class="form-control" id="sup_search"
							autocomplete="off" placeholder="{tr}Type to search contacts…{/tr}" />
						<ul id="sup_dropdown" class="dropdown-menu"
							style="display:none;position:absolute;width:100%;z-index:1000;max-height:220px;overflow-y:auto"></ul>
					</div>
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
	</div>
</div>
{/strip}
<script>
(function($) {
	var timer, contacts = [];
	var $input  = $('#sup_search');
	var $hidden = $('#supplier_content_id');
	var $dd     = $('#sup_dropdown');

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

	$(document).on('mousedown', '#sup_dropdown a', function(e) {
		e.preventDefault();
		$input.val($(this).data('label'));
		$hidden.val($(this).data('id'));
		$dd.hide().empty();
		contacts = [];
	});

	$input.on('blur', function() { setTimeout(function() { $dd.hide(); }, 150); });

	$input.on('keydown', function(e) {
		if (!$dd.is(':visible')) return;
		var $links = $dd.find('a'), idx = $links.index($dd.find('li.active a'));
		if (e.key === 'ArrowDown') { e.preventDefault(); $links.parent().removeClass('active'); $links.eq(idx + 1 < $links.length ? idx + 1 : 0).parent().addClass('active'); }
		else if (e.key === 'ArrowUp') { e.preventDefault(); $links.parent().removeClass('active'); $links.eq(idx > 0 ? idx - 1 : $links.length - 1).parent().addClass('active'); }
		else if (e.key === 'Enter') { var $a = $dd.find('li.active a'); if ($a.length) { e.preventDefault(); $a.trigger('mousedown'); } }
		else if (e.key === 'Escape') { $dd.hide(); }
	});
}(jQuery));
</script>
