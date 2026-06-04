	{assign var=xrefcnt value=$gContent->mInfo.$source|default:[]|@count}
	{jstab title="$source_title ($xrefcnt)"}
	{legend legend=$source_title}
	<div class="form-group table-responsive">
		<table>
			<thead>
				<tr>
					<th>{tr}Source{/tr}</th>
					<th>{tr}Data{/tr}</th>
					{if $source ne 'history'}
						<th>{tr}Started{/tr}</th>
					{else}
						<th>{tr}Ended{/tr}</th>
					{/if}
					<th>{tr}Updated{/tr}</th>
					<th>{tr}Edit{/tr}</th>
				</tr>
			</thead>
			<tbody>
				{section name=xref loop=$gContent->mInfo.$source}
					<tr class="{cycle values="even,odd"}">
						<td>{$gContent->mInfo.$source[xref].source_title|escape}</td>
						<td>
							{if $gContent->mInfo.$source[xref].xref}
								<a href="{$smarty.const.CONTACT_PKG_URL}?content_id={$gContent->mInfo.$source[xref].xref}">{$gContent->mInfo.$source[xref].data|escape}</a>
							{else}
								{$gContent->mInfo.$source[xref].data|escape}
							{/if}
						</td>
						{if $source ne 'history'}
							<td>{$gContent->mInfo.$source[xref].start_date|bit_short_date}</td>
						{else}
							<td>{$gContent->mInfo.$source[xref].end_date|bit_short_date}</td>
						{/if}
						<td>{$gContent->mInfo.$source[xref].last_update_date|bit_short_date}</td>
						<td>
							{if $gBitUser->hasPermission('p_stock_update')}
								{smartlink ititle="Edit" ifile="edit_xref.php" biticon="document-properties" content_id=$gContent->mInfo.content_id xref_id=$gContent->mInfo.$source[xref].xref_id}
							{/if}
						</td>
					</tr>
				{sectionelse}
					<tr class="norecords">
						<td colspan="5">{tr}No {$source_title} records found{/tr}</td>
					</tr>
				{/section}
			</tbody>
		</table>
	</div>
	{if $gBitUser->hasPermission('p_stock_update') && $source ne 'history'}
		<div>
			{smartlink ititle="Add record" ifile="add_xref.php" biticon="list-add" content_id=$gContent->mInfo.content_id group=$group}
		</div>
	{/if}
	{/legend}
	{/jstab}
