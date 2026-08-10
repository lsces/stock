{strip}
{jstab title="{$asmTab.title|escape} (&times;{$asmTab.kit_count|escape})"}
<div class="table-responsive">
	<table class="table table-condensed">
		<thead>
			<tr>
				<th>{tr}Item{/tr}</th>
				<th>{tr}Qty{/tr}</th>
				{if $allow_edit|default:false && $gBitUser->hasPermission('p_stock_admin')}<th></th>{/if}
			</tr>
		</thead>
		<tbody>
			{if $asmTab.lines}
				{foreach $asmTab.lines as $line}
				<tr class="{cycle values='even,odd'}">
					<td>
						{if $line.xref}
							<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$line.xref|escape}">{$line.component_title|default:$line.xref|escape}</a>
						{else}
							&nbsp;
						{/if}
					</td>
					<td>{$line.xkey|escape}</td>
					{if $allow_edit|default:false && $gBitUser->hasPermission('p_stock_admin')}
					<td>
						<span class="actionicon">
							{smartlink ititle="Edit" ipackage="liberty" ifile="edit_xref.php" biticon="edit" content_id=$gContent->mInfo.content_id xref_id=$line.xref_id}
							{smartlink ititle="Remove" ipackage="liberty" ifile="edit_xref.php" biticon="user-trash" content_id=$gContent->mInfo.content_id xref_id=$line.xref_id expunge=1}
						</span>
					</td>
					{/if}
				</tr>
				{/foreach}
			{else}
				<tr class="norecords">
					<td colspan="{if $allow_edit|default:false && $gBitUser->hasPermission('p_stock_admin')}3{else}2{/if}">{tr}No items{/tr}</td>
				</tr>
			{/if}
		</tbody>
	</table>
</div>
{/jstab}
{/strip}
