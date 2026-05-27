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
		$formHash['assembly_id'] = $gContent->mAssemblyId;
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

} elseif( $gContent->isValid() && !empty( $_REQUEST['add_component'] ) ) {
	$title = trim( $_REQUEST['component_title'] ?? '' );
	if( $title !== '' ) {
		$row = $gBitDb->getRow(
			"SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."liberty_content` lc
			 WHERE UPPER(lc.`title`) = UPPER(?) AND lc.`content_type_guid` = 'stockcomponent'",
			[ $title ]
		);
		if( $row ) {
			$nextPos = ((int)$gBitDb->getOne(
				"SELECT MAX(`item_position`) FROM `".BIT_DB_PREFIX."stock_assembly_component_map` WHERE `assembly_content_id`=?",
				[ $gContent->mContentId ]
			)) + 1;
			if( !$gContent->addItem( $row['content_id'], $nextPos ) ) {
				$stockErrors[] = KernelTools::tra('Component already in this assembly:').' '.htmlspecialchars($title);
			}
		} else {
			$stockErrors[] = KernelTools::tra('Component not found:').' '.htmlspecialchars($title);
		}
	}
	if( empty( $stockErrors ) ) {
		header( 'Location: '.STOCK_PKG_URL.'edit.php?content_id='.$gContent->mContentId );
		die();
	}

} elseif( $gContent->isValid() && !empty( $_REQUEST['upload_components_csv'] ) ) {
	$csvLoaded = $csvSkipped = 0;
	$csvErrors = [];
	if( !empty( $_FILES['csv_file']['tmp_name'] ) && is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
		$nextPos = ((int)$gBitDb->getOne(
			"SELECT MAX(`item_position`) FROM `".BIT_DB_PREFIX."stock_assembly_component_map` WHERE `assembly_content_id`=?",
			[ $gContent->mContentId ]
		)) + 1;
		if( ($fh = fopen( $_FILES['csv_file']['tmp_name'], 'r' )) !== false ) {
			while( ($cols = fgetcsv($fh)) !== false ) {
				$title = trim($cols[0]);
				if( $title === '' || strtolower($title) === 'title' ) continue;
				$row = $gBitDb->getRow(
					"SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."liberty_content` lc
					 WHERE UPPER(lc.`title`) = UPPER(?) AND lc.`content_type_guid` = 'stockcomponent'",
					[ $title ]
				);
				if( $row ) {
					if( $gContent->addItem( $row['content_id'], $nextPos ) ) {
						$nextPos++;
						$csvLoaded++;
					} else {
						$csvSkipped++;
					}
				} else {
					$csvErrors[] = KernelTools::tra('Not found:').' '.htmlspecialchars($title);
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
			header( 'Location: '.STOCK_PKG_URL.'edit.php?content_id='.$gContent->mContentId );
			die();
		}
	}
}

// Initalize the errors list which contains any errors which occured during storage
$errors = !empty($gContent->mErrors) ? $gContent->mErrors : [];
$gBitSmarty->assign('errors', $errors);
if( !empty($stockErrors) ) {
	$gBitSmarty->assign('stockWarnings', $stockErrors);
}

if( $gContent->isValid() ) {
	$sortMode = $_REQUEST['sort_mode'] ?? 'item_position_asc';
	$gBitSmarty->assign( 'componentMap', $gContent->getComponentMapList($sortMode) );
	$gBitSmarty->assign( 'sortMode', $sortMode );
}

$gContent->mInfo['stockassembly_types'] = $gContent->getXrefGroupList();

$gContent->invokeServices( 'content_edit_function' );

$gBitSystem->display( 'bitpackage:stock/edit_assembly.tpl', KernelTools::tra('Edit Assembly: ').$gContent->getTitle() , [ 'display_mode' => 'edit' ]);
