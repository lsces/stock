{* $Header$ *}
{strip}
{if !empty($gGallery) && $gContent}
	{if !empty($gGallery->mInfo.previous_image_id)}
		<link rel="prev" title="{tr}Previous{/tr}" href="{$gContent->getImageUrl($gGallery->mInfo.previous_image_id)|escape}" />
	{/if}
	{if !empty($gGallery->mInfo.next_image_id)}
		<link rel="next" title="{tr}Next{/tr}" href="{$gContent->getImageUrl($gGallery->mInfo.next_image_id)|escape}" />
	{/if}
{/if}
{if $gBitSystem->isPackageActive( 'rss' ) && $gBitSystem->isFeatureActive( 'stock_rss' ) && $gBitSystem->getActivePackage() eq 'stock' && $gBitUser->hasPermission( 'p_stock_view' )}
	{if !empty($gGallery)}
		{assign var=stock_rss_gal_id value=$gGallery->mGalleryId}
	{elseif $gContent}
		{assign var=stock_rss_gal_id value=$gContent->mGalleryId}
	{/if}
	<link rel="alternate" type="application/rss+xml" title="{$gBitSystem->getConfig('stock_rss_title',"{tr}Image Galleries{/tr} RSS")}" href="{$smarty.const.STOCK_PKG_URL}stock_rss.php?version={$gBitSystem->getConfig('rssfeed_default_version',0)}&amp;gallery_id={$stock_rss_gal_id}" />
{/if}
{/strip}
