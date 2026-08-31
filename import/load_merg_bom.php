<?php
/**
 * Import MERG group BOM quantities into existing kitlocker assemblies.
 *
 * Reads merg_group_<X>.csv from storage/stock/. Row 1 is the title (skipped).
 * Row 2 is the header: cols 0-2 are Component/Order code/Source; any col from 3
 * onwards whose header is a plain integer is treated as an assembly content_id.
 * Non-numeric header cells (e.g. "Notes") are ignored.
 *
 * For each data row with Source='R' and a non-empty Rapid order code:
 *   - looks up the component via #SUP xref (xref=109, xkey=order code)
 *   - for each assembly column with a non-zero quantity, writes a BOM xref
 *
 * Usage:  load_merg_bom.php?group=E
 *         load_merg_bom.php?group=E&dry=1     preview only
 *         load_merg_bom.php?group=E&clear=1   remove existing BOM lines first
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

use Bitweaver\Liberty\LibertyContent;
use Bitweaver\Liberty\LibertyXref;

global $gBitSystem, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

const RAPID_SUPPLIER_ID = 109;

$group   = preg_replace( '/[^A-Za-z]/', '', $_REQUEST['group'] ?? '' );
$dryRun  = !empty( $_REQUEST['dry'] );
$doClear = !empty( $_REQUEST['clear'] );

$csvFile = STOCK_IMPORT_PATH . 'merg_group_' . strtoupper( $group ) . '.csv';

$errors      = [];
$assemblies  = [];   // keyed by content_id: [ 'title'=>, 'loaded'=>, 'skipped'=>, 'cleared'=> ]
$asmCols     = [];   // col index => assembly content_id

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

			if( $rowNum === 1 ) {
				continue; // title row
			}

			if( $rowNum === 2 ) {
				// Header row — detect assembly columns by numeric content_id
				foreach( $data as $col => $cell ) {
					$cell = trim( $cell );
					if( $col >= 3 && ctype_digit( $cell ) && (int)$cell > 0 ) {
						$asmId = (int)$cell;
						$asmTitle = $gBitDb->getOne(
							"SELECT `title` FROM `".BIT_DB_PREFIX."liberty_content` WHERE `content_id` = ?",
							[ $asmId ]
						);
						if( !$asmTitle ) {
							$errors[] = "Header col $col: assembly content_id $asmId not found.";
							continue;
						}
						$asmCols[$col] = $asmId;
						$assemblies[$asmId] = [ 'title' => $asmTitle, 'loaded' => 0, 'skipped' => 0, 'cleared' => 0 ];

						if( $doClear && !$dryRun ) {
							$assemblies[$asmId]['cleared'] = LibertyContent::deleteXrefByItem(
								$asmId, [ 'SGL', 'PRT', 'PCK', 'SHT', 'VOL' ]
							);
						}
					}
				}
				continue;
			}

			// Data rows
			$component = trim( $data[0] ?? '' );
			$orderCode = trim( $data[1] ?? '' );
			$source    = strtoupper( trim( $data[2] ?? '' ) );

			if( empty( $component ) || $source !== 'R' || empty( $orderCode ) ) {
				continue;
			}

			$compId = $gBitDb->getOne(
				"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_xref`
				 WHERE `item` = '#SUP' AND `xref` = ? AND `xkey` = ?",
				[ RAPID_SUPPLIER_ID, $orderCode ]
			);

			if( !$compId ) {
				$errors[] = "Row $rowNum ($orderCode): component not found — run stub import first.";
				continue;
			}

			foreach( $asmCols as $col => $asmId ) {
				$qty = trim( $data[$col] ?? '' );
				if( $qty === '' || $qty === '0' || (float)$qty == 0 ) {
					continue;
				}

				// xorder = next available for this assembly
				$xorder = (int)$gBitDb->getOne(
					"SELECT COALESCE(MAX(`xorder`), 0) + 1 FROM `".BIT_DB_PREFIX."liberty_xref`
					 WHERE `content_id` = ? AND `item` IN ('SGL','PRT','PCK','SHT','VOL')",
					[ $asmId ]
				);

				if( !$dryRun ) {
					$xrefObj = new LibertyXref();
					$xrefObj->mContentTypeGuid = 'stockassembly';
					$pHash = [
						'content_id' => $asmId,
						'item'       => 'SGL',
						'xorder'     => $xorder,
						'xref'       => $compId,
						'xkey'       => $qty,
					];
					if( $xrefObj->store( $pHash ) ) {
						$assemblies[$asmId]['loaded']++;
					} else {
						$errors[] = "Row $rowNum ($orderCode) → assembly $asmId: xref store failed.";
						$assemblies[$asmId]['skipped']++;
					}
				} else {
					$assemblies[$asmId]['loaded']++;
				}
			}
		}
		fclose( $handle );
	}
}

global $gBitSmarty;
$gBitSmarty->assign( 'group',      $group );
$gBitSmarty->assign( 'dryRun',    $dryRun );
$gBitSmarty->assign( 'doClear',   $doClear );
$gBitSmarty->assign( 'csvFile',   $csvFile );
$gBitSmarty->assign( 'asmCols',   $asmCols );
$gBitSmarty->assign( 'assemblies', $assemblies );
$gBitSmarty->assign( 'errors',    $errors );

$gBitSystem->display( 'bitpackage:stock/load_merg_bom.tpl', 'MERG BOM Import — Group ' . strtoupper( $group ) );
