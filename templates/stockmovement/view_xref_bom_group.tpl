{assign var=xrefAllowEdit value=$allow_edit|default:false}
{assign var=isHistory value=($xrefGroup->mXGroup eq 'history')}
{jstab title="`$xrefGroup->mTitle` ({$xrefGroup->mXrefs|@count})"}
{legend legend=$xrefGroup->mTitle}
<div class="form-group table-responsive">
	<table class="table">
		<thead>
			<tr>
				<th style="width:20%">{tr}Component{/tr}</th>
				<th style="width:40%">{tr}Description{/tr}</th>
				<th style="width:15%">{tr}Qty{/tr}</th>
				<th style="width:25%">{tr}Ref{/tr}</th>
				{if $xrefAllowEdit}<th>{tr}Added{/tr}</th><th>{tr}Updated{/tr}</th><th>{tr}Edit{/tr}</th>{/if}
			</tr>
		</thead>
		<tbody>
			{if $xrefGroup->mXrefs}
				{foreach $xrefGroup->mXrefs as $xrefInfo}
					{include file=$gContent->getXrefRecordTemplate($xrefInfo.template)}
				{/foreach}
			{else}
				<tr class="norecords">
					<td colspan="{if $xrefAllowEdit}7{else}4{/if}">{tr}No parts list found{/tr}</td>
				</tr>
			{/if}
		</tbody>
	</table>
</div>
{if $allow_add && $gContent->isValid() && $gContent->hasUpdatePermission() && !$isHistory}
	<div>
		{smartlink ititle="Add record" ipackage="liberty" ifile="add_xref.php" biticon="list-add" content_id=$gContent->mInfo.content_id group=$xrefGroup->mSortOrder}
	</div>
{/if}
{/legend}
{/jstab}
