<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';
use Bitweaver\KernelTools;
use Bitweaver\Liberty\LibertyBase;

global $gBitSystem, $gBitUser;

include_once STOCK_PKG_INCLUDE_PATH.'assembly_lookup_inc.php';

$gContent->verifyUpdatePermission();

if( !empty($_REQUEST['cancel']) ) {
	header( 'Location: '.$gContent->getDisplayUrl() );
	die;
} elseif( !empty($_REQUEST['save_order']) ) {
	if( !empty( $_REQUEST['batch'] ) ) {
		$batchCon = array_flip( $_REQUEST['batch'] );
		$batchCon[key($batchCon)]++;
		$gBitSmarty->assign( 'batchEdit', $batchCon );
	}

	$gContent->loadComponents();

	$feedback = null;

	// Auto-sort before applying manual positions
	if( !empty( $_REQUEST['reorder_gallery'] ) ) {
		$reorder = [];
		switch( $_REQUEST['reorder_gallery'] ) {
			case 'title':
				foreach( array_keys( $gContent->mItems ) as $itemConId ) {
					$reorder[$gContent->mItems[$itemConId]->mContentId] = $gContent->mItems[$itemConId]->mInfo['title'];
				}
				break;
			case 'random':
				foreach( array_keys( $gContent->mItems ) as $itemConId ) {
					$reorder[$gContent->mItems[$itemConId]->mContentId] = rand( 0, 9999999 );
				}
				break;
		}
		if( $reorder ) {
			natcasesort( $reorder );
			$sortPos = 10;
			foreach( $reorder as $conId => $sortVal ) {
				$newOrder[$conId] = $sortPos;
				$sortPos += 10;
			}
		}
	}

	// Process each item: batch commands and position/title updates
	foreach( $_REQUEST['itemPosition'] as $contentId => $newPos ) {
		if( $galleryItem = LibertyBase::getLibertyObject( $contentId ) ) {
			$galleryItem->load();
			if( isset( $batchCon[$contentId] ) && !empty( $_REQUEST['batch_command'] ) ) {
				@list( $batchCommand, $batchParam ) = @explode( ':', $_REQUEST['batch_command'] );
				switch( $batchCommand ) {
					case 'delete':
						$galleryItem->expunge();
						$galleryItem = null;
						break;
					case 'remove':
						$parents = $galleryItem->getParentAssemblies();
						if( $galleryItem->isContentType( STOCKASSEMBLY_CONTENT_TYPE_GUID ) || count( $parents ) > 1 ) {
							$gContent->removeItem( $contentId );
						} else {
							$galleryItem->expunge();
						}
						$galleryItem = null;
						break;
					case 'gallerymove':
						if( empty( $destAssembly ) ) {
							$destAssembly = new StockAssembly( null, $batchParam );
							$destAssembly->load();
						}
						if( $batchParam != $contentId ) {
							$gContent->removeItem( $contentId );
						}
						// fall through to copy
					case 'gallerycopy':
						if( empty( $destAssembly ) ) {
							$destAssembly = new StockAssembly( null, $batchParam );
							$destAssembly->load();
						}
						if( $destAssembly->addItem( $contentId ) ) {
							$feedback['success'][] = $galleryItem->getTitle().' '.KernelTools::tra('added to').' '.$destAssembly->getTitle();
						} else {
							$feedback['error'][] = $galleryItem->getTitle().' '.KernelTools::tra('could not be added to').' '.$destAssembly->getTitle();
						}
						break;
				}
			}

			if( is_object( $galleryItem ) ) {
				// auto-sort position takes precedence over manual position
				$newPos = preg_replace( '/[^\d\.]/', '', !empty( $newOrder[$contentId] ) ? $newOrder[$contentId] : $newPos );

				if( !empty( $_REQUEST['item_title'][$contentId] ) && $galleryItem->mInfo['title'] != $_REQUEST['item_title'][$contentId] ) {
					$storeHash = [
						'title' => $_REQUEST['item_title'][$contentId],
						'edit'  => $galleryItem->getField( 'data' ),
					];
					$galleryItem->store( $storeHash );
				}

				$galleryItem->updatePosition( $gContent->mContentId, $newPos );
			}
		}
	}

	$_SESSION['component_order_feedback'] = $feedback;

	KernelTools::bit_redirect( STOCK_PKG_URL.'component_order.php?assembly_id='.$gContent->getField('assembly_id') );
}

if( !empty( $_SESSION['component_order_feedback'] ) ) {
	$feedback = $_SESSION['component_order_feedback'];
	unset( $_SESSION['component_order_feedback'] );
}

// Assembly list for move/copy targets
$listHash = [
	'user_id'       => $gBitUser->mUserId,
	'page'          => -1,
	'max_records'   => -1,
	'no_thumbnails' => true,
	'sort_mode'     => 'title_asc',
	'show_empty'    => true,
	'offset'        => 0,
];
if( $gBitSystem->isFeatureActive( 'stock_show_all_to_admins' ) && $gBitUser->hasPermission( 'p_stock_admin' ) ) {
	unset( $listHash['user_id'] );
}
$assemblyList = $gContent->getList( $listHash );
$gBitSmarty->assign( 'assemblyList', $assemblyList );

$gContent->loadComponents( $listHash );

if( !empty( $feedback ) ) {
	$gBitSmarty->assign( 'formfeedback', $feedback );
}

$gBitSystem->display( 'bitpackage:stock/component_order.tpl', KernelTools::tra('Order Components').': '.$gContent->getTitle(), [ 'display_mode' => 'display' ] );
