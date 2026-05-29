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
				<tr class="{cycle values="even,odd"}">
					<td>
						{if $gContent->mInfo.$source[xref].xref > 0}
							<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?component_id={$gContent->mInfo.$source[xref].xref|escape}">{$gContent->mInfo.$source[xref].xref_title|default:$gContent->mInfo.$source[xref].xref|escape}</a>
						{else}
							&nbsp;
						{/if}
					</td>
					<td>{$gContent->mInfo.$source[xref].xref_data|escape}</td>
					<td>{$gContent->mInfo.$source[xref].xkey|escape}</td>
					<td>{$gContent->mInfo.$source[xref].xkey_ext|escape}</td>
					{if $xrefAllowEdit}
						<td>{$gContent->mInfo.$source[xref].start_date|bit_short_date}</td>
						<td>{$gContent->mInfo.$source[xref].last_update_date|bit_short_date}</td>
						<td>
							<span class="actionicon">
								{if $gContent->hasUpdatePermission() && $source ne 'history'}
									{smartlink ititle="Edit" ipackage="liberty" ifile="edit_xref.php" booticon="icon-note-edit" content_id=$gContent->mInfo.content_id xref_id=$gContent->mInfo.$source[xref].xref_id}
								{/if}
								{if $gContent->hasExpungePermission()}
									{smartlink ititle="Delete" ipackage="liberty" ifile="edit_xref.php" booticon="icon-note-delete" content_id=$gContent->mInfo.content_id xref_id=$gContent->mInfo.$source[xref].xref_id expunge=1}
								{/if}
							</span>
						</td>
					{/if}
				</tr>
				{if $gContent->mInfo.$source[xref].data}
				<tr>
					<td colspan="7" class="xref-note">{$gContent->mInfo.$source[xref].data|escape}</td>
				</tr>
				{/if}
			{sectionelse}
				<tr class="norecords">
					<td colspan="7">{tr}No parts list found{/tr}</td>
				</tr>
			{/section}
		</tbody>
	</table>
</div>
{if $allow_add && $gContent->isValid() && $gContent->hasUpdatePermission() && $source ne 'history'}
	<div>
		{smartlink ititle="Add record" ipackage="liberty" ifile="add_xref.php" booticon="icon-note-add" content_id=$gContent->mInfo.content_id group=$group}
	</div>
{/if}
{/legend}
{/jstab}
