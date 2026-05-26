{strip}
{if $gBitSystem->isPackageActive( 'stock' ) && $modComponents}
	{bitmodule title="$moduleTitle" name="stock_components"}
		<ul class="list-unstyled">
			{foreach from=$modComponents item=modComp}
				<li class="{cycle values='odd,even'} item">
					<a href="{$modComp.display_url|escape}">
						{if $maxlen gt 0}
							{$modComp.title|escape|truncate:$maxlen:"...":true}
						{else}
							{$modComp.title|escape}
						{/if}
					</a>
					{if !empty($module_params.description)}
						<br />
						{if $maxlendesc gt 0}
							{$modComp.data|escape|truncate:$maxlendesc:"...":true}
						{else}
							{$modComp.data|escape}
						{/if}
					{/if}
					{if !$userComponents}
						<br />{tr}By{/tr} {displayname hash=$modComp}
					{/if}
				</li>
			{foreachelse}
				<li></li>
			{/foreach}
		</ul>
		{if $userComponents}
			<a href="{$smarty.const.STOCK_PKG_URL}list_components.php?user_id={$userComponents}">{tr}See more...{/tr}</a>
		{/if}
	{/bitmodule}
{/if}
{/strip}
