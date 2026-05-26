{strip}
<div class="display stock">
	<div class="header">
		<h1>{$page_title|default:"Import Results"|escape}</h1>
	</div>
	<div class="body">
		<p>{tr}File{/tr}: <code>{$csvFile|escape}</code></p>

		<p>
			<strong>{$loaded}</strong> {tr}records imported{/tr}.
			{if $skipped}
				<strong>{$skipped}</strong> {tr}rows skipped{/tr}.
			{/if}
		</p>

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
