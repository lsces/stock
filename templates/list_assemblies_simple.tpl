{strip}
<div class="listing stock">
	<header>
		<div class="floaticon">
			{minifind prompt="Assemblies"}
		</div>
		<h1>{tr}Assemblies{/tr}{if $gQueryUserId} {tr}by{/tr} {displayname user_id=$gQueryUserId}{/if}</h1>
	</header>

	<section class="body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th style="width:60px"></th>
					<th>{smartlink ititle="Name" isort="title" idefault=1 icontrol=$listInfo}</th>
					{if $gBitSystem->isFeatureActive('stock_list_user')}<th>{tr}Owner{/tr}</th>{/if}
					{if $gBitSystem->isFeatureActive('stock_list_created')}<th>{smartlink ititle="Created" isort="created" icontrol=$listInfo}</th>{/if}
					{if $gBitSystem->isFeatureActive('stock_list_lastmodif')}<th>{smartlink ititle="Modified" isort="last_modified" icontrol=$listInfo}</th>{/if}
					{if $gBitSystem->isFeatureActive('stock_list_hits')}<th>{smartlink ititle="Hits" isort="hits" icontrol=$listInfo}</th>{/if}
				</tr>
			</thead>
			<tbody>
				{foreach from=$galleryList key=galleryId item=gal}
					<tr>
						<td>
							{if $gal.thumbnail_url}
								<a href="{$gal.display_url|escape}">
									<img src="{$gal.thumbnail_url|escape}" alt="{$gal.title|escape}" style="max-height:48px;max-width:56px"/>
								</a>
							{/if}
						</td>
						<td>
							<a href="{$gal.display_url|escape}">{$gal.title|escape}</a>
							{if $gal.is_hidden|default:'n' == 'y' || $gal.is_private|default:'n' == 'y' || $gal.access_answer|default:false}
								{booticon iname="icon-lock" ipackage="icons" iexplain="Restricted"}
							{/if}
							{if $gal.data}
								<br/><small class="text-muted">{$gal.data|truncate:250|escape}</small>
							{/if}
						</td>
						{if $gBitSystem->isFeatureActive('stock_list_user')}<td>{displayname hash=$gal nolink=true}</td>{/if}
						{if $gBitSystem->isFeatureActive('stock_list_created')}<td>{$gal.created|bit_short_date}</td>{/if}
						{if $gBitSystem->isFeatureActive('stock_list_lastmodif')}<td>{$gal.last_modified|bit_short_date}</td>{/if}
						{if $gBitSystem->isFeatureActive('stock_list_hits')}<td>{$gal.hits}</td>{/if}
					</tr>
				{foreachelse}
					<tr><td colspan="6" class="norecords">{tr}No assemblies found.{/tr}</td></tr>
				{/foreach}
			</tbody>
		</table>

		<nav>
			{pagination}
		</nav>

	</section>
</div>
{/strip}
