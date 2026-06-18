{strip}
<div class="display stock">
	<header>
		{include file="bitpackage:stock/assembly_icons_inc.tpl"}
		<h1>{$gContent->getTitle()|escape}</h1>
		{include file="bitpackage:stock/assembly_nav.tpl"}
	</header>

	<section class="body">
		{formfeedback success=$stockSuccess error=$stockErrors warning=$stockWarnings}

		{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$gContent->mInfo}
		{if $gContent->mInfo.data}
			<div class="content">{$gContent->getParsedData()}</div>
		{/if}
	</section>

	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='view' serviceHash=$gContent->mInfo}

	{if $gContent->getPreference('allow_comments') eq 'y'}
		{include file="bitpackage:liberty/comments.tpl"}
	{/if}
</div>
{/strip}
