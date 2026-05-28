<?php
/**
 * Phase 1: Import KitlockerGroups.csv as child assemblies of assembly 375.
 *
 * CSV columns: KLID, Title
 * Each group assembly is tagged with a KLID xref so load_kitlocker_assemblies.php
 * can locate the correct parent by KLID lookup.
 *
 * Already-existing groups (matched by KLID xref) are skipped.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once STOCK_PKG_CLASS_PATH.'StockAssembly.php';

$csvFile          = __DIR__.'/data/KitlockerGroups.csv';
$parentAssemblyId = 375;
$loaded           = $skipped = 0;
$errors           = [];

// Load parent assembly
$parent = new StockAssembly( $parentAssemblyId, null );
$parent->load();
if( !$parent->isValid() ) {
	$errors[] = "Parent assembly $parentAssemblyId not found.";
} elseif( !file_exists( $csvFile ) ) {
	$errors[] = "File not found: $csvFile";
} else {
	$fh = fopen( $csvFile, 'r' );
	$rowNum = 0;
	while( ($cols = fgetcsv( $fh, 0, ',', '"', '' )) !== false ) {
		$rowNum++;
		if( $rowNum === 1 ) continue; // header: KLID, Title

		$klid  = trim( $cols[0] ?? '' );
		$title = trim( $cols[1] ?? '' );
		if( $klid === '' || $title === '' ) continue;

		// Skip if KLID xref already exists
		if( $gBitDb->getOne(
			"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_xref` WHERE `item`=? AND `xkey`=?",
			[ 'KLID', $klid ]
		) ) {
			$skipped++;
			continue;
		}

		$assembly = new StockAssembly();
		$pHash = [ 'title' => $title, 'edit' => '', 'format_guid' => 'bithtml' ];
		if( !$assembly->store( $pHash ) ) {
			$errors[] = "Row $rowNum: failed to create '$title'";
			continue;
		}

		$contentId = $assembly->mContentId;

		// Tag with KLID xref
		$xrefId = $gBitDb->GenID( 'liberty_xref_seq' );
		$gBitDb->associateInsert( BIT_DB_PREFIX.'liberty_xref', [
			'xref_id'          => $xrefId,
			'content_id'       => $contentId,
			'item'             => 'KLID',
			'xorder'           => 0,
			'xkey'             => $klid,
			'last_update_date' => $gBitDb->NOW(),
		] );

		// Add as child of parent assembly 375
		$parent->addItem( $contentId );

		$loaded++;
	}
	fclose( $fh );
}

$gBitSmarty->assign( 'loaded',   $loaded );
$gBitSmarty->assign( 'skipped',  $skipped );
$gBitSmarty->assign( 'deleted',  0 );
$gBitSmarty->assign( 'errors',   $errors );
$gBitSmarty->assign( 'csvFile',  $csvFile );
$gBitSmarty->assign( 'movement', null );

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import KitLocker Groups' );
