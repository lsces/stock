<?php
/**
 * Create stub components for Rapid Online parts not yet in the system.
 *
 * Reads a MERG group CSV (merg_group_<X>.csv in storage/stock/), finds every row
 * with Source='R' and a non-empty order code, checks whether a #SUP xref for that
 * Rapid code already exists, and creates a stub component titled RO<code> for any
 * that are missing.
 *
 * Usage: load_merg_stubs.php?group=E
 * Append &dry=1 to preview without writing anything.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

use Bitweaver\Liberty\LibertyContent;

require_once '../../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

const RAPID_CONTENT_ID = 109;

$group    = preg_replace( '/[^A-Za-z]/', '', $_REQUEST['group'] ?? '' );
$dryRun   = !empty( $_REQUEST['dry'] );
$doUpdate = !empty( $_REQUEST['update'] );

$csvFile = STOCK_IMPORT_PATH . 'merg_group_' . strtoupper( $group ) . '.csv';

$created  = 0;
$skipped  = 0;
$errors   = [];
$rows_out = [];

if( empty( $group ) ) {
	$errors[] = 'No group specified — append ?group=A through ?group=E to the URL.';
} elseif( !file_exists( $csvFile ) ) {
	$errors[] = 'CSV not found: ' . $csvFile;
} else {
	$handle = fopen( $csvFile, 'r' );
	if( $handle === false ) {
		$errors[] = 'Cannot open: ' . $csvFile;
	} else {
		$rowNum = 0;
		while( ( $data = fgetcsv( $handle, 2000, ',', '"', '' ) ) !== false ) {
			$rowNum++;
			if( $rowNum <= 2 ) {
				continue; // title + header rows
			}

			$component = trim( $data[0] ?? '' );
			$orderCode = trim( $data[1] ?? '' );
			$source    = strtoupper( trim( $data[2] ?? '' ) );

			// Skip legend rows (empty component), non-Rapid, or no order code
			if( empty( $component ) || $source !== 'R' || empty( $orderCode ) ) {
				continue;
			}

			// Check for existing #SUP xref for this Rapid code
			$existingId = $gBitDb->getOne(
				"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_xref`
				 WHERE `item` = '#SUP' AND `xref` = ? AND `xkey` = ?",
				[ RAPID_CONTENT_ID, $orderCode ]
			);

			if( $existingId ) {
				// Update mode — backfill description on existing RO* stubs
				if( $doUpdate ) {
					$stubTitle = $gBitDb->getOne(
						"SELECT `title` FROM `".BIT_DB_PREFIX."liberty_content` WHERE `content_id` = ?",
						[ $existingId ]
					);
					if( strncmp( (string)$stubTitle, 'RO', 2 ) === 0 ) {
						if( !$dryRun ) {
							$stockComponent = new StockComponent( (int)$existingId );
							$stockComponent->load();
							$pHash = [
								'title'       => $stubTitle,
								'edit'        => $component,
								'format_guid' => 'bithtml',
							];
							$stockComponent->store( $pHash );
						}
						$created++;
						$rows_out[] = [ 'code' => $orderCode, 'component' => $component, 'status' => $dryRun ? 'would update' : 'updated', 'content_id' => $existingId ];
					} else {
						$skipped++;
						$rows_out[] = [ 'code' => $orderCode, 'component' => $component, 'status' => 'skip (renamed)', 'content_id' => $existingId ];
					}
				} else {
					$skipped++;
					$rows_out[] = [ 'code' => $orderCode, 'component' => $component, 'status' => 'exists', 'content_id' => $existingId ];
				}
				continue;
			}

			$stubTitle = 'RO' . $orderCode;

			if( !$dryRun ) {
				$stockComponent = new StockComponent();
				$pHash = [
					'title'       => $stubTitle,
					'edit'        => $component,
					'format_guid' => 'bithtml',
				];
				if( !$stockComponent->store( $pHash ) ) {
					$errors[] = "Row $rowNum ($orderCode): failed to create component.";
					$rows_out[] = [ 'code' => $orderCode, 'component' => $component, 'status' => 'error', 'content_id' => null ];
					continue;
				}

				$newContentId = $stockComponent->mContentId;

				LibertyContent::upsertXrefByContentId( $newContentId, '#SUP', [
					'xorder' => 1,
					'xref'   => RAPID_CONTENT_ID,
					'xkey'   => substr( $orderCode, 0, 32 ),
				] );

				$created++;
				$rows_out[] = [ 'code' => $orderCode, 'component' => $component, 'status' => 'created', 'content_id' => $newContentId ];
			} else {
				$rows_out[] = [ 'code' => $orderCode, 'component' => $component, 'status' => 'would create', 'content_id' => null ];
				$created++;
			}
		}
		fclose( $handle );
	}
}

global $gBitSmarty;
$gBitSmarty->assign( 'group',    $group );
$gBitSmarty->assign( 'dryRun',  $dryRun );
$gBitSmarty->assign( 'doUpdate', $doUpdate );
$gBitSmarty->assign( 'csvFile', $csvFile );
$gBitSmarty->assign( 'created', $created );
$gBitSmarty->assign( 'skipped', $skipped );
$gBitSmarty->assign( 'errors',  $errors );
$gBitSmarty->assign( 'rows',    $rows_out );

$gBitSystem->display( 'bitpackage:stock/load_merg_stubs.tpl', 'MERG Stub Import — Group ' . strtoupper( $group ) );
