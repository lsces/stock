{strip}
<div class="assemblybar">
	<nav>
		{assign var=breadCrumbs value=$gContent->getBreadcrumbLinks(1)}
		<ol class="breadcrumb">
			{if $gContent->hasUpdatePermission()}
				{assign var=creatorInfo value=$gContent->mInfo}
				<li>{displayname user=$creatorInfo.creator_user user_id=$creatorInfo.creator_user_id|default:0 real_name=$creatorInfo.creator_real_name} :: <a href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php">Assemblies</a></li>
			{/if}
			{if $breadCrumbs}
				{foreach from=$breadCrumbs item=breadTitle key=breadId}
					{if $breadId==$gContent->mAssemblyId}<li class="active">{$breadTitle}</li>
					{else}<li><a href="{$smarty.const.STOCK_PKG_URL}view.php?assembly_id={$breadId}">{$breadTitle}</a></li>{/if}
				{/foreach}
			{/if}
		</ol>
	</nav>

	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='nav' serviceHash=$gContent->mInfo}

	<div class="clear"></div>
</div><!-- end .gallerybar -->
{/strip}
