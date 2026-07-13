<?php
/**
 * Sync KLSGL / KL3M from the "MERG Kitlocker - Stock predict" HTML export.
 *
 * Reads storage/stock/KitlockerStockPredict.html (upload the site's raw export under that
 * name), matches each row to an existing kitlocker item by KLID = Code, and upserts
 * KLSGL (Current Stock) and KL3M (No. sold in 3 months). Codes with no matching KLID are
 * reported as skipped, unless named in ?create=CODE:TYPE,CODE:TYPE (TYPE is A for
 * StockAssembly or C for StockComponent), in which case a new record is created.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once __DIR__.'/ImportKitlockerStockPredict.php';

$htmlFile = STOCK_IMPORT_PATH.'KitlockerStockPredict.html';
$loaded   = 0;
$created  = 0;
$skipped  = 0;
$errors   = [];

$createTypes = [];
foreach( explode( ',', trim( $_REQUEST['create'] ?? '' ) ) as $pair ) {
	if( !str_contains( $pair, ':' ) ) {
		continue;
	}
	[ $code, $type ] = explode( ':', $pair, 2 );
	$createTypes[trim( $code )] = strtoupper( trim( $type ) );
}

if( !file_exists( $htmlFile ) ) {
	$errors[] = 'HTML file not found: '.$htmlFile;
} else {
	$html = file_get_contents( $htmlFile );
	$rows = stockParseKitlockerStockPredictHtml( $html );

	foreach( $rows as $row ) {
		$result = stockImportKitlockerStockPredictRow( $row, $createTypes[$row['code']] ?? null );
		if( $result['created'] ) {
			$created++;
		} elseif( $result['matched'] ) {
			$loaded++;
		} else {
			$skipped++;
			$errors[] = 'Code \''.$row['code'].'\' — no matching KLID, skipped.';
		}
	}
}

$gBitSmarty->assign( 'loaded',  $loaded );
$gBitSmarty->assign( 'created', $created );
$gBitSmarty->assign( 'skipped', $skipped );
$gBitSmarty->assign( 'errors',  $errors );
$gBitSmarty->assign( 'csvFile', $htmlFile );

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Sync Kitlocker Stock Predict' );
