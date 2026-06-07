{strip}
<div class="display stock">
	<header>
		<div class="floaticon hidden-print">
			<button type="button" class="btn btn-link" onclick="window.print()">{biticon ipackage="icons" iname="document-print" iexplain="Print"}</button>
		</div>
		<h1>{$gContent->getTitle()|escape} &mdash; {tr}Parts List{/tr}</h1>
	</header>

	<section class="body">
		<table class="table table-striped table-condensed">
			<thead>
				<tr>
					<th style="width:3em">{tr}#{/tr}</th>
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
					{math equation="x % 1000"       x=$row.xorder|default:0 assign=posInGroup}
					{if $thisGroup != $lastGroup}
					<tr class="active">
						<th colspan="5">{tr}Group{/tr} {$thisGroup}</th>
					</tr>
					{assign var=lastGroup value=$thisGroup}
					{/if}
					<tr>
						<td>{$posInGroup}</td>
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
				<tr class="norecords"><td colspan="5">{tr}No parts list found.{/tr}</td></tr>
			{/if}
			</tbody>
		</table>
	</section>
</div>
{/strip}
<script>window.addEventListener('load', function(){ window.print(); });</script>
