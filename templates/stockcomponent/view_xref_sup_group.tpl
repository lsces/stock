{assign var=xrefcnt value=$gContent->mInfo.$source|default:[]|@count}
{assign var=xrefAllowEdit value=$allow_edit|default:true}
{jstab title="$source_title ($xrefcnt)"}
{legend legend=$source_title}
<div class="form-group table-responsive">
	<table>
		<thead>
			<tr>
				<th>{tr}Supplier{/tr}</th>
				<th>{tr}Part No.{/tr}</th>
				<th>{tr}Price{/tr}</th>
				<th>{tr}Note{/tr}</th>
				{if $xrefAllowEdit}<th>{tr}Added{/tr}</th><th>{tr}Updated{/tr}</th><th>{tr}Edit{/tr}</th>{/if}
			</tr>
		</thead>
		<tbody>
			{section name=xref loop=$gContent->mInfo.$source}
				{assign var=_rowTpl value=$gContent->mInfo.$source[xref].template}
				{include file=$gContent->getXrefRecordTemplate($_rowTpl)}
			{sectionelse}
				<tr class="norecords">
					<td colspan="7">{tr}No supplier records found{/tr}</td>
				</tr>
			{/section}
		</tbody>
	</table>
</div>
{if $allow_add && $gContent->isValid() && $gContent->hasUpdatePermission() && $source ne 'history'}
	<div>
		{smartlink ititle="Add supplier" ipackage="stock" ifile="add_supplier.php" biticon="list-add" content_id=$gContent->mInfo.content_id}
	</div>
{/if}
{/legend}
{/jstab}
