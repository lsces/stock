<?php
/**
 * Phase 2: Import KitlockerAssemblies.csv under their KitLocker group assemblies.
 *
 * CSV columns: Title, KLID, Data, KLSGL, KL3M, Group
 * Group is numeric — 'G' is prepended to match the KLID xref stored by
 * load_kitlocker_groups.php.
 *
 * Existing assemblies are matched by KLID xref so the title can be updated to
 * a tidier version. Old kitlocker xrefs are wiped and replaced on each run.
 *
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

$csvFile  = __DIR__.'/data/KitlockerAssemblies.csv';
$loaded   = $skipped = $updated = 0;
$errors   = [];

// KLID → content_id map for group assemblies (G1, G2, …)
$klidMap = $gBitDb->getAssoc(
	"SELECT lx.xkey, lx.content_id FROM `".BIT_DB_PREFIX."liberty_xref` lx
	 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.content_id=lx.content_id
	 WHERE lx.item=? AND lc.content_type_guid=?",
	[ 'KLID', STOCKASSEMBLY_CONTENT_TYPE_GUID ]
);

$kitlockerXrefItems = [ 'KLID', 'KLPR', 'KLSGL', 'KL3M', 'KLURL', 'KLSGL' ];

if( empty( $klidMap ) ) {
	$errors[] = 'No KLID xref entries found — run load_kitlocker_groups.php first.';
} elseif( !file_exists( $csvFile ) ) {
	$errors[] = "File not found: $csvFile";
} else {
	$fh = fopen( $csvFile, 'r' );
	$rowNum = 0;
	while( ($cols = fgetcsv( $fh, 0, ',', '"', '' )) !== false ) {
		$rowNum++;
		if( $rowNum === 1 ) continue; // header: Title, KLID, Data, KLSGL, KL3M, Group

		$title    = trim( $cols[0] ?? '' );
		$klid     = trim( $cols[1] ?? '' );
		$data     = trim( $cols[2] ?? '' );
		$klsgl    = trim( $cols[3] ?? '' );
		$kl3m     = trim( $cols[4] ?? '' );
		$groupNum = trim( $cols[5] ?? '' );
		$groupKey = 'G'.$groupNum;

		if( $title === '' || $klid === '' || $groupNum === '' ) {
			$skipped++;
			continue;
		}

		if( !isset( $klidMap[$groupKey] ) ) {
			$errors[] = "Row $rowNum: '$title' — group '$groupKey' not in KLID map, skipped.";
			$skipped++;
			continue;
		}

		$groupContentId = (int)$klidMap[$groupKey];

		// Find existing assembly by its own KLID xref
		$contentId = (int)$gBitDb->getOne(
			"SELECT lx.content_id FROM `".BIT_DB_PREFIX."liberty_xref` lx
			 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.content_id=lx.content_id
			 WHERE lx.item=? AND lx.xkey=? AND lc.content_type_guid=?",
			[ 'KLID', $klid, STOCKASSEMBLY_CONTENT_TYPE_GUID ]
		);

		if( $contentId ) {
			// Update title and description
			$assembly = new StockAssembly( null, $contentId );
			$assembly->load();
			$pHash = [ 'content_id' => $contentId, 'title' => $title, 'edit' => $data, 'format_guid' => 'bithtml' ];
			$assembly->store( $pHash );

			// Wipe old kitlocker xrefs
			$gBitDb->query(
				"DELETE FROM `".BIT_DB_PREFIX."liberty_xref`
				 WHERE content_id=? AND item IN ('KLID','KLPR','KLSGL','KL3M','KLURL')",
				[ $contentId ]
			);
			$updated++;
		} else {
			// Create new assembly
			$assembly = new StockAssembly();
			$pHash = [ 'title' => $title, 'edit' => $data, 'format_guid' => 'bithtml' ];
			if( !$assembly->store( $pHash ) ) {
				$errors[] = "Row $rowNum: failed to create '$title'";
				$skipped++;
				continue;
			}
			$contentId = $assembly->mContentId;

			// Link to parent group
			$alreadyLinked = (int)$gBitDb->getOne(
				"SELECT COUNT(*) FROM `".BIT_DB_PREFIX."stock_assembly_map`
				 WHERE assembly_content_id=? AND item_content_id=?",
				[ $groupContentId, $contentId ]
			);
			if( !$alreadyLinked ) {
				$gBitDb->query(
					"INSERT INTO `".BIT_DB_PREFIX."stock_assembly_map`
					 (assembly_content_id, item_content_id, item_position) VALUES (?,?,NULL)",
					[ $groupContentId, $contentId ]
				);
			}
			$loaded++;
		}

		// Store kitlocker xrefs
		foreach( [ 'KLID' => $klid, 'KLSGL' => $klsgl, 'KL3M' => $kl3m ] as $item => $value ) {
			if( $value !== '' ) {
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

		// Keep contentId in klidMap for subsequent lookups within this run
		$klidMap[$klid] = $contentId;
	}
	fclose( $fh );
}

$gBitSmarty->assign( 'loaded',   $loaded );
$gBitSmarty->assign( 'skipped',  $skipped );
$gBitSmarty->assign( 'deleted',  $updated );
$gBitSmarty->assign( 'errors',   $errors );
$gBitSmarty->assign( 'csvFile',  $csvFile );
$gBitSmarty->assign( 'movement', null );

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import KitLocker Assemblies' );
