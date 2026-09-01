<?php
/**
 * Add a single component line to a stock movement BOM.
 * Same flow as add_component.php but for stockmovement content.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;
use Bitweaver\Liberty\LibertyContent;
use Bitweaver\Liberty\LibertyXref;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb, $gBitThemes;

include_once STOCK_PKG_INCLUDE_PATH.'movement_lookup_inc.php';

if( !$gContent->isValid() ) {
	$gBitSystem->fatalError( 'No valid movement specified.' );
}
$gContent->verifyUpdatePermission();

$validItems = [ 'SGL' => 'Single unit', 'PRT' => 'Part', 'SHT' => 'Sheet', 'VOL' => 'Volume' ];
$errors = [];

if( !empty( $_REQUEST['fCancel'] ) ) {
	header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$gContent->mContentId );
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
		// component_id is only populated once a suggestion is actually picked
		// (BitComponentTypeahead.js) — falls back to an exact-title match
		// otherwise, same as before this used the shared resolver.
		$compId = LibertyContent::resolveContentIdByTitle( (int)( $_REQUEST['component_id'] ?? 0 ), $title, 'stockcomponent' );

		if( !$compId ) {
			header( 'Location: '.STOCK_PKG_URL.'edit_component.php?title='.urlencode( $title ) );
			die;
		}

		$nextXorder = (int)$gBitDb->getOne(
			"SELECT COALESCE( MAX(x.`xorder`) + 1, 1 ) FROM `".BIT_DB_PREFIX."liberty_xref` x
			 WHERE x.`content_id` = ? AND x.`item` IN ('SGL','PRT','SHT','VOL')",
			[ $gContent->mContentId ]
		) ?: 1;

		$xrefObj = new LibertyXref();
		$xrefObj->mContentTypeGuid = 'stockmovement';
		$pHash = [
			'content_id' => $gContent->mContentId,
			'item'       => $item,
			'xorder'     => $nextXorder,
			'xref'       => $compId,
			'xkey'       => $xkey,
			'xkey_ext'   => trim( $_REQUEST['xkey_ext'] ?? '' ) ?: null,
			'edit'       => trim( $_REQUEST['edit'] ?? '' ) ?: null,
		];
		if( $xrefObj->store( $pHash ) ) {
			header( 'Location: '.STOCK_PKG_URL.'edit_movement.php?content_id='.$gContent->mContentId );
			die;
		}
		$errors[] = KernelTools::tra( 'Failed to store BOM entry.' );
	}
}

$gBitSmarty->assign( 'validItems', $validItems );
$gBitSmarty->assign( 'errors',     $errors );
$gBitSmarty->assign( 'lookupUrl',  STOCK_PKG_URL.'includes/lookup_component.php' );

$gBitThemes->loadJavascript( KERNEL_PKG_PATH.'scripts/BitComponentTypeahead.js', true );

$gBitSystem->display( 'bitpackage:stock/add_movement_component.tpl', KernelTools::tra( 'Add Component' ).': '.$gContent->getTitle(), [ 'display_mode' => 'edit' ] );
