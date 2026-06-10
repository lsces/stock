{assign var=xrefAllowEdit value=$allow_edit|default:false}
{assign var=isHistory value=($xrefGroup->mXGroup eq 'history')}
{strip}
<div class="row stock-group-gallery">
	{foreach $xrefGroup->mXrefs as $xrefInfo}
	<div class="col-md-4 col-sm-6 col-xs-12">
		<div class="panel panel-default">
			<div class="panel-body">
				<strong>{$xrefInfo.xref_title|escape}</strong>
				<small class="text-muted pull-right">{$xrefInfo.item|escape}</small>
			</div>
		</div>
	</div>
	{foreachelse}
	<div class="col-xs-12"><p class="norecords">{tr}No stock groups assigned.{/tr}</p></div>
	{/foreach}
</div>
{/strip}
