{strip}
<div class="edit stock">
	<div class="header">
		<h1>{tr}Add Component{/tr}: {$gContent->getTitle()|escape}</h1>
	</div>

	<div class="body">
		{formfeedback error=$errors}

		{form id="addComponentForm" ipackage="stock" ifile="add_movement_component.php"}
			<input type="hidden" name="content_id" value="{$gContent->mContentId}" />

			<div class="form-group">
				{formlabel label="Component" for="component_title" mandatory="y"}
				{forminput}
					<input type="hidden" name="component_id" id="component_id" value="" />
					<div style="position:relative">
						<input type="text" class="form-control" name="component_title" id="component_title"
							autocomplete="off"
							value="{$smarty.request.component_title|default:''|escape}"
							placeholder="{tr}Type to search…{/tr}" />
						<ul id="comp_dropdown" class="dropdown-menu"
							style="display:none;position:absolute;width:100%;z-index:1000;max-height:220px;overflow-y:auto"></ul>
					</div>
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
					<input type="text" class="form-control" name="xkey" id="xkey"
						value="{$smarty.request.xkey|default:''|escape}" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Ref designators" for="xkey_ext"}
				{forminput}
					<input type="text" class="form-control" name="xkey_ext" id="xkey_ext"
						value="{$smarty.request.xkey_ext|default:''|escape}" />
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Note" for="edit"}
				{forminput}
					<input type="text" class="form-control" name="edit" id="edit"
						value="{$smarty.request.edit|default:''|escape}" />
				{/forminput}
			</div>

			<div class="form-group submit">
				<input type="submit" class="btn btn-default" name="fCancel" value="{tr}Cancel{/tr}" />
				<input type="submit" class="btn btn-primary" name="fAddComponent" value="{tr}Add Component{/tr}" />
			</div>
		{/form}
	</div>
</div>
{/strip}
<script>
(function($) {
	BitComponentTypeahead({
		input:     '#component_title',
		dropdown:  '#comp_dropdown',
		idField:   '#component_id',
		lookupUrl: '{$lookupUrl}'
	});
}(jQuery));
</script>
