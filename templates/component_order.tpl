{strip}
<div class="admin stock">
	<div class="header">
		<h1>{tr}Order Components{/tr}: <a href="{$smarty.const.STOCK_PKG_URL}view.php?assembly_id={$gContent->mAssemblyId}">{$gContent->getTitle()|escape}</a></h1>
	</div>

	<div class="body">
		<p class="formhelp">{tr}Assign each component a position to control which package it belongs to and its order within that package. The integer part of the position number is the package group (e.g. 1, 2, 3) and the decimal part is the order within the package (e.g. 1.010, 1.020, 1.030). Count by tens within each package so components can be inserted later without renumbering.{/tr}</p>

		{formfeedback hash=$formfeedback}

		{form id="batch_order" legend="Order Components"}
			<input type="hidden" name="assembly_id" value="{$gContent->mAssemblyId}"/>

			<table class="table table-striped data">
				<thead>
					<tr>
						<th>{tr}Component{/tr}</th>
						<th style="width:8em;">{tr}Position{/tr}</th>
						<th style="width:4em;">{tr}Batch{/tr}</th>
					</tr>
				</thead>
				<tbody>
				{assign var=lastPackage value=-1}
				{foreach from=$gContent->mItems item=galItem key=itemContentId}
					{assign var=thisPackage value=$galItem->getField('item_position')|default:0|floor}
					{if $thisPackage != $lastPackage}
					<tr class="active">
						<th colspan="3">
							{if $thisPackage > 0}
								{tr}Package{/tr} {$thisPackage}
							{else}
								{tr}Unassigned{/tr}
							{/if}
						</th>
					</tr>
					{assign var=lastPackage value=$thisPackage}
					{/if}
					<tr>
						<td>
							<input type="text" class="form-control" name="item_title[{$galItem->mContentId}]" value="{$galItem->getTitle()|escape}" />
							{if $galItem->mInfo.data}
								<small class="text-muted">{$galItem->mInfo.data|truncate:120|escape}</small>
							{/if}
							<div class="small">
								<a href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$galItem->mInfo.content_id}">{tr}Edit{/tr}</a>
							</div>
						</td>
						<td>
							<input type="text" class="form-control input-sm" size="6" name="itemPosition[{$galItem->mContentId}]" value="{$galItem->mInfo.item_position|default:0}" />
						</td>
						<td class="text-center">
							<input type="checkbox" name="batch[]" value="{$galItem->mContentId}" {if $batchEdit.$itemContentId|default:'' ne ''}checked="checked"{/if}/>
						</td>
					</tr>
				{foreachelse}
					<tr class="norecords"><td colspan="3">{tr}No components in this assembly{/tr}</td></tr>
				{/foreach}
				</tbody>
			</table>

			<div class="form-group">
				{formlabel label="Batch command" for="batch_command"}
				{forminput}
					<select name="batch_command" id="batch_command" class="form-control">
						<option value=""></option>
						<option value="delete">{tr}Delete{/tr}</option>
						<option value="remove">{tr}Remove from this assembly{/tr}</option>
						{if count($assemblyList) > 1}
							<optgroup label="{tr}Copy to Assembly{/tr}">
								{foreach from=$assemblyList item=asm key=asmId}
									{if $gContent->mInfo.content_id ne $asm.content_id}
										<option value="gallerycopy:{$asm.content_id}">&raquo; {$asm.title|escape|truncate:50}</option>
									{/if}
								{/foreach}
							</optgroup>
							<optgroup label="{tr}Move to Assembly{/tr}">
								{foreach from=$assemblyList item=asm key=asmId}
									{if $gContent->mInfo.content_id ne $asm.content_id}
										<option value="gallerymove:{$asm.content_id}">&rarr; {$asm.title|escape|truncate:50}</option>
									{/if}
								{/foreach}
							</optgroup>
						{/if}
					</select>
					<script>
						document.write('<label class="checkbox-inline"><input type="checkbox" onclick="BitBase.switchCheckboxes(this.form.id,\'batch[]\',\'switcher\')" id="switcher"> {tr}Select all{/tr}</label>');
					</script>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Auto-sort by" for="reorder_gallery"}
				{forminput}
					<select name="reorder_gallery" id="reorder_gallery" class="form-control">
						<option value=""></option>
						<option value="title">{tr}Title{/tr}</option>
						<option value="random">{tr}Random{/tr}</option>
					</select>
					{formhelp note="Resets positions for all components in this assembly."}
				{/forminput}
			</div>

			<div class="form-group submit">
				<input type="submit" class="btn btn-default" name="cancel" value="{tr}Back{/tr}" />
				<input type="submit" class="btn btn-primary" name="save_order" value="{tr}Save{/tr}" />
			</div>
		{/form}
	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
