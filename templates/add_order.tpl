{strip}
<div class="listing stock">
	<header>
		<h1>{tr}Create Order{/tr}</h1>
	</header>

	<section class="body">

		{if $errors}
		<div class="alert alert-danger">
			{foreach $errors as $e}<p>{$e|escape}</p>{/foreach}
		</div>
		{/if}

		{form ipackage="stock" ifile="add_order.php" method="post"}
		<div class="form-horizontal">

			<div class="form-group">
				{formlabel label="Supplier" for="ref_from"}
				{forminput}
					<input type="hidden" name="supplier_contact_id" id="supplier_contact_id"
						value="{$supplierCidVal|escape}" />
					<div style="position:relative">
						<input type="text" class="form-control" name="ref_from" id="ref_from"
							autocomplete="off" placeholder="{tr}Type to search contacts…{/tr}"
							value="{$supplierVal|escape}" maxlength="160" />
						<ul id="contact_dropdown" class="dropdown-menu"
							style="display:none;position:absolute;width:100%;z-index:1000;max-height:220px;overflow-y:auto"></ul>
					</div>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Order Ref" for="ref_key"}
				{forminput}
					<input type="text" class="form-control" name="ref_key" id="ref_key"
						value="{$refKeyVal|escape}" maxlength="160" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Order Date" for="ordered_date"}
				{forminput}
					<input type="text" class="form-control input-small" name="ordered_date" id="ordered_date"
						placeholder="dd/mm/yyyy" value="{$orderedDateVal|escape}" maxlength="10" />
				{/forminput}
			</div>

		</div>

		{if $lines}
		<table class="table table-condensed" id="order-lines">
			<thead>
				<tr>
					<th>{tr}Component{/tr}</th>
					<th>{tr}Part No{/tr}</th>
					<th>{tr}Type{/tr}</th>
					<th class="text-right">{tr}Qty{/tr}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{foreach $lines as $line}
				<tr>
					<td>
						<input type="hidden" name="component_id[]" value="{$line.component_id|escape}" />
						<input type="hidden" name="qty_type[]" value="{$line.qty_type|escape}" />
						{$line.title|escape}
					</td>
					<td>{$line.part_number|escape}</td>
					<td>{$line.qty_type|escape}</td>
					<td class="text-right" style="width:7em">
						<input type="number" class="form-control input-sm text-right" name="qty[]"
							value="{$line.qty|string_format:'%g'|escape}" min="0.001" step="any"
							style="width:6em;display:inline-block" />
					</td>
					<td style="width:2em">
						<button type="button" class="btn btn-link btn-xs bw-del-row"
							title="{tr}Remove line{/tr}">&#x2715;</button>
					</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
		{else}
		<p class="muted">{tr}No shortage lines found.{/tr}</p>
		{/if}

		<div class="form-group">
			<button type="submit" name="fCreate" value="1" class="btn btn-primary">{tr}Create Order{/tr}</button>
			<button type="submit" name="fCancel" value="1" class="btn btn-default">{tr}Cancel{/tr}</button>
		</div>

		{/form}

	</section>
</div>
{/strip}
<script>
(function($) {
	$(document).on('click', '.bw-del-row', function() {
		$(this).closest('tr').remove();
	});

	var timer, contacts = [];
	var $input  = $('#ref_from');
	var $hidden = $('#supplier_contact_id');
	var $dd     = $('#contact_dropdown');

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
			var $a = $dd.find('li.active a');
			if ($a.length) { e.preventDefault(); $a.trigger('mousedown'); }
		} else if (e.key === 'Escape') {
			$dd.hide();
		}
	});
}(jQuery));
</script>
