{strip}
<div class="listing stock">
	<header>
		<div class="floaticon">
			{minifind prompt="Assemblies"}
		</div>
		<h1>{tr}Assemblies{/tr}{if $gQueryUserId} {tr}by{/tr} {displayname user_id=$gQueryUserId}{/if}</h1>
	</header>

	<section class="body">
		<ul class="list-inline sortby">
			<li>{biticon ipackage="icons" iname="go-next" ipackage="icons" iexplain="sort by" iforce="icon"}</li>
			{if $gBitSystem->isFeatureActive('stock_list_title')}
				<li>{smartlink ititle="Name" isort="title"}</li>
			{/if}
			{if $gBitSystem->isFeatureActive('stock_list_user')}
				<li>{smartlink ititle="Owner" isort=$gBitSystem->getConfig('users_display_name')}</li>
			{/if}
			{if $gBitSystem->isFeatureActive('stock_list_created')}
				<li>{smartlink ititle="Created" isort="created"}</li>
			{/if}
			{if $gBitSystem->isFeatureActive('stock_list_lastmodif')}
				<li>{smartlink ititle="Last Modified" isort="last_modified"}</li>
			{/if}
			{if $gBitSystem->isFeatureActive('stock_list_hits')}
				<li>{smartlink ititle="Hits" isort="hits"}</li>
			{/if}
		</ul>

		<div class="row assemblies">
			{foreach from=$galleryList key=galleryId item=gal}
				<div class="col-xs-6 col-sm-4 col-md-3">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">
								<a href="{$gal.display_url|escape}">
									{if $gBitSystem->isFeatureActive('stock_list_title')}
										{$gal.title|truncate|escape}
									{else}
										{tr}Assembly{/tr} {$gal.content_id}
									{/if}
								</a>
								{if $gal.is_hidden|default:'n' == 'y' || $gal.is_private|default:'n' == 'y' || $gal.access_answer|default:false}
									{biticon ipackage="icons" iname="lock" ipackage="icons" iexplain="Restricted"}
								{/if}
							</h3>
						</div>
						<div class="panel-body">
							{if $gBitSystem->isFeatureActive('stock_list_user')}
								<small>{displayname hash=$gal nolink=true}</small>
							{/if}
							{if $gBitSystem->isFeatureActive('stock_list_created')}
								<div class="text-muted small">{$gal.created|bit_short_date}</div>
							{/if}
							{if $gBitSystem->isFeatureActive('stock_list_lastmodif')}
								<div class="text-muted small">{tr}Modified:{/tr} {$gal.last_modified|bit_short_date}</div>
							{/if}
							{if $gBitSystem->isFeatureActive('stock_list_hits')}
								<div class="text-muted small">{tr}Hits:{/tr} {$gal.hits}</div>
							{/if}
							{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='body' serviceHash=$gal}
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
