<?php
/**
 * Import the MERG "Part Sources" report (flat CSV) - see ImportMergParts.php.
 *
 * Place the CSV at: storage/stock/MergPartsList.csv
 * Dry run by default (nothing is written); add ?apply=1 to import.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once __DIR__.'/ImportMergParts.php';

$csvFile = STOCK_IMPORT_PATH.'MergPartsList.csv';
$apply   = !empty( $_REQUEST['apply'] );
$result  = [ 'rows' => [], 'summary' => [], 'contacts' => [], 'skippedSuppliers' => [], 'errors' => [] ];

if( !is_readable( $csvFile ) ) {
	$result['errors'][] = 'CSV file not found: '.$csvFile;
} else {
	$result = stockMergPartsImport( $csvFile, $apply );
}

$gBitSmarty->assign( 'apply',  $apply );
$gBitSmarty->assign( 'csvFile', $csvFile );
$gBitSmarty->assign( 'result', $result );

$gBitSystem->display( 'bitpackage:stock/load_merg_parts.tpl', 'MERG Parts Import' );
