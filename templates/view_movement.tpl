{strip}
<div class="display stock">
	<header>
		<div class="floaticon">
			{if $gContent->hasUpdatePermission()}
				<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_movement.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="edit" iexplain="Edit Movement"}</a>
			{/if}
			{if $gContent->hasAdminPermission()}
				<a title="{tr}Delete Movement{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_movement.php?content_id={$gContent->mContentId}&amp;delete=1" onclick="return confirm('{tr}Are you sure you want to delete this movement?{/tr}')">{biticon ipackage="icons" iname="user-trash" iexplain="Delete Movement"}</a>
			{/if}
		</div>
		<h1>{$gContent->getTitle()|escape}</h1>
		<small>
			<a href="{$smarty.const.STOCK_PKG_URL}list_movements.php">{tr}Movements{/tr}</a>
			&rsaquo; <a href="{$smarty.const.STOCK_PKG_URL}list_movements.php?user_id={$gContent->mInfo.user_id}">{$gContent->mInfo.creator|escape}</a>
		</small>
	</header>

	<section class="body">

		<dl class="dl-horizontal">
			<dt>{tr}Type{/tr}</dt>
			<dd>{$gContent->mInfo.ref_type_title|default:$gContent->getDirection()|escape}</dd>
			{if $gContent->mInfo.ref_contact_name}
			<dt>{tr}Supplier{/tr}</dt>
			<dd>
				<a href="{$smarty.const.CONTACT_PKG_URL}view.php?content_id={$gContent->mInfo.ref_contact_id}">{$gContent->mInfo.ref_contact_name|escape}</a>
			</dd>
			{elseif $gContent->mInfo.ref_from_data}
			<dt>{tr}From{/tr}</dt>
			<dd>{$gContent->mInfo.ref_from_data|escape}</dd>
			{/if}
			<dt>{tr}Created{/tr}</dt>
			<dd>{$gContent->mInfo.created|bit_short_datetime} {tr}by{/tr} {$gContent->mInfo.creator|escape}</dd>
			{if $gContent->mInfo.ref_start_date}
			<dt>{if $isPbld}{tr}Build Date{/tr}{else}{tr}Ordered{/tr}{/if}</dt>
			<dd>{$gContent->mInfo.ref_start_date|bit_short_date}</dd>
			{/if}
			<dt>{if $isPbld}{tr}Completed{/tr}{else}{tr}Received{/tr}{/if}</dt>
			<dd>{if $gContent->isReceived()}{$gContent->mInfo.event_time|bit_short_date}{else}{if $isPbld}{tr}In progress{/tr}{else}{tr}Pending{/tr}{/if}{/if}</dd>
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
					{* See edit_movement.tpl for the reasoning behind each of these — kept
					   identical here so view/edit show the same set of tabs. *}
					{if $xrefGroup->mXGroup eq 'reference'}
					{elseif $xrefGroup->mXGroup eq 'assembly'}
						{if $isBuild}
							{include file=$gContent->getXrefListTemplate($xrefGroup->mTemplate)
								xrefGroup=$xrefGroup allow_add=false allow_edit=false}
						{/if}
					{elseif $xrefGroup->mXGroup eq 'quantity'}
						{if !$isBuild}
							{include file=$gContent->getXrefListTemplate($xrefGroup->mTemplate)
								xrefGroup=$xrefGroup allow_add=false allow_edit=false}
						{/if}
					{elseif $xrefGroup->mXGroup eq 'supplier' || $xrefGroup->mXGroup eq 'stgrp' || $xrefGroup->mXGroup eq 'kitlocker'}
					{else}
						{include file=$gContent->getXrefListTemplate($xrefGroup->mTemplate)
							xrefGroup=$xrefGroup allow_add=false allow_edit=false}
					{/if}
				{/foreach}
				{foreach $assemblyTabs as $asmTab}
					{include file="bitpackage:stock/view_assembly_bom_tab.tpl" asmTab=$asmTab allow_edit=false}
				{/foreach}
			{/jstabs}
		{/if}

	</section><!-- end .body -->
</div><!-- end .stock -->
{/strip}
