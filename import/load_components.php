<?php
/**
 * Load stock components from a CSV file.
 *
 * Place your CSV at:  stock/import/data/components.csv
 *
 * CSV columns (with header row skipped):
 *   title, description, part_number, quantity_value, quantity_item
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once __DIR__.'/ImportComponent.php';

$csvFile  = __DIR__.'/data/components.csv';
$loaded   = 0;
$skipped  = 0;
$errors   = [];

if( !file_exists( $csvFile ) ) {
	$errors[] = 'CSV file not found: '.$csvFile;
} else {
	$handle = fopen( $csvFile, 'r' );
	if( $handle === false ) {
		$errors[] = 'Cannot open CSV file.';
	} else {
		$row = 0;
		while( ( $data = fgetcsv( $handle, 1000, ',' ) ) !== false ) {
			$row++;
			if( $row === 1 ) {
				continue; // skip header
			}
			if( StockComponentRecordLoad( $data ) ) {
				$loaded++;
			} else {
				$skipped++;
				$errors[] = "Row $row skipped: ".( trim( $data[0] ?? '' ) ?: '(empty title)' );
			}
		}
		fclose( $handle );
	}
}

$gBitSmarty->assign( 'loaded',  $loaded );
$gBitSmarty->assign( 'skipped', $skipped );
$gBitSmarty->assign( 'errors',  $errors );
$gBitSmarty->assign( 'csvFile', $csvFile );

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import Components' );
