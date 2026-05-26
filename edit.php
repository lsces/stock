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

global $gBitSystem;

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
}

// Initalize the errors list which contains any errors which occured during storage
$errors = !empty($gContent->mErrors) ? $gContent->mErrors : [];
$gBitSmarty->assign('errors', $errors);

$gContent->mInfo['stockassembly_types'] = $gContent->getXrefGroupList();

$gContent->invokeServices( 'content_edit_function' );

$gBitSystem->display( 'bitpackage:stock/edit_assembly.tpl', KernelTools::tra('Edit Assembly: ').$gContent->getTitle() , [ 'display_mode' => 'edit' ]);
