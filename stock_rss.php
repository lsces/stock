<?php
/**
 * @version $Header$
 * @package stock
 * @subpackage functions
 */

/**
 * Initialization
 */
namespace Bitweaver\Stock;

use Bitweaver\KernelTools;
use Bitweaver\BitBase;
use Bitweaver\Rss\FeedItem;

require_once "../kernel/includes/setup_inc.php";

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPackage( 'rss' );
$gBitSystem->verifyFeature( 'stock_rss' );

require_once RSS_PKG_INCLUDE_PATH.'rss_inc.php';

$rss->title = $gBitSystem->getConfig( 'stock_rss_title', $gBitSystem->getConfig( 'site_title' ).' - '.KernelTools::tra( 'Image Galleries' ) );
$rss->description = $gBitSystem->getConfig( 'stock_rss_description', $gBitSystem->getConfig( 'site_title' ).' - '.KernelTools::tra( 'RSS Feed' ) );

// check permission to view stock images
if( !$gBitUser->hasPermission( 'p_stock_view' ) ) {
	require_once RSS_PKG_PATH."rss_error.php";
} else {
	$listHash = [
		'max_records' => $gBitSystem->getConfig( 'stock_rss_max_records', 10 ),
		'sort_mode'   => 'last_modified_desc',
		'assembly_id'  => !empty( $_REQUEST['assembly_id'] ) ? $_REQUEST['assembly_id'] : null,
		'user_id'     => !empty( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : null,
	];

	// check if we want to use the cache file
	$cacheFile = TEMP_PKG_PATH.RSS_PKG_NAME.'/'.STOCK_PKG_NAME.'/'."g{$listHash['assembly_id']}u{$listHash['user_id']}".$cacheFileTail;
	$rss->useCached( $rss_version_name, $cacheFile, $gBitSystem->getConfig( 'rssfeed_cache_time' ));

	// if we have a gallery we can work with - load it
	if( BitBase::verifyId( $_REQUEST['assembly_id'] ?? 0 ) ) {
		$gallery = new StockAssembly( $_REQUEST['assembly_id'] );
		$gallery->load();
		$rss->title .= " - {$gallery->getTitle()}";
	}

	$stock = new StockComponent();
	$feeds = $stock->getList( $listHash );

	// set the rss link
	$rss->link = 'http://'.$_SERVER['HTTP_HOST'].STOCK_PKG_URL;

	global $gBitSystem;
	// get all the data ready for the feed creator
	foreach( $feeds as $feed ) {
		$item               = new FeedItem();
		$item->title        = $feed['title'];
		$item->link         = $feed['display_url'];
		$item->description  = '<a href="'.$feed['display_url'].'"><img src="'.$feed['thumbnail_url'].'" /></a>';
		$item->description .= '<p>'.$feed['data'].'</p>';

		$item->date         = ( int )$feed['last_modified'];
		$item->source       = 'http://'.$_SERVER['HTTP_HOST'].BIT_ROOT_URL;
		$item->author       = $gBitUser->getDisplayName( false, $feed );

		$item->descriptionTruncSize = $gBitSystem->getConfig( 'rssfeed_truncate', 5000 );
		$item->descriptionHtmlSyndicated = false;

		// pass the item on to the rss feed creator
		$rss->addItem( $item );
	}

	// finally we are ready to serve the data
	echo $rss->saveFeed( $rss_version_name, $cacheFile );
}
