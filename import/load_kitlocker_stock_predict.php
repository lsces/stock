<?php
/**
 * Sync KLSGL / KL3M from the "MERG Kitlocker - Stock predict" HTML export.
 *
 * Matches each row to an existing kitlocker item by KLID = Code, and upserts KLSGL (Current
 * Stock) and KL3M (No. sold in 3 months). A browser upload form (html_file) submits the
 * site's raw HTML export, no manual file copy needed.
 *
 * The upload always lands at a fixed working path (STOCK_IMPORT_PATH.'KitlockerStockPredict.html')
 * and is parsed from there, same as before. Once that parse completes, the working file is
 * archived to STOCK_IMPORT_PATH.'archive/' under its own original uploaded name (sanitized) —
 * same convention health's importers use, so successive dated exports don't overwrite each
 * other in the archive — and the fixed working copy is then deleted; the next upload recreates
 * it fresh.
 *
 * Codes with no matching KLID are reported as skipped rather than silently created. Each
 * skipped row's own name/KLSGL/KL3M ride along in its "Add as Assembly/Component" link
 * (?create=CODE:TYPE&name=...&klsgl=...&kl3m=...), since by the time that link is clicked the
 * working file has already been archived away — retry never needs to reopen any file.
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

$justUploaded  = false;
$uploadedName  = null;
if( !empty( $_FILES['html_file']['tmp_name'] ) && $_FILES['html_file']['error'] === UPLOAD_ERR_OK ) {
	$uploadedName = basename( $_FILES['html_file']['name'] );
	move_uploaded_file( $_FILES['html_file']['tmp_name'], $htmlFile );
	$justUploaded = true;
}

$loaded      = 0;
$created     = 0;
$skipped     = 0;
$errors      = [];
$skippedRows = [];
$displayFile = null;

if( $justUploaded ) {
	if( !file_exists( $htmlFile ) ) {
		$errors[] = 'HTML file not found: '.$htmlFile;
	} else {
		$html = file_get_contents( $htmlFile );
		$rows = stockParseKitlockerStockPredictHtml( $html );

		foreach( $rows as $row ) {
			$result = stockImportKitlockerStockPredictRow( $row );
			if( $result['matched'] ) {
				$loaded++;
			} else {
				$skipped++;
				$skippedRows[] = $row;
			}
		}

		$safeName = preg_replace( '/[^A-Za-z0-9 ()._-]/', '_', $uploadedName ?? '' );
		if( $safeName === '' ) {
			$safeName = 'KitlockerStockPredict.html';
		}
		$archiveDir = STOCK_IMPORT_PATH.'archive/';
		if( !is_dir( $archiveDir ) ) {
			mkdir( $archiveDir, 0777, true );
		}
		$displayFile = $archiveDir.$safeName;
		copy( $htmlFile, $displayFile );
		unlink( $htmlFile );
	}
} elseif( !empty( $_REQUEST['create'] ) ) {
	// Retry for one previously-skipped code — row data comes from the link, not a re-parsed file.
	[ $code, $type ] = array_pad( explode( ':', trim( $_REQUEST['create'] ), 2 ), 2, null );
	$type = $type !== null ? strtoupper( trim( $type ) ) : null;
	if( $code !== null && in_array( $type, [ 'A', 'C' ], true ) ) {
		$row = [
			'code'  => trim( $code ),
			'name'  => trim( $_REQUEST['name']  ?? '' ),
			'klsgl' => trim( $_REQUEST['klsgl'] ?? '' ),
			'kl3m'  => trim( $_REQUEST['kl3m']  ?? '' ),
		];
		$result = stockImportKitlockerStockPredictRow( $row, $type );
		if( $result['created'] ) {
			$created++;
		} elseif( $result['matched'] ) {
			$loaded++;
		} else {
			$skipped++;
			$skippedRows[] = $row;
		}
	}
}

$gBitSmarty->assign( 'uploadForm', true );
$gBitSmarty->assign( 'loaded',  $loaded );
$gBitSmarty->assign( 'created', $created );
$gBitSmarty->assign( 'skipped', $skipped );
$gBitSmarty->assign( 'errors',      $errors );
$gBitSmarty->assign( 'skippedRows', $skippedRows );
$gBitSmarty->assign( 'csvFile',     $displayFile ?? $htmlFile );

$gBitSystem->display( 'bitpackage:stock/import_results.tpl', 'Sync Kitlocker Stock Predict' );
