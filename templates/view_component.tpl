{strip}
<div class="display stock">
	{formfeedback hash=$feedback}
	<header>
		{if empty($liberty_preview)}
		<div class="floaticon">
			{if $gContent->hasUpdatePermission()}
				<a title="{tr}Edit{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$gContent->mContentId}">{biticon ipackage="icons" iname="edit" iexplain="Edit Component"}</a>
				<a title="{tr}Delete{/tr}" href="{$smarty.const.STOCK_PKG_URL}edit_component.php?content_id={$gContent->mContentId}&amp;delete=1">{biticon ipackage="icons" iname="user-trash" iexplain="Delete Component"}</a>
			{/if}
		</div>
		{/if}
		<h1>{$gContent->getTitle()|escape}</h1>
		{if empty($liberty_preview)}
		<small><a href="{$smarty.const.STOCK_PKG_URL}list_components.php">{tr}Components{/tr}</a></small>
		{/if}
	</header>

	<section class="body">
		{if $gContent->mInfo.data ne ''}
			<p class="description">{$gContent->mInfo.parsed_data}</p>
		{/if}

		{jstabs}
			{if $gXrefInfo->mGroups}
				{assign var=klGroup value=null}
				{foreach $gXrefInfo->mGroups as $xrefGroup}
					{if $xrefGroup->mXGroup eq 'kitlocker'}
						{assign var=klGroup value=$xrefGroup}
					{else}
						{include file=$gContent->getXrefListTemplate($xrefGroup->mTemplate)
							xrefGroup=$xrefGroup allow_edit=false}
					{/if}
				{/foreach}
				{if $isKitlocker && $klGroup}
					{include file=$gContent->getXrefListTemplate($klGroup->mTemplate)
						xrefGroup=$klGroup allow_edit=false}
				{/if}
			{/if}

			{jstab title="{tr}Stock{/tr}"}
			<table class="table table-condensed">
				<thead>
					<tr>
						<th>{tr}Type{/tr}</th>
						<th class="text-right">{tr}Level{/tr}</th>
					</tr>
				</thead>
				<tbody>
					{if $componentStockLevels}
						{foreach from=$componentStockLevels key=qtype item=level}
						<tr{if $level < 0} class="danger"{elseif $level == 0} class="warning"{/if}>
							<td>{$qtype|escape}</td>
							<td class="text-right">{if $qtype eq 'PRT' && $partSize > 0}{math equation="l/p" l=$level p=$partSize format="%.2f"}{elseif $qtype eq 'SHT'}{$level|string_format:"%.2f"}{else}{$level|string_format:"%.0f"}{/if}</td>
						</tr>
						{/foreach}
					{else}
						<tr class="norecords"><td colspan="2">{tr}No stock movements recorded{/tr}</td></tr>
					{/if}
				</tbody>
			</table>
			<a class="btn btn-default btn-xs" href="{$smarty.const.STOCK_PKG_URL}list_movements.php?part_content_id={$gContent->mContentId}">{tr}Stock history{/tr}</a>
			{/jstab}
		{/jstabs}
	</section>

	{if $gGallery && $gGallery->isCommentable()}
		{include file="bitpackage:liberty/comments.tpl"}
	{/if}

</div>
{/strip}
