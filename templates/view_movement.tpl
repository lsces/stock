{strip}
<div class="display stock">
	<div class="floaticon">
		{if $gBitUser->hasPermission('p_stock_create')}
			<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_movement.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="edit" iexplain="Edit Movement"}</a>
		{/if}
	</div>

	<div class="header">
		<h1>{$gContent->getTitle()|escape}</h1>
	</div>

	<div class="body">

		<dl class="dl-horizontal">
			<dt>{tr}Type{/tr}</dt>
			<dd>{$gContent->mInfo.ref_type_title|default:$gContent->getDirection()|escape}</dd>
			{if $gContent->mInfo.ref_contact_name}
			<dt>{tr}Supplier{/tr}</dt>
			<dd>
				<a href="{$smarty.const.CONTACT_PKG_URL}display.php?content_id={$gContent->mInfo.ref_contact_id}">{$gContent->mInfo.ref_contact_name|escape}</a>
			</dd>
			{elseif $gContent->mInfo.reference.0.data}
			<dt>{tr}From{/tr}</dt>
			<dd>{$gContent->mInfo.reference.0.data|escape}</dd>
			{/if}
			<dt>{tr}Created{/tr}</dt>
			<dd>{$gContent->mInfo.created|bit_short_datetime} {tr}by{/tr} {$gContent->mInfo.creator|escape}</dd>
			{if $gContent->mInfo.ref_start_date}
			<dt>{tr}Ordered{/tr}</dt>
			<dd>{$gContent->mInfo.ref_start_date|bit_short_date}</dd>
			{/if}
			<dt>{tr}Received{/tr}</dt>
			<dd>{if $gContent->isReceived()}{$gContent->mInfo.event_time|bit_short_date}{else}{tr}Pending{/tr}{/if}</dd>
			{if $gContent->mInfo.last_modified neq $gContent->mInfo.created}
				<dt>{tr}Modified{/tr}</dt>
				<dd>{$gContent->mInfo.last_modified|bit_short_datetime} {tr}by{/tr} {$gContent->mInfo.editor|escape}</dd>
			{/if}
			{if $gContent->mInfo.data}
			<dt>{tr}Note{/tr}</dt>
			<dd>{$gContent->mInfo.data|escape}</dd>
			{/if}
		</dl>

		{if $gXrefInfo->mGroups}
			{jstabs}
				{foreach $gXrefInfo->mGroups as $xrefGroup}
					{if $xrefGroup->mXGroup neq 'reference'}
						{include file=$gContent->getXrefListTemplate($xrefGroup->mTemplate)
							xrefGroup=$xrefGroup
							allow_add=false
							allow_edit=false}
					{/if}
				{/foreach}
			{/jstabs}
		{/if}

	</div><!-- end .body -->
</div><!-- end .stock -->
{/strip}
