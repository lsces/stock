{strip}
<div class="edit stock">
	<div class="header">
		<h1>
			{if $gContent->getTitle()}
				{tr}Edit Assembly{/tr}: {$gContent->getTitle()|escape}
			{else}
				{tr}Create Assembly{/tr}
			{/if}
		</h1>
	</div>

	<div class="body">
		{form id="editAssemblyForm" ipackage="stock" ifile="edit.php"}
			{formfeedback error=$errors warning=$stockWarnings success=$stockSuccess}

			<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}"/>

			<div class="form-group">
				{formlabel label="Title" for="assembly-title" mandatory="y"}
				{forminput}
					<input type="text" name="title" id="assembly-title" value="{$gContent->getTitle()|escape}" maxlength="160" size="50"/>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Description" for="assembly-desc"}
				{forminput}
					<textarea name="edit" id="assembly-desc" rows="4" cols="50">{$gContent->mInfo.data|default:''|escape}</textarea>
				{/forminput}
			</div>

			{if $gContent->mInfo.stockassembly_types}
				{jstabs}
					{section name=xrefGroup loop=$gContent->mInfo.stockassembly_types}
						{include file="bitpackage:liberty/list_xref.tpl"
							source=$gContent->mInfo.stockassembly_types[xrefGroup].source
							source_title=$gContent->mInfo.stockassembly_types[xrefGroup].title
							group=$gContent->mInfo.stockassembly_types[xrefGroup].sort_order
							allow_add=true}
					{/section}
				{/jstabs}
			{/if}

			<div class="form-group submit">
				{if $gContent->isValid()}
					<input type="submit" class="btn btn-default" name="cancelgallery" value="{tr}Cancel{/tr}"/>
				{/if}
				<input type="submit" class="btn btn-primary" name="savegallery" value="{tr}Save Assembly{/tr}"/>
			</div>
		{/form}

		{* ── Components section ── *}
		{if $gContent->isValid()}
			<hr/>
			<h3>{tr}Components{/tr}</h3>

			{* CSV upload results *}
			{if isset($csvLoaded)}
				<div class="alert alert-info">
					{tr}Loaded{/tr}: <strong>{$csvLoaded}</strong>
					{if $csvSkipped} &nbsp; {tr}Skipped (duplicate){/tr}: <strong>{$csvSkipped}</strong>{/if}
				</div>
				{if $csvErrors}
					<ul class="text-warning">
						{foreach from=$csvErrors item=msg}<li>{$msg|escape}</li>{/foreach}
					</ul>
				{/if}
			{/if}

			{* Components table *}
			{if $componentMap}
				{form ipackage="stock" ifile="edit.php"}
					<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}"/>
					<table class="table table-condensed table-striped">
						<thead>
							<tr>
								<th>{smartlink ititle="Pos" isort="item_position" ifile="edit.php" ipackage="stock" idefault=1 content_id=$gContent->mContentId}</th>
								<th>{smartlink ititle="Component" isort="title" ifile="edit.php" ipackage="stock" content_id=$gContent->mContentId}</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							{foreach from=$componentMap key=contentId item=comp}
								<tr>
									<td>{$comp.item_position|escape}</td>
									<td>{$comp.title|escape}</td>
									<td>
										<input type="submit" class="btn btn-xs btn-default"
											name="remove_component_{$contentId}" value="{tr}Remove{/tr}"/>
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				{/form}
			{else}
				<p class="muted">{tr}No components yet.{/tr}</p>
			{/if}

			{* ── Add single component ── *}
			<h4>{tr}Add Component{/tr}</h4>
			{form ipackage="stock" ifile="edit.php"}
				<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}"/>
				<div class="form-inline">
					<div class="form-group">
						<input type="text" class="form-control" name="component_title"
							placeholder="{tr}Component title{/tr}" size="40"/>
					</div>
					<input type="submit" class="btn btn-default" name="add_component" value="{tr}Add{/tr}"/>
				</div>
			{/form}

			{* ── Upload CSV ── *}
			<h4>{tr}Upload Components CSV{/tr}</h4>
			<p class="help-block">{tr}One component title per line (or comma-separated with title in first column).{/tr}</p>
			{form enctype="multipart/form-data" ipackage="stock" ifile="edit.php"}
				<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}"/>
				<div class="form-inline">
					<input type="file" name="csv_file" accept=".csv,text/csv"/>
					<input type="submit" class="btn btn-default" name="upload_components_csv" value="{tr}Upload{/tr}"/>
				</div>
			{/form}
		{/if}

	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
