{assign var=xrefcnt value=$gContent->mInfo.$source|default:[]|@count}
{assign var=xrefAllowEdit value=$allow_edit|default:true}
{jstab title="$source_title ($xrefcnt)"}
{legend legend=$source_title}
<div class="form-group table-responsive">
	<table>
		<thead>
			<tr>
				<th>{tr}Component{/tr}</th>
				<th>{tr}Description{/tr}</th>
				<th>{tr}Qty{/tr}</th>
				<th>{tr}Ref{/tr}</th>
				{if $xrefAllowEdit}<th>{tr}Added{/tr}</th><th>{tr}Updated{/tr}</th><th>{tr}Edit{/tr}</th>{/if}
			</tr>
		</thead>
		<tbody>
			{section name=xref loop=$gContent->mInfo.$source}
				{include file=$gContent->getXrefRecordTemplate($gContent->mInfo.$source[xref].template)}
			{sectionelse}
				<tr class="norecords">
					<td colspan="8">{tr}No parts list found{/tr}</td>
				</tr>
			{/section}
		</tbody>
	</table>
</div>
{if $allow_add && $gContent->isValid() && $gContent->hasUpdatePermission() && $source ne 'history'}
	<div>
		{smartlink ititle="Add component" ipackage="stock" ifile="add_component.php" booticon="icon-note-add" content_id=$gContent->mInfo.content_id}
	</div>
{/if}
{/legend}
{/jstab}
