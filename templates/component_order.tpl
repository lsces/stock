{strip}
<div class="edit stock">
	<div class="header">
		<div class="floaticon hidden-print">
			<button type="button" class="btn btn-link" onclick="window.print()">{biticon ipackage="icons" iname="document-print" iexplain="Print"}</button>
		</div>
		<h1>{tr}Parts List{/tr}: <a href="{$smarty.const.STOCK_PKG_URL}view_assembly.php?content_id={$gContent->mContentId}">{$gContent->getTitle()|escape}</a></h1>
	</div>

	<div class="body">
		{formfeedback hash=$formfeedback}

		{form id="part_order"}
			<input type="hidden" name="content_id" value="{$gContent->mContentId|escape}"/>

			<table class="table table-striped table-condensed">
				<thead>
					<tr>
						<th style="width:7em;">{tr}Order{/tr}</th>
						<th>{tr}Component{/tr}</th>
						<th>{tr}Description{/tr}</th>
						<th>{tr}Qty{/tr}</th>
						<th>{tr}Ref designators{/tr}</th>
					</tr>
				</thead>
				<tbody>
				{assign var=lastGroup value=-1}
				{if $gXrefInfo->mGroups.quantity && $gXrefInfo->mGroups.quantity->mXrefs}
					{foreach from=$gXrefInfo->mGroups.quantity->mXrefs item=row}
						{math equation="floor(x/1000)" x=$row.xorder|default:0 assign=thisGroup}
						{math equation="x % 1000"     x=$row.xorder|default:0 assign=posInGroup}
						{if $thisGroup != $lastGroup}
						<tr class="active">
							<th colspan="5">{tr}Group{/tr} {$thisGroup}</th>
						</tr>
						{assign var=lastGroup value=$thisGroup}
						{/if}
						<tr>
							<td>
								<input type="text" class="form-control input-sm hidden-print" size="7"
									name="xrefOrder[{$row.xref_id|escape}]"
									value="{$row.xorder|escape}" />
								<span class="visible-print-inline">{$posInGroup}</span>
							</td>
							<td>
								{if $row.xref > 0}
									<a href="{$smarty.const.STOCK_PKG_URL}view_component.php?content_id={$row.xref|escape}">{$row.linked_title|default:$row.xref|escape}</a>
								{else}
									&nbsp;
								{/if}
							</td>
							<td>{$row.linked_data|escape}</td>
							<td>
								{$row.xkey|escape}
								{if $row.item eq 'PRT' && $row.part_size} of {$row.part_size|escape}{if $row.part_size_ext} {$row.part_size_ext|escape}{/if}{/if}
							</td>
							<td>{$row.xkey_ext|escape}</td>
						</tr>
					{/foreach}
				{else}
					<tr class="norecords"><td colspan="5">{tr}No parts list found{/tr}</td></tr>
				{/if}
				</tbody>
			</table>

			<div class="form-group submit hidden-print">
				<input type="submit" class="btn btn-default" name="cancel"     value="{tr}Back{/tr}" />
				<input type="submit" class="btn btn-primary" name="save_order" value="{tr}Save{/tr}" />
			</div>
		{/form}
	</div>
</div>
{/strip}
