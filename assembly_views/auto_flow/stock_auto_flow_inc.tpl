{strip}
{include file="bitpackage:stock/assembly_nav.tpl"}
<div class="display stock container">
	<div class="header col-xs-12">
		{include file="bitpackage:stock/assembly_icons_inc.tpl"}
		<h1>{$gContent->getTitle()|escape}</h1>
	</div>

	<div class="body col-xs-12">
		{formfeedback success=$stockSuccess error=$stockErrors warning=$stockWarnings}

		{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$gContent->mInfo}
		{if $gContent->mInfo.data}
			<p>{$gContent->mInfo.data|escape}</p>
		{/if}

		<div class="col-xs-12">
		{foreach from=$gContent->mItems item=galItem key=itemContentId}
			<div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
				<div class="gallery-box">
					<a href="{$galItem->getDisplayUrl()|escape}">
						<h3>{$galItem->mInfo.title|escape}</h3>
					</a>
					{if $galItem->mInfo.data}
						<p class="text-muted">{$galItem->mInfo.data|truncate:120|escape}</p>
					{/if}
					{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$galItem->mInfo type=mini}
				</div>
			</div>
		{foreachelse}
			<p class="norecords">{tr}This assembly has no components.{/tr}</p>
		{/foreach}
		</div>
		<div class="clear"></div>

	</div><!-- end .body -->

	{pagination content_id=$gContent->mContentId}

	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='view' serviceHash=$gContent->mInfo}

	{if $gContent->getPreference('allow_comments') eq 'y'}
		{include file="bitpackage:liberty/comments.tpl"}
	{/if}
</div><!-- end .stock -->
{/strip}
