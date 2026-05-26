{strip}
<div class="gallerybar">
	<nav>
		{assign var=breadCrumbs value=$gContent->getBreadcrumbLinks(1)}
		<ol class="breadcrumb">
			{if $gContent->hasUpdatePermission()}
				{if !empty($gGallery->mInfo)}
					{assign var=creatorInfo value=$gGallery->mInfo}
				{else}
					{assign var=creatorInfo value=$gContent->mInfo}
				{/if}
				<li>{displayname user=$creatorInfo.creator_user user_id=$creatorInfo.creator_user_id|default:0 real_name=$creatorInfo.creator_real_name} :: <a href="{$smarty.const.STOCK_PKG_URL}?user_id={$creatorInfo.user_id}">Galleries</a></li>
			{/if}
			{if $breadCrumbs}
				{foreach from=$breadCrumbs item=breadTitle key=breadId}
					{if $breadId==$gContent->mGalleryId}<li class="active">{$breadTitle}</li>
					{else}<li><a href="{$smarty.const.STOCK_PKG_URL}gallery/{$breadId}">{$breadTitle}</a></li>{/if}
				{/foreach}
			{/if}
		</ol>
	</nav>

	{include file="bitpackage:liberty/services_inc.tpl" serviceLocation='nav' serviceHash=$gContent->mInfo}

	<div class="clear"></div>
</div><!-- end .gallerybar -->
{/strip}
