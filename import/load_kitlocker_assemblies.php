<?php
/**
 * Load assemblies and components from KitlockerAssemblies.csv.
 *
 * Reads storage/stock/KitlockerAssemblies.csv (header row present).
 * Each row is routed to StockAssembly or StockComponent based on the Type column.
 * Append ?clear=y to delete existing records by title before re-importing.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once __DIR__.'/ImportKitlockerAssemblies.php';

$csvFile = STOCK_IMPORT_PATH . 'KitlockerAssemblies.csv';
$doClear = ( ( $_REQUEST['clear'] ?? '' ) === 'y' );
$loaded  = 0;
$skipped = 0;
$deleted = 0;
$errors  = [];

if( !file_exists( $csvFile ) ) {
	$errors[] = 'CSV file not found: '.$csvFile;
} else {
	$handle = fopen( $csvFile, 'r' );
	if( $handle === false ) {
		$errors[] = 'Cannot open CSV file.';
	} else {
		$rows = [];
		while( ( $data = fgetcsv( $handle, 0, ',', '"', '' ) ) !== false ) {
			$rows[] = $data;
		}
		fclose( $handle );

		// Skip header row
		$dataRows = array_slice( $rows, 1 );

		if( $doClear ) {
			foreach( $dataRows as $data ) {
				$title = trim( $data[0] ?? '' );
				$type  = strtoupper( trim( $data[6] ?? '' ) );
				if( !empty( $title ) && in_array( $type, [ 'A', 'C' ] ) ) {
					if( stockExpungeKitlockerItemByTitle( $title, $type ) ) {
						$deleted++;
					}
				}
			}
		}

		foreach( $dataRows as $idx => $data ) {
			$result   = stockImportKitlockerItem( $data, $idx + 2 );
			$loaded  += $result['loaded'];
			$skipped += $result['skipped'];
			$errors   = array_merge( $errors, $result['errors'] );
		}
	}
}

$gBitSmarty->assign( 'loaded',  $loaded );
$gBitSmarty->assign( 'skipped', $skipped );
$gBitSmarty->assign( 'deleted', $deleted );
$gBitSmarty->assign( 'errors',  $errors );
$gBitSmarty->assign( 'csvFile', $csvFile );

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import Kitlocker Assemblies & Components' );
