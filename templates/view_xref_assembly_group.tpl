{assign var=xrefAllowEdit value=$allow_edit|default:false}
{assign var=xrefAllowAdd  value=$allow_add|default:false}
{assign var=isHistory value=($xrefGroup->mXGroup eq 'history')}
{jstab title="{tr}Assembly{/tr} ({$xrefGroup->mXrefs|@count})"}
{legend legend="Assembly"}
<div class="form-group table-responsive">
	<table class="table">
		<thead>
			<tr>
				<th>{tr}Item{/tr}</th>
				<th>{tr}Type{/tr}</th>
				<th>{tr}Qty{/tr}</th>
				{if $xrefAllowEdit}<th></th>{/if}
			</tr>
		</thead>
		<tbody>
			{if $xrefGroup->mXrefs}
				{foreach $xrefGroup->mXrefs as $xrefInfo}
					<tr class="{cycle values='even,odd'}">
						{include file=$gContent->getXrefRecordTemplate($xrefInfo.template)}
					</tr>
				{/foreach}
			{else}
				<tr class="norecords">
					<td colspan="{if $xrefAllowEdit}4{else}3{/if}">{tr}No items{/tr}</td>
				</tr>
			{/if}
		</tbody>
	</table>
</div>

{if $xrefAllowAdd && $itemListJson}
<hr />
<h5>{tr}Add item{/tr}</h5>
{form ipackage="stock" ifile="edit_movement.php"}
	<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}" />
	<div class="form-inline">
		<input type="hidden" name="assembly_content_id" id="asm_add_id" value="" />
		<div style="position:relative;display:inline-block;width:20em;vertical-align:top">
			<input type="text" class="form-control" id="asm_add_search"
				autocomplete="off" placeholder="{tr}Type to search…{/tr}" />
			<ul id="asm_add_dropdown" class="dropdown-menu"
				style="display:none;position:absolute;width:100%;z-index:1000;max-height:220px;overflow-y:auto"></ul>
		</div>
		&nbsp;
		<input type="number" class="form-control input-sm" name="kit_count"
			min="1" step="1" value="1" style="width:5em;display:inline-block" title="{tr}Qty / kits{/tr}" />
		&nbsp;
		<input type="submit" class="btn btn-default btn-sm" name="fAddAssembly" value="{tr}Add{/tr}" />
	</div>
{/form}
{/if}
{/legend}
{/jstab}
{if $xrefAllowAdd && $itemListJson}
<script>
(function($) {
	var items  = {$itemListJson};
	var $input  = $('#asm_add_search');
	var $hidden = $('#asm_add_id');
	var $dd     = $('#asm_add_dropdown');

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
				$('<a>').attr('href','#').data('id', i.id).data('text', i.text).text(label)
			));
		});
		$dd.show();
	});

	$(document).on('mousedown', '#asm_add_dropdown a', function(e) {
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
{/if}
