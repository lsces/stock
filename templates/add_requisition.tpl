{strip}
<div class="edit stock">
	<div class="header">
		<h1>{tr}Create Requisition{/tr}</h1>
	</div>

	<div class="body">
		{formfeedback error=$errors}

		{form id="addRequisitionForm" ipackage="stock" ifile="add_requisition.php"}

			<div class="form-group">
				{formlabel label="RQ Number" for="title" mandatory="y"}
				{forminput}
					<input type="text" class="form-control" name="title" id="title"
						value="{$smarty.request.title|escape}" placeholder="RQ-2026-001" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Ordered" for="ordered_date"}
				{forminput}
					<input type="text" class="form-control input-small" name="ordered_date" id="ordered_date"
						placeholder="dd/mm/yyyy" maxlength="10"
						value="{$smarty.request.ordered_date|default:$todayFormatted|escape}" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Item" for="assembly_search" mandatory="y"}
				{forminput}
					<input type="hidden" name="assembly_content_id" id="assembly_content_id"
						value="{$preselect|default:''|escape}" />
					<div style="position:relative">
						<input type="text" class="form-control" id="assembly_search"
							autocomplete="off"
							value="{$preselectTitle|escape}"
							placeholder="Type to search…" />
						<ul id="assembly_dropdown" class="dropdown-menu"
							style="display:none;position:absolute;width:100%;z-index:1000;max-height:220px;overflow-y:auto"></ul>
					</div>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Qty" for="kit_count"}
				{forminput}
					<input type="number" class="form-control input-sm" name="kit_count"
						id="kit_count" min="1" step="1" style="width:6em"
						value="{$kitCount|escape}" />
				{/forminput}
			</div>

			<div class="form-group submit">
				<input type="submit" class="btn btn-default" name="fCancel"  value="{tr}Cancel{/tr}" />
				<input type="submit" class="btn btn-primary" name="fCreate"  value="{tr}Create Requisition{/tr}" />
			</div>

		{/form}
	</div>
</div>
{/strip}
<script>
(function($) {
	var items = {$itemListJson};
	var $input  = $('#assembly_search');
	var $hidden = $('#assembly_content_id');
	var $dd     = $('#assembly_dropdown');

	$input.on('input', function() {
		var q = this.value.toLowerCase().trim();
		$dd.hide().empty();
		$hidden.val('');
		if (!q) return;
		var matched = items.filter(function(i) {
			return i.text.toLowerCase().indexOf(q) !== -1 || (i.klid && i.klid.toLowerCase().indexOf(q) !== -1);
		});
		if (!matched.length) return;
		matched.forEach(function(i) {
			var label = i.text + (i.klid ? ' (' + i.klid + ')' : '');
			$dd.append($('<li>').append(
				$('<a>').attr('href', '#').data('id', i.id).data('text', i.text).text(label)
			));
		});
		$dd.show();
	});

	$(document).on('mousedown', '#assembly_dropdown a', function(e) {
		e.preventDefault();
		$input.val($(this).data('text'));
		$hidden.val($(this).data('id'));
		$dd.hide().empty();
	});

	$input.on('blur', function() {
		setTimeout(function() { $dd.hide(); }, 150);
	});

	$input.on('keydown', function(e) {
		if (!$dd.is(':visible')) return;
		var $links = $dd.find('a');
		var idx    = $links.index($dd.find('li.active a'));
		if (e.key === 'ArrowDown') {
			e.preventDefault();
			$links.parent().removeClass('active');
			$links.eq(idx + 1 < $links.length ? idx + 1 : 0).parent().addClass('active');
		} else if (e.key === 'ArrowUp') {
			e.preventDefault();
			$links.parent().removeClass('active');
			$links.eq(idx > 0 ? idx - 1 : $links.length - 1).parent().addClass('active');
		} else if (e.key === 'Enter') {
			var $active = $dd.find('li.active a');
			if ($active.length) { e.preventDefault(); $active.trigger('mousedown'); }
		} else if (e.key === 'Escape') {
			$dd.hide();
		}
	});
}(jQuery));
</script>
