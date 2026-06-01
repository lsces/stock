{strip}
<div class="display stock">
	<div class="floaticon">
		{if $gBitUser->hasPermission('p_stock_create')}
			<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_movement.php?content_id={$gContent->mContentId}">{booticon iname="fa-pen-to-square" iexplain="Edit Movement"}</a>
		{/if}
	</div>

	<div class="header">
		<h1>{$gContent->getTitle()|escape}</h1>
	</div>

	<div class="body">

		<dl class="dl-horizontal">
			<dt>{tr}Type{/tr}</dt>
			<dd>{$gContent->getDirection()|escape}</dd>
			<dt>{tr}Received{/tr}</dt>
			<dd>{if $gContent->isReceived()}{$gContent->mInfo.event_time|bit_short_date}{else}{tr}Pending{/tr}{/if}</dd>
			<dt>{tr}Created{/tr}</dt>
			<dd>{$gContent->mInfo.created|bit_short_datetime} {tr}by{/tr} {$gContent->mInfo.creator|escape}</dd>
			{if $gContent->mInfo.last_modified neq $gContent->mInfo.created}
				<dt>{tr}Modified{/tr}</dt>
				<dd>{$gContent->mInfo.last_modified|bit_short_datetime} {tr}by{/tr} {$gContent->mInfo.editor|escape}</dd>
			{/if}
		</dl>

		{assign var=movXrefGroups value=$gContent->getXrefGroupList()}
		{if $movXrefGroups}
			{jstabs}
				{section name=xrefGroup loop=$movXrefGroups}
					{include file=$gContent->getXrefListTemplate($movXrefGroups[xrefGroup].template)
						source=$movXrefGroups[xrefGroup].source
						source_title=$movXrefGroups[xrefGroup].title
						group=$movXrefGroups[xrefGroup].sort_order
						allow_add=false
						allow_edit=false}
				{/section}
			{/jstabs}
		{/if}

	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
