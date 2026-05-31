{strip}
<div class="edit stock">
	<div class="header">
		<h1>{tr}Parts List{/tr}: <a href="{$smarty.const.STOCK_PKG_URL}view.php?assembly_id={$gContent->mAssemblyId}">{$gContent->getTitle()|escape}</a></h1>
	</div>

	<div class="body">
		{formfeedback hash=$formfeedback}

		{form id="part_order"}
			<input type="hidden" name="assembly_id" value="{$gContent->mAssemblyId|escape}"/>

			<table class="table table-striped table-condensed">
				<thead>
					<tr>
						<th style="width:6em;">{tr}Order{/tr}</th>
						<th>{tr}Component{/tr}</th>
						<th>{tr}Description{/tr}</th>
						<th>{tr}Qty{/tr}</th>
						<th>{tr}Ref designators{/tr}</th>
					</tr>
				</thead>
				<tbody>
				{assign var=lastGroup value=-1}
				{if $gContent->mInfo.quantity}
					{foreach from=$gContent->mInfo.quantity item=row}
						{math equation="floor(x/1000)" x=$row.xorder|default:0 assign=thisGroup}
						{if $thisGroup != $lastGroup}
						<tr class="active">
							<th colspan="5">{tr}Group{/tr} {$thisGroup}</th>
						</tr>
						{assign var=lastGroup value=$thisGroup}
						{/if}
						<tr>
							<td>
								<input type="text" class="form-control input-sm" size="5"
									name="xrefOrder[{$row.xref_id|escape}]"
									value="{$row.xorder|escape}" />
							</td>
							<td>
								{if $row.xref > 0}
									<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$row.xref|escape}">{$row.xref_title|default:$row.xref|escape}</a>
								{else}
									&nbsp;
								{/if}
							</td>
							<td>{$row.xref_data|escape}</td>
							<td>
								{$row.xkey|escape}
								{if $row.item eq 'PCK' && $row.pack_size} of {$row.pack_size|escape}{if $row.pack_size_ext} {$row.pack_size_ext|escape}{/if}{/if}
							</td>
							<td>{$row.xkey_ext|escape}</td>
						</tr>
					{/foreach}
				{else}
					<tr class="norecords"><td colspan="5">{tr}No parts list found{/tr}</td></tr>
				{/if}
				</tbody>
			</table>

			<div class="form-group submit">
				<input type="submit" class="btn btn-default" name="cancel"     value="{tr}Back{/tr}" />
				<input type="submit" class="btn btn-primary" name="save_order" value="{tr}Save{/tr}" />
			</div>
		{/form}
	</div>
</div>
{/strip}
