<?php
/**
 * Load assemblies from a simple 4-column CSV (title, description, KLPR, KLURL).
 * No header row. Existing assemblies (by title) are skipped unless clear=y.
 *
 * Place your CSV at: stock/import/data/simple_assemblies.csv
 * Append ?clear=y to the URL to delete and re-import all rows.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once __DIR__.'/ImportSimpleAssembly.php';

$csvFile  = __DIR__.'/data/simple_assemblies.csv';
$doClear  = ( ( $_REQUEST['clear'] ?? '' ) === 'y' );
$loaded   = 0;
$skipped  = 0;
$deleted  = 0;
$errors   = [];

if( !file_exists( $csvFile ) ) {
	$errors[] = 'CSV file not found: '.$csvFile;
} else {
	$handle = fopen( $csvFile, 'r' );
	if( $handle === false ) {
		$errors[] = 'Cannot open CSV file.';
	} else {
		// Slurp all rows first so we can clear before inserting
		$rows = [];
		while( ( $data = fgetcsv( $handle, 1000, ',', '"', '' ) ) !== false ) {
			$rows[] = $data;
		}
		fclose( $handle );

		if( $doClear ) {
			foreach( $rows as $data ) {
				$title = trim( $data[0] ?? '' );
				if( !empty( $title ) && stockExpungeAssemblyByTitle( $title ) ) {
					$deleted++;
				}
			}
		}

		foreach( $rows as $rowNum => $data ) {
			$result   = stockImportSimpleAssembly( $data, $rowNum + 1 );
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

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import Assemblies' );
