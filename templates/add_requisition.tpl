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
				{formlabel label="Assembly" for="assembly_content_id" mandatory="y"}
				{forminput}
					<select name="assembly_content_id" id="assembly_content_id" class="form-control">
						<option value="">{tr}— Select assembly —{/tr}</option>
						{foreach from=$assemblyList item=asm}
							<option value="{$asm.content_id|escape}"
								{if $preselect == $asm.content_id} selected="selected"{/if}>
								{$asm.title|escape}
							</option>
						{/foreach}
					</select>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Kits" for="kit_count"}
				{forminput}
					<input type="number" class="form-control input-sm" name="kit_count"
						id="kit_count" min="1" step="1" style="width:6em"
						value="{$kitCount|escape}" />
					{formhelp note="Number of assemblies to requisition"}
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
