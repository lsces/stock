<?php
/**
 * Load initial stock from a 3-column CSV (component_title, description[ignored], quantity).
 * No header row. Creates one IN movement and adds each row as an item.
 *
 * Place your CSV at: stock/import/data/stock.csv
 * Browse to this page to create/reload the movement in draft status.
 * Append ?process=y to commit stock levels and mark the movement complete.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

require_once STOCK_PKG_CLASS_PATH.'StockMovement.php';

$csvFile   = __DIR__.'/data/stock.csv';
$doProcess = ( ( $_REQUEST['process'] ?? '' ) === 'y' );
$loaded    = 0;
$skipped   = 0;
$errors    = [];
$movement  = null;

if( !file_exists( $csvFile ) ) {
	$errors[] = 'CSV file not found: '.$csvFile;
} else {
	$handle = fopen( $csvFile, 'r' );
	if( $handle === false ) {
		$errors[] = 'Cannot open CSV file.';
	} else {
		$rows = [];
		while( ( $data = fgetcsv( $handle, 1000, ',', '"', '' ) ) !== false ) {
			$rows[] = $data;
		}
		fclose( $handle );

		// Create the movement
		$movement = new StockMovement();
		$pHash = [
			'title'     => 'Initial Stock Load',
			'direction' => STOCK_MOVEMENT_IN,
			'status'    => 'pending',
		];
		if( !$movement->store( $pHash ) ) {
			$errors[] = 'Failed to create stock movement.';
		} else {
			foreach( $rows as $rowNum => $data ) {
				$componentTitle = trim( $data[0] ?? '' );
				$qty            = isset( $data[2] ) && is_numeric( trim( $data[2] ) ) ? (float)trim( $data[2] ) : null;

				if( empty( $componentTitle ) ) {
					$skipped++;
					$errors[] = "Row ".($rowNum+1).": empty title, skipped.";
					continue;
				}
				if( $qty === null || $qty <= 0 ) {
					$skipped++;
					$errors[] = "Row ".($rowNum+1).": '$componentTitle' — invalid quantity, skipped.";
					continue;
				}

				$contentId = $gBitDb->getOne(
					"SELECT sc.`content_id`
					 FROM `".BIT_DB_PREFIX."stock_component` sc
					 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = sc.`content_id`
					 WHERE lc.`title` = ?",
					[ $componentTitle ]
				);
				if( !$contentId ) {
					$skipped++;
					$errors[] = "Row ".($rowNum+1).": component '$componentTitle' not found, skipped.";
					continue;
				}

				$movement->addItem( (int)$contentId, $qty, 'SGL' );
				$loaded++;
			}

			if( $doProcess && $loaded > 0 ) {
				if( !$movement->processMovement() ) {
					$errors[] = 'processMovement() failed: '.implode( ', ', $movement->mErrors );
				}
			}
		}
	}
}

$gBitSmarty->assign( 'loaded',    $loaded );
$gBitSmarty->assign( 'skipped',   $skipped );
$gBitSmarty->assign( 'deleted',   0 );
$gBitSmarty->assign( 'errors',    $errors );
$gBitSmarty->assign( 'csvFile',   $csvFile );
$gBitSmarty->assign( 'movement',  $movement );
$gBitSmarty->assign( 'doProcess', $doProcess );

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Import Stock' );
