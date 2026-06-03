<?php
/**
 * Add a single BOM component to an assembly.
 * Finds existing component by title and stores the BOM xref,
 * then redirects to component_order.php so position can be adjusted.
 * If the component title is not found, redirects to edit_component.php to create it.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;
use Bitweaver\Liberty\LibertyXref;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb, $gBitUser;

include_once STOCK_PKG_INCLUDE_PATH.'assembly_lookup_inc.php';

if( !$gContent->isValid() ) {
	$gBitSystem->fatalError( 'No valid assembly specified.' );
}
$gContent->verifyUpdatePermission();

$validItems = [ 'SGL' => 'Single unit', 'PCK' => 'Pack', 'SHT' => 'Sheet (H x W)', 'VOL' => 'Volume' ];
$errors = [];

if( !empty( $_REQUEST['fCancel'] ) ) {
	header( 'Location: '.$gContent->getEditUrl() );
	die;
}

if( !empty( $_REQUEST['fAddComponent'] ) ) {
	$title = trim( $_REQUEST['component_title'] ?? '' );
	$item  = strtoupper( trim( $_REQUEST['item'] ?? 'SGL' ) );
	$xkey  = trim( $_REQUEST['xkey'] ?? '' );

	if( !array_key_exists( $item, $validItems ) ) {
		$item = 'SGL';
	}

	if( $title === '' ) {
		$errors[] = KernelTools::tra( 'Component title is required.' );
	} else {
		// Find existing component by title, or create one
		$compId = (int)$gBitDb->getOne(
			"SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."liberty_content` lc
			 WHERE lc.`content_type_guid`='".STOCKCOMPONENT_CONTENT_TYPE_GUID."' AND lc.`title`=?",
			[ $title ]
		);

		if( !$compId ) {
			header( 'Location: '.STOCK_PKG_URL.'edit_component.php?title='.urlencode( $title ).'&assembly_content_id='.$gContent->mContentId );
			die;
		}

		if( $compId && empty( $errors ) ) {
			// Place new row at end of current BOM
			$maxXorder = (int)$gBitDb->getOne(
				"SELECT MAX(`xorder`) FROM `".BIT_DB_PREFIX."liberty_xref`
				 WHERE `content_id`=? AND `item` IN ('SGL','PCK','SHT','VOL')",
				[ $gContent->mContentId ]
			);

			$xrefObj = new LibertyXref();
			$xrefObj->mContentTypeGuid = STOCKASSEMBLY_CONTENT_TYPE_GUID;
			$pHash = [
				'content_id' => $gContent->mContentId,
				'item'       => $item,
				'xorder'     => $maxXorder + 10,
				'xref'       => $compId,
				'xkey'       => $xkey,
				'xkey_ext'   => trim( $_REQUEST['xkey_ext'] ?? '' ) ?: null,
				'edit'       => trim( $_REQUEST['edit'] ?? '' ) ?: null,
			];
			if( $xrefObj->store( $pHash ) ) {
				header( 'Location: '.STOCK_PKG_URL.'component_order.php?content_id='.$gContent->mContentId );
				die;
			} else {
				$errors[] = KernelTools::tra( 'Failed to store BOM entry.' );
			}
		}
	}
}

$gBitSmarty->assign( 'validItems', $validItems );
$gBitSmarty->assign( 'errors', $errors );
$gBitSmarty->assign( 'lookupUrl', STOCK_PKG_URL.'includes/lookup_component.php' );

$gBitSystem->display( 'bitpackage:stock/add_component.tpl', KernelTools::tra('Add Component').': '.$gContent->getTitle(), [ 'display_mode' => 'edit' ] );
