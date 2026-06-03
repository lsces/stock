{strip}
<div class="edit stock">
	<div class="header">
		<h1>{tr}Add Component{/tr}: {$gContent->getTitle()|escape}</h1>
	</div>

	<div class="body">
		{formfeedback error=$errors}

		{form id="addComponentForm" ipackage="stock" ifile="add_component.php"}
			<input type="hidden" name="content_id" value="{$gContent->mContentId}"/>

			<div class="form-group">
				{formlabel label="Component" for="component_title" mandatory="y"}
				{forminput}
					<input type="text" class="form-control" name="component_title" id="component_title"
						autocomplete="off" list="component_suggestions"
						value="{$smarty.request.component_title|default:''|escape}" />
					<datalist id="component_suggestions"></datalist>
					{formhelp note="Type to search existing components, or enter a new title to create one."}
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Qty Type" for="item"}
				{forminput}
					<select name="item" id="item" class="form-control">
						{foreach from=$validItems key=code item=label}
							<option value="{$code}"{if $smarty.request.item|default:'SGL' eq $code} selected="selected"{/if}>{$label|escape}</option>
						{/foreach}
					</select>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Qty" for="xkey"}
				{forminput}
					<input type="text" class="form-control" name="xkey" id="xkey" value="{$smarty.request.xkey|default:''|escape}" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Ref designators" for="xkey_ext"}
				{forminput}
					<input type="text" class="form-control" name="xkey_ext" id="xkey_ext" value="{$smarty.request.xkey_ext|default:''|escape}" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Note" for="edit"}
				{forminput}
					<input type="text" class="form-control" name="edit" id="edit" value="{$smarty.request.edit|default:''|escape}" />
				{/forminput}
			</div>

			<div class="form-group submit">
				<input type="submit" class="btn btn-default" name="fCancel" value="{tr}Cancel{/tr}"/>
				<input type="submit" class="btn btn-primary" name="fAddComponent" value="{tr}Add Component{/tr}"/>
			</div>
		{/form}
	</div><!-- end .body -->
</div><!-- end .stock -->
<script>
(function($) {
	var timer;
	$('#component_title').on('input', function() {
		var q = $(this).val();
		clearTimeout(timer);
		if (q.length < 2) { $('#component_suggestions').empty(); return; }
		timer = setTimeout(function() {
			$.getJSON('{$lookupUrl}', {ldelim}q: q{rdelim}, function(data) {
				var dl = $('#component_suggestions').empty();
				$.each(data, function(i, row) {
					dl.append($('<option>').val(row.title));
				});
			});
		}, 250);
	});
}(jQuery));
</script>
{/strip}
