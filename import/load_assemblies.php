<?php
/**
 * Load stock assemblies and their BOM from a CSV file.
 *
 * Place your CSV at:  stock/import/data/assemblies.csv
 *
 * CSV columns (with header row skipped):
 *   assembly_title, component_title, quantity_value, quantity_item, item_position
 *
 * Rows are grouped by assembly_title. Components must already exist
 * (run load_components.php first).
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once __DIR__.'/ImportAssembly.php';

$csvFile = __DIR__.'/data/simple_assemblies.csv';
$loaded  = 0;
$skipped = 0;
$errors  = [];

if( !file_exists( $csvFile ) ) {
	$errors[] = 'CSV file not found: '.$csvFile;
} else {
	$handle = fopen( $csvFile, 'r' );
	if( $handle === false ) {
		$errors[] = 'Cannot open CSV file.';
	} else {
		// Group rows by assembly title
		$batches  = [];
		$rowNum   = 0;
		while( ( $data = fgetcsv( $handle, 1000, ',', '"', '\\' ) ) !== false ) {
			$rowNum++;
			if( $rowNum === 1 ) {
				continue; // skip header
			}
			$assemblyTitle = trim( $data[0] ?? '' );
			if( empty( $assemblyTitle ) ) {
				$skipped++;
				$errors[] = "Row $rowNum: empty assembly title, skipped.";
				continue;
			}
			$batches[$assemblyTitle][] = array_merge( [ $rowNum ], $data );
		}
		fclose( $handle );

		// Process each assembly batch
		foreach( $batches as $assemblyTitle => $rows ) {
			$result = StockAssemblyBatchLoad( $assemblyTitle, $rows );
			$loaded  += $result['loaded'];
			$skipped += $result['skipped'];
			$errors   = array_merge( $errors, $result['errors'] );
		}
	}
}

$gBitSmarty->assign( 'loaded',  $loaded );
$gBitSmarty->assign( 'skipped', $skipped );
$gBitSmarty->assign( 'errors',  $errors );
$gBitSmarty->assign( 'csvFile', $csvFile );

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import Assemblies' );
