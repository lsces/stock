{strip}
<div class="listing stock">
	<header>
		<div class="floaticon hidden-print">
			<button type="button" class="btn btn-link" onclick="window.print()">{biticon ipackage="icons" iname="document-print" iexplain="Print"}</button>
			{if $gBitUser->mUserId eq $gQueryUserId}
				{if $gBitUser->hasPermission('p_stock_create')}
					<a href="{$smarty.const.STOCK_PKG_URL}add_prebuild.php">{biticon ipackage="icons" iname="package-x-generic" iexplain="Add Prebuild"}</a>
				{/if}
				<a href="{$smarty.const.STOCK_PKG_URL}list_stock.php?user_id={$gQueryUserId}">{biticon ipackage="icons" iname="view-form-table" iexplain="Stock Levels"}</a>
			{/if}
			{minifind prompt="Assemblies"}
		</div>
		<h1>{tr}Assemblies{/tr} {tr}by{/tr} {displayname user_id=$gQueryUserId}</h1>
		<small><a href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php">{tr}Assemblies{/tr}</a></small>
	</header>

	<section class="body">
		<div class="row assemblies">
			{foreach from=$galleryList key=galleryId item=gal}
				<div class="col-xs-12 col-sm-6 col-md-4">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">
								<a href="{$gal.display_url|escape}">
									{$gal.title|truncate|escape}<br />
									<small class="text-muted">{tr}Kitlocker{/tr}: {$gal.klid|escape}</small>
								</a>
							</h3>
						</div>
						<div class="panel-body">
							{if $gal.parsed_data}
								<div class="content">{$gal.parsed_data}</div>
							{/if}
						</div>
						<div class="panel-footer">
							<dl class="dl-horizontal small" style="margin-bottom:0">
								<dt>{tr}Components{/tr}</dt>
								<dd>{$gal.component_count}</dd>
								{if $gal.prebuild_content_id}
									{assign var=pbldUrl value="`$smarty.const.STOCK_PKG_URL`view_movement.php?content_id=`$gal.prebuild_content_id`"}
									<dt><a href="{$pbldUrl}">{tr}Prebuilt{/tr}</a></dt>
									<dd><a href="{$pbldUrl}">{$gal.prebuild_count|string_format:"%.0f"}</a></dd>
								{else}
									<dt>{tr}Prebuilt{/tr}</dt>
									<dd>{$gal.prebuild_count|string_format:"%.0f"}</dd>
								{/if}
							</dl>
						</div>
					</div>
				</div>
			{foreachelse}
				<div class="col-xs-12 norecords">{tr}No assemblies found.{/tr}</div>
			{/foreach}
		</div>

		<nav>
			{pagination}
		</nav>

	</section>
</div>
{/strip}
