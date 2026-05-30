<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';
use Bitweaver\KernelTools;
use Bitweaver\Liberty\LibertyXref;

global $gBitSystem, $gBitUser;

include_once STOCK_PKG_INCLUDE_PATH.'assembly_lookup_inc.php';

$gContent->verifyUpdatePermission();

if( !empty( $_REQUEST['cancel'] ) ) {
	header( 'Location: '.$gContent->getDisplayUrl() );
	die;
} elseif( !empty( $_REQUEST['save_order'] ) && !empty( $_REQUEST['xrefOrder'] ) ) {
	foreach( $_REQUEST['xrefOrder'] as $xrefId => $newXorder ) {
		$xrefId   = (int)$xrefId;
		$newXorder = (int)$newXorder;
		if( !$xrefId ) continue;

		$xrefObj = new LibertyXref();
		$xrefObj->mContentTypeGuid = STOCKASSEMBLY_CONTENT_TYPE_GUID;
		$xrefObj->load( $xrefId );
		if( $xrefObj->isValid() ) {
			$pHash = [
				'xref_id'    => $xrefId,
				'content_id' => $gContent->mContentId,
				'item'       => $xrefObj->mItem,
				'xorder'     => $newXorder,
			];
			$xrefObj->store( $pHash );
		}
	}
	// Reload so fresh xorder data is displayed
	foreach( [ 'supplier', 'quantity', 'values', 'kitlocker', 'history' ] as $_xg ) {
		unset( $gContent->mInfo[$_xg] );
	}
	$gContent->loadXrefList();
}

$gBitSystem->display( 'bitpackage:stock/component_order.tpl', KernelTools::tra('Parts List').': '.$gContent->getTitle(), [ 'display_mode' => 'display' ] );
