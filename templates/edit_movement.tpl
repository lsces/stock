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

		{form id="editMovementForm" ipackage="stock" ifile="edit_movement.php"}
			<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}" />

			<div class="form-group">
				{formlabel label="Reference" for="title" mandatory="y"}
				{forminput}
					<input type="text" class="form-control input-xlarge" name="title" id="title"
						value="{$gContent->getTitle()|escape}" maxlength="160" />
					{formhelp note="Movement reference — e.g. REQ-2026-001"}
				{/forminput}
			</div>

			{if !$gContent->isValid() && $refTypes}
			<div class="form-group">
				{formlabel label="Movement Type" mandatory="y"}
				{forminput}
					{foreach from=$refTypes key=item item=label}
						<label class="radio">
							<input type="radio" name="movement_type" value="{$item|escape}"
								{if $smarty.foreach.default.first} checked="checked"{/if} /> {$label|escape}
						</label>
					{/foreach}
				{/forminput}
			</div>
			{/if}

			<div class="form-group submit">
				<input type="submit" class="btn btn-primary" name="fSave" value="{tr}Save{/tr}" />
				{if $gContent->isValid()}
					{if !$gContent->isReceived()}
						<input type="submit" class="btn btn-success" name="fReceived" value="{tr}Mark Received{/tr}"
							onclick="return confirm('{tr}Mark this movement as received?{/tr}')" />
					{else}
						<span class="label label-success">{tr}Received{/tr}</span>
					{/if}
					<input type="submit" class="btn btn-danger pull-right" name="delete" value="{tr}Delete{/tr}" />
				{/if}
			</div>
		{/form}

		{if $gContent->isValid()}

			{* ── Upload CSV ── *}
			{if !$gContent->isReceived()}
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

			{* ── Xref tabs — items and references ── *}
			{if $gContent->mInfo.movement_xref_groups}
				{jstabs}
					{section name=xrefGroup loop=$gContent->mInfo.movement_xref_groups}
						{include file=$gContent->getXrefListTemplate($gContent->mInfo.movement_xref_groups[xrefGroup].template)
							source=$gContent->mInfo.movement_xref_groups[xrefGroup].source
							source_title=$gContent->mInfo.movement_xref_groups[xrefGroup].title
							group=$gContent->mInfo.movement_xref_groups[xrefGroup].sort_order
							allow_add=true
							allow_edit=true}
					{/section}
				{/jstabs}
			{/if}

		{/if}

	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
