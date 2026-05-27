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

		{* ── Header form ── *}
		{form ipackage="stock" ifile="edit_movement.php"}
			<input type="hidden" name="content_id"  value="{$gContent->mContentId|escape}"/>
			<input type="hidden" name="movement_id" value="{$gContent->mMovementId|escape}"/>

			<div class="form-group">
				{formlabel label="Title" for="movement-title" mandatory="y"}
				{forminput}
					<input type="text" class="form-control input-xlarge" name="title" id="movement-title"
						value="{$gContent->getTitle()|escape}" maxlength="160" size="50"
						{if $isComplete}disabled="disabled"{/if}/>
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Direction" for="movement-dir"}
				{forminput}
					{if $isComplete}
						<span class="form-control-static">
							{if $gContent->mInfo.direction eq 'I'}{tr}IN{/tr}{else}{tr}OUT{/tr}{/if}
						</span>
					{else}
						<select name="direction" id="movement-dir" class="form-control">
							<option value="I"{if $gContent->mInfo.direction eq 'I'} selected="selected"{/if}>{tr}IN{/tr}</option>
							<option value="O"{if $gContent->mInfo.direction eq 'O'} selected="selected"{/if}>{tr}OUT{/tr}</option>
						</select>
					{/if}
				{/forminput}
			</div>

			<div class="form-group">
				{formlabel label="Status" for="movement-status"}
				{forminput}
					{if $isComplete}
						<span class="form-control-static">{tr}Complete{/tr}</span>
					{else}
						<select name="status" id="movement-status" class="form-control">
							{foreach from=$statusOptions key=val item=label}
								<option value="{$val}"{if ($gContent->mInfo.status|default:'draft') eq $val} selected="selected"{/if}>{tr}{$label}{/tr}</option>
							{/foreach}
						</select>
					{/if}
				{/forminput}
			</div>

			{if !$isComplete}
				<div class="form-group submit">
					<input type="submit" class="btn btn-primary" name="save" value="{tr}Save Movement{/tr}"/>
					{if $gContent->isValid() && $isPending}
						<input type="submit" class="btn btn-success" name="process" value="{tr}Process — Commit Stock{/tr}"
							onclick="return confirm('{tr}Commit this movement to stock? This cannot be undone.{/tr}')"/>
					{/if}
					{if $gContent->isValid()}
						<input type="submit" class="btn btn-danger pull-right" name="delete" value="{tr}Delete Movement{/tr}"/>
					{/if}
				</div>
			{/if}
		{/form}

		{* ── Items section ── *}
		{if $gContent->isValid()}
			<hr/>
			<h3>{tr}Items{/tr}</h3>

			{* CSV upload results *}
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

			{* Items table *}
			{if $gContent->mInfo.items}
				{form ipackage="stock" ifile="edit_movement.php"}
					<input type="hidden" name="content_id"  value="{$gContent->mContentId|escape}"/>
					<input type="hidden" name="movement_id" value="{$gContent->mMovementId|escape}"/>
					<table class="table table-condensed table-striped">
						<thead>
							<tr>
								<th>{tr}Component{/tr}</th>
								<th>{tr}Qty{/tr}</th>
								<th>{tr}Type{/tr}</th>
								{if !$isComplete}<th></th>{/if}
							</tr>
						</thead>
						<tbody>
							{foreach from=$gContent->mInfo.items key=itemId item=item}
								<tr>
									<td>{$item.title|escape}</td>
									<td>{$item.quantity_value|escape}</td>
									<td>{$item.quantity_item|escape}</td>
									{if !$isComplete}
										<td>
											<input type="submit" class="btn btn-xs btn-default"
												name="remove_item_{$itemId}" value="{tr}Remove{/tr}"/>
										</td>
									{/if}
								</tr>
							{/foreach}
						</tbody>
					</table>
				{/form}
			{else}
				<p class="muted">{tr}No items yet.{/tr}</p>
			{/if}

			{if !$isComplete}
				{* ── Add single item ── *}
				<h4>{tr}Add Item{/tr}</h4>
				{form ipackage="stock" ifile="edit_movement.php"}
					<input type="hidden" name="content_id"  value="{$gContent->mContentId|escape}"/>
					<input type="hidden" name="movement_id" value="{$gContent->mMovementId|escape}"/>
					<div class="form-inline">
						<div class="form-group">
							<input type="text" class="form-control" name="component_title"
								placeholder="{tr}Component title{/tr}" size="35"/>
						</div>
						<div class="form-group">
							<input type="number" class="form-control" name="qty"
								placeholder="{tr}Qty{/tr}" min="0.001" step="any" size="8"/>
						</div>
						<div class="form-group">
							<select name="qty_src" class="form-control">
								{foreach from=$qtySrcOptions key=val item=label}
									<option value="{$val}">{tr}{$label}{/tr}</option>
								{/foreach}
							</select>
						</div>
						<input type="submit" class="btn btn-default" name="add_item" value="{tr}Add{/tr}"/>
					</div>
				{/form}

				{* ── Upload CSV ── *}
				<h4>{tr}Upload CSV{/tr}</h4>
				<p class="help-block">{tr}Three columns: component title, description (ignored), quantity.{/tr}</p>
				{form enctype="multipart/form-data" ipackage="stock" ifile="edit_movement.php"}
					<input type="hidden" name="content_id"  value="{$gContent->mContentId|escape}"/>
					<input type="hidden" name="movement_id" value="{$gContent->mMovementId|escape}"/>
					<div class="form-inline">
						<input type="file" name="csv_file" accept=".csv,text/csv"/>
						<input type="submit" class="btn btn-default" name="upload_csv" value="{tr}Upload{/tr}"/>
					</div>
				{/form}
			{/if}

		{/if}

	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
