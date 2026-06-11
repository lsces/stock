<?php
/**
 * @package stock
 * @subpackage functions
 */

/**
 * required setup
 */
namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';
use Bitweaver\KernelTools;

global $gBitSystem, $gBitDb;

include_once LIBERTY_PKG_INCLUDE_PATH.'liberty_lib.php';
include_once STOCK_PKG_INCLUDE_PATH.'assembly_lookup_inc.php';

// Ensure the user has the permission to create or edit assemblies
if( $gContent->isValid() ){
	$gContent->verifyUpdatePermission();
}else{
	$gContent->verifyCreatePermission();
}

if( !empty( $_REQUEST['savegallery'] ) ) {
	if( $gContent->store( $_REQUEST ) ) {
		$gContent->load();
		header("location: ".$gContent->getDisplayUrl() );
		die();
	}
} elseif( !empty( $_REQUEST['delete'] ) ) {
	$gContent->hasUserPermission( 'p_stock_admin', true); // , KernelTools::tra( "You do not have permission to delete this image gallery" ) );

	if( !empty( $_REQUEST['cancel'] ) ) {
		// user cancelled - just continue on, doing nothing
	} elseif( empty( $_REQUEST['confirm'] ) ) {
		$formHash['delete'] = true;
		$formHash['content_id'] = $gContent->mContentId;
		$formHash['input'] = [
			'<label><input name="recurse" value="" type="radio" checked="checked" /> '.KernelTools::tra( 'Delete only components in this assembly. Sub-assemblies will not be removed.' ).'</label>',
			'<label><input name="recurse" value="all" type="radio" /> '.KernelTools::tra( 'Permanently delete all contents, even if they appear in other assemblies.' ).'</label>',
		];
		$gBitSystem->confirmDialog( $formHash,
			[
				'warning' => KernelTools::tra('Are you sure you want to delete this assembly?') . ' ' . $gContent->getTitle(),
				'error' => KernelTools::tra('This cannot be undone!'),
			],
		);
	} else {
		$userId = $gContent->getField( 'user_id' );

		$gContent->pRecursiveDelete = !empty( $_REQUEST['recurse'] ) && ($_REQUEST['recurse'] == 'all');

		if( $gContent->expunge() ) {
			header( "Location: ".STOCK_PKG_URL.'?user_id='.$userId );
		}
	}

} elseif( !empty($_REQUEST['cancelgallery'] ) ) {
	header( 'Location: '.$gContent->getDisplayUrl() );
	die();

} elseif( $gContent->isValid() && !empty( $_REQUEST['upload_bom_csv'] ) ) {
	$csvLoaded = $csvSkipped = 0;
	$csvErrors = [];
	if( !empty( $_FILES['csv_file']['tmp_name'] ) && is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
		$origName = preg_replace( '/[^a-zA-Z0-9_-]/', '_', pathinfo( $_FILES['csv_file']['name'], PATHINFO_FILENAME ) );
		$savedBom = STOCK_IMPORT_PATH . $origName . '_bom_' . $gContent->mContentId . '.csv';
		move_uploaded_file( $_FILES['csv_file']['tmp_name'], $savedBom );
		// Valid BOM unit types (MOV is for movements, not BOM lines)
		$validItems = [ 'SGL', 'PRT', 'SHT', 'VOL' ];
		if( ($fh = fopen( $savedBom, 'r' )) !== false ) {
			$rowNum = 0;
			while( ($cols = fgetcsv($fh, 0, ',', '"', '')) !== false ) {
				$rowNum++;
				$componentTitle = trim( $cols[0] ?? '' );
				if( $componentTitle === '' || strtolower( $componentTitle ) === 'component' ) continue;

				$xorder  = (int)( $cols[1] ?? 0 );
				$xkey    = trim( $cols[2] ?? '' );
				$item    = strtoupper( trim( $cols[3] ?? 'SGL' ) );
				$xkeyExt = trim( $cols[4] ?? '' ) ?: null;
				$data    = trim( $cols[5] ?? '' ) ?: null;

				if( !in_array( $item, $validItems ) ) {
					$item = 'SGL';
				}

				if( empty( $componentTitle ) ) {
					$csvErrors[] = KernelTools::tra('Row')." $rowNum: ".KernelTools::tra('empty component name');
					$csvSkipped++;
					continue;
				}

				$compId = $gBitDb->getOne(
					"SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."liberty_content` lc
					 WHERE lc.`content_type_guid` = 'stockcomponent' AND lc.`title` = ?",
					[ $componentTitle ]
				);
				if( !$compId ) {
					$csvErrors[] = KernelTools::tra('Row')." $rowNum: ".KernelTools::tra('component not found').' — '.htmlspecialchars( $componentTitle );
					$csvSkipped++;
					continue;
				}

				$xrefObj = new \Bitweaver\Liberty\LibertyXref();
				$xrefObj->mContentTypeGuid = STOCKASSEMBLY_CONTENT_TYPE_GUID;
				$pHash = [
					'content_id' => $gContent->mContentId,
					'item'       => $item,
					'xorder'     => $xorder,
					'xref'       => $compId,
					'xkey'       => $xkey,
					'xkey_ext'   => $xkeyExt,
					'edit'       => $data,
				];
				$existingId = $gBitDb->getOne(
					"SELECT `xref_id` FROM `".BIT_DB_PREFIX."liberty_xref`
					 WHERE `content_id`=? AND `item`=? AND `xorder`=?",
					[ $gContent->mContentId, $item, $xorder ]
				);
				if( $existingId ) {
					$xrefObj->load( $existingId );
					$pHash['xref_id'] = $existingId;
				}
				if( $xrefObj->store( $pHash ) ) {
					$csvLoaded++;
				} else {
					$csvErrors[] = KernelTools::tra('Row')." $rowNum: ".KernelTools::tra('failed to store').' '.htmlspecialchars($componentTitle);
					$csvSkipped++;
				}
			}
			fclose($fh);
		}
	}
	$gBitSmarty->assign( 'csvLoaded',  $csvLoaded );
	$gBitSmarty->assign( 'csvSkipped', $csvSkipped );
	$gBitSmarty->assign( 'csvErrors',  $csvErrors );

} elseif( $gContent->isValid() ) {
	foreach( $_REQUEST as $k => $v ) {
		if( preg_match( '/^remove_component_(\d+)$/', $k, $m ) ) {
			$gContent->removeItem( (int)$m[1] );
			header( 'Location: '.STOCK_PKG_URL.'edit_assembly.php?content_id='.$gContent->mContentId );
			die();
		}
	}
}

// Initalize the errors list which contains any errors which occured during storage
$errors = !empty($gContent->mErrors) ? $gContent->mErrors : [];
$gBitSmarty->assign('errors', $errors);

if( $gContent->isValid() ) {
	$sortMode = $_REQUEST['sort_mode'] ?? 'item_position_asc';
	$gBitSmarty->assign( 'componentMap', $gContent->getComponentMapList($sortMode) );
	$gBitSmarty->assign( 'sortMode', $sortMode );
}

$gContent->loadXrefInfo();
if( isset( $gContent->mXrefInfo->mGroups['stgrp'] ) ) {
	if( isset( $gContent->mXrefInfo->mGroups['kitlocker'] ) ) {
		$gContent->mXrefInfo->mGroups['kitlocker']->mXrefs = array_merge(
			$gContent->mXrefInfo->mGroups['kitlocker']->mXrefs,
			$gContent->mXrefInfo->mGroups['stgrp']->mXrefs
		);
	}
	unset( $gContent->mXrefInfo->mGroups['stgrp'] );
}
$gBitSmarty->assign( 'gXrefInfo', $gContent->mXrefInfo );

$gContent->invokeServices( 'content_edit_function' );

$gBitSystem->display( 'bitpackage:stock/edit_assembly.tpl', KernelTools::tra('Edit Assembly: ').$gContent->getTitle() , [ 'display_mode' => 'edit' ]);
