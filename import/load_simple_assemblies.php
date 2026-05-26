<?php
/**
 * Load assemblies from a simple 4-column CSV (title, description, KLPR, KLURL).
 * No header row. Existing assemblies (by title) are skipped.
 *
 * Place your CSV at: stock/import/data/simple_assemblies.csv
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once __DIR__.'/ImportSimpleAssembly.php';

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
		$rowNum = 0;
		while( ( $data = fgetcsv( $handle, 1000, ',', '"', '' ) ) !== false ) {
			$rowNum++;
			$result   = stockImportSimpleAssembly( $data, $rowNum );
			$loaded  += $result['loaded'];
			$skipped += $result['skipped'];
			$errors   = array_merge( $errors, $result['errors'] );
		}
		fclose( $handle );
	}
}

$gBitSmarty->assign( 'loaded',  $loaded );
$gBitSmarty->assign( 'skipped', $skipped );
$gBitSmarty->assign( 'errors',  $errors );
$gBitSmarty->assign( 'csvFile', $csvFile );

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import Assemblies' );
