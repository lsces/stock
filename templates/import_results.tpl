{strip}
<div class="display stock">
	<div class="header">
		<h1>{$page_title|default:"Import Results"|escape}</h1>
	</div>
	<div class="body">
		<p>{tr}File{/tr}: <code>{$csvFile|escape}</code></p>

		<p>
			<strong>{$loaded}</strong> {tr}records imported{/tr}.
			{if $updated}
				<strong>{$updated}</strong> {tr}records updated{/tr}.
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
	</div>
</div>
{/strip}
