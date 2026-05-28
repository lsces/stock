<?php
/**
 * Phase 2: Import KitlockerAssemblies.csv under their KitLocker group assemblies.
 *
 * CSV columns: Title, Description, SGL, KL3M, Group
 * Group is numeric — 'G' is prepended to match the KLID xref stored by
 * load_kitlocker_groups.php. SGL and KL3M are stored as xref values.
 *
 * Already-existing assemblies (matched by title) are skipped.
 * Run load_kitlocker_groups.php first.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once STOCK_PKG_CLASS_PATH.'StockAssembly.php';

$csvFile = __DIR__.'/data/KitlockerAssemblies.csv';
$loaded  = $skipped = 0;
$errors  = [];

// Build KLID → content_id map from xref table
$klidMap = $gBitDb->getAssoc(
	"SELECT `xkey`, `content_id` FROM `".BIT_DB_PREFIX."liberty_xref` WHERE `item`=?",
	[ 'KLID' ]
);

if( empty( $klidMap ) ) {
	$errors[] = 'No KLID xref entries found — run load_kitlocker_groups.php first.';
} elseif( !file_exists( $csvFile ) ) {
	$errors[] = "File not found: $csvFile";
} else {
	$fh = fopen( $csvFile, 'r' );
	$rowNum = 0;
	while( ($cols = fgetcsv( $fh, 0, ',', '"', '' )) !== false ) {
		$rowNum++;
		if( $rowNum === 1 ) continue; // header: Title, Description, SGL, KL3M, Group

		$title       = trim( $cols[0] ?? '' );
		$description = trim( $cols[1] ?? '' );
		$sgl         = trim( $cols[2] ?? '' );
		$kl3m        = trim( $cols[3] ?? '' );
		$groupNum    = trim( $cols[4] ?? '' );
		$klid        = 'G'.$groupNum;

		if( $title === '' || $groupNum === '' ) {
			$skipped++;
			continue;
		}

		if( !isset( $klidMap[$klid] ) ) {
			$errors[] = "Row $rowNum: '$title' — group '$klid' not in KLID map, skipped.";
			$skipped++;
			continue;
		}

		// Skip if assembly already exists by title
		if( $gBitDb->getOne(
			"SELECT sa.`content_id` FROM `".BIT_DB_PREFIX."stock_assembly` sa
			 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = sa.`content_id`
			 WHERE lc.`title` = ?",
			[ $title ]
		) ) {
			$skipped++;
			continue;
		}

		$assembly = new StockAssembly();
		$pHash = [ 'title' => $title, 'edit' => $description, 'format_guid' => 'bithtml' ];
		if( !$assembly->store( $pHash ) ) {
			$errors[] = "Row $rowNum: failed to create '$title'";
			$skipped++;
			continue;
		}

		$contentId      = $assembly->mContentId;
		$groupContentId = (int)$klidMap[$klid];

		// Add as child of the group assembly
		$gBitDb->getOne(
			"INSERT INTO `".BIT_DB_PREFIX."stock_assembly_component_map`
			 (`assembly_content_id`, `item_content_id`, `item_position`) VALUES (?,?,NULL)",
			[ $groupContentId, $contentId ]
		);

		// Store SGL and KL3M xrefs
		foreach( [ 'SGL' => $sgl, 'KL3M' => $kl3m ] as $item => $value ) {
			if( $value !== '' && is_numeric( $value ) ) {
				$xrefId = $gBitDb->GenID( 'liberty_xref_seq' );
				$gBitDb->associateInsert( BIT_DB_PREFIX.'liberty_xref', [
					'xref_id'          => $xrefId,
					'content_id'       => $contentId,
					'item'             => $item,
					'xorder'           => 0,
					'xkey'             => $value,
					'last_update_date' => $gBitDb->NOW(),
				] );
			}
		}

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

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import KitLocker Assemblies' );
