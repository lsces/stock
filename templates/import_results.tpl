{strip}
<div class="display stock">
	<div class="header">
		<h1>{$page_title|default:"Import Results"|escape}</h1>
	</div>
	<div class="body">
		{if $uploadForm}
			<div class="bitnav">
				<ul class="pagination">
					<li class="bitnav-picker">
						<form method="post" enctype="multipart/form-data" id="kitlockerUploadForm">
							<input type="file" name="html_file" accept=".html,.htm,text/html" />
						</form>
					</li>
					<li class="bitnav-gap"><button type="submit" form="kitlockerUploadForm">{tr}Upload &amp; Run{/tr}</button></li>
				</ul>
			</div>
			<hr />
		{else}
			<p>{tr}File{/tr}: <code>{$csvFile|escape}</code></p>
		{/if}

		<p>
			<strong>{$loaded}</strong> {tr}records imported{/tr}.
			{if $updated}
				<strong>{$updated}</strong> {tr}records updated{/tr}.
			{/if}
			{if $created}
				<strong>{$created}</strong> {tr}new records created{/tr}.
			{/if}
			{if $skipped}
				<strong>{$skipped}</strong> {tr}rows skipped{/tr}.
			{/if}
			{if $deleted}
				<strong>{$deleted}</strong> {tr}records cleared first{/tr}.
			{/if}
		</p>

		{if $movement}
			{if $doProcess}
				<p class="success">{tr}Movement processed — stock levels updated.{/tr}</p>
			{elseif $loaded gt 0}
				<p>{tr}Movement created in pending status.{/tr}
				{tr}Append{/tr} <code>?process=y</code> {tr}to commit stock levels.{/tr}</p>
			{/if}
		{/if}

		{if $errors}
			<h3>{tr}Errors / warnings{/tr}</h3>
			<ul>
				{foreach $errors as $msg}
					<li>{$msg|escape}</li>
				{/foreach}
			</ul>
		{/if}

		{if $skippedRows}
			<h3>{tr}Unmatched codes{/tr}</h3>
			<table class="table table-condensed">
				<thead><tr><th>{tr}Code{/tr}</th><th>{tr}Name{/tr}</th><th></th></tr></thead>
				<tbody>
					{foreach $skippedRows as $sr}
					<tr>
						<td>{$sr.code|escape}</td>
						<td>{$sr.name|escape}</td>
						<td>
							<a class="btn btn-default btn-xs" href="?create={$sr.code|escape:"url"}:A&amp;name={$sr.name|escape:"url"}&amp;klsgl={$sr.klsgl|escape:"url"}&amp;kl3m={$sr.kl3m|escape:"url"}">{tr}Add as Assembly{/tr}</a>
							<a class="btn btn-default btn-xs" href="?create={$sr.code|escape:"url"}:C&amp;name={$sr.name|escape:"url"}&amp;klsgl={$sr.klsgl|escape:"url"}&amp;kl3m={$sr.kl3m|escape:"url"}">{tr}Add as Component{/tr}</a>
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		{/if}
	</div>
</div>
{/strip}
