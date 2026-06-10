{strip}
<div class="listing stock">
	<header>
		<div class="floaticon">
		</div>
		<h1>{tr}Kitlocker{/tr}</h1>
	</header>

	<section class="body">
		<div class="row">
			{foreach $stgrpItems as $item}
			<div class="col-md-4 col-sm-6 col-xs-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<a href="{$smarty.const.STOCK_PKG_URL}list_kitlocker.php?stgrp={$item.item|escape:'url'}">{$item.cross_ref_title|escape}</a>
					</div>
					{if $item.data}
					<div class="panel-body">
						{$item.data|escape}
					</div>
					{/if}
					<div class="panel-footer">
						{tr}Count{/tr}: {$item.item_count}
					</div>
				</div>
			</div>
			{foreachelse}
			<div class="col-xs-12"><p class="norecords">{tr}No kitlocker groups defined.{/tr}</p></div>
			{/foreach}
		</div>
	</section>
</div>
{/strip}
