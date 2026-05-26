<?php
/**
 * Load components from a 5-column CSV (title, description, supplier, PN, price).
 * First row is a header and is skipped. Existing components (by title) are skipped
 * unless clear=y is passed.
 *
 * Place your CSV at: stock/import/data/simple_components.csv
 * Append ?clear=y to the URL to delete and re-import all rows.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once __DIR__.'/ImportSimpleComponent.php';

$csvFile = __DIR__.'/data/simple_components.csv';
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
		$rows   = [];
		$rowNum = 0;
		while( ( $data = fgetcsv( $handle, 1000, ',', '"', '' ) ) !== false ) {
			$rowNum++;
			if( $rowNum === 1 ) {
				continue; // skip header
			}
			$rows[] = $data;
		}
		fclose( $handle );

		if( $doClear ) {
			foreach( $rows as $data ) {
				$title = trim( $data[0] ?? '' );
				if( !empty( $title ) && stockExpungeComponentByTitle( $title ) ) {
					$deleted++;
				}
			}
		}

		foreach( $rows as $idx => $data ) {
			$result   = stockImportSimpleComponent( $data, $idx + 2 ); // +2: 1-based + header
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

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import Components' );
