<?php
/**
 * Phase 1: Import KitlockerGroups.csv as child assemblies of a parent assembly.
 *
 * CSV columns: KLID, Title
 * Each group assembly is tagged with a KLID xref so load_kitlocker_assemblies.php
 * can locate the correct parent by KLID lookup.
 *
 * Safe to re-run: existing groups (matched by KLID xref) are skipped but their
 * parent link is always checked and added if missing.
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
$parentAssemblyId = 21;
$loaded           = $skipped = $linked = 0;
$errors           = [];

// Load parent assembly and verify it actually exists in the DB
$parent = new StockAssembly( $parentAssemblyId, null );
$parent->load();
if( !$parent->isValid() || empty( $parent->mContentId ) ) {
	$errors[] = "Parent assembly $parentAssemblyId not found or has no content_id — check the assembly_id.";
} elseif( !file_exists( $csvFile ) ) {
	$errors[] = "File not found: $csvFile";
} else {
	$parentContentId = $parent->mContentId;
	$fh = fopen( $csvFile, 'r' );
	$rowNum = 0;
	while( ($cols = fgetcsv( $fh, 0, ',', '"', '' )) !== false ) {
		$rowNum++;
		if( $rowNum === 1 ) continue; // header: KLID, Title

		$klid  = trim( $cols[0] ?? '' );
		$title = trim( $cols[1] ?? '' );
		if( $klid === '' || $title === '' ) continue;

		// Get existing content_id from KLID xref, or create new assembly
		$contentId = (int)$gBitDb->getOne(
			"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_xref` WHERE `item`=? AND `xkey`=?",
			[ 'KLID', $klid ]
		);

		if( $contentId ) {
			$skipped++;
		} else {
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
			$loaded++;
		}

		// Always ensure parent link exists (safe to re-run)
		$alreadyLinked = (int)$gBitDb->getOne(
			"SELECT COUNT(*) FROM `".BIT_DB_PREFIX."stock_assembly_component_map`
			 WHERE `assembly_content_id`=? AND `item_content_id`=?",
			[ $parentContentId, $contentId ]
		);
		if( !$alreadyLinked ) {
			$gBitDb->getOne(
				"INSERT INTO `".BIT_DB_PREFIX."stock_assembly_component_map`
				 (`assembly_content_id`, `item_content_id`, `item_position`) VALUES (?,?,NULL)",
				[ $parentContentId, $contentId ]
			);
			$linked++;
		}
	}
	fclose( $fh );
}

$gBitSmarty->assign( 'loaded',   $loaded );
$gBitSmarty->assign( 'skipped',  $skipped );
$gBitSmarty->assign( 'deleted',  $linked );
$gBitSmarty->assign( 'errors',   $errors );
$gBitSmarty->assign( 'csvFile',  $csvFile );
$gBitSmarty->assign( 'movement', null );

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import KitLocker Groups' );
