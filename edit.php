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

$gBitSmarty->assign( 'galleryPaginationTypes', $gContent::getAllLayouts() );

if( !empty( $_REQUEST['savegallery'] ) ) {
	// When creating a sub-assembly, inherit protector role from the first parent if not explicitly set
	if( !$gContent->isValid() && !empty( $_REQUEST['gallery_additions'] ) && ( !isset( $_REQUEST['protector']['role_id'] ) || $_REQUEST['protector']['role_id'] == -1 ) ) {
		$parentAssemblyId = reset( $_REQUEST['gallery_additions'] );
		if( $parentContentId = $gContent->mDb->GetOne( "SELECT `content_id` FROM `".BIT_DB_PREFIX."stock_assembly` WHERE `assembly_id`=?", [ $parentAssemblyId ] ) ) {
			if( ( $parentRole = $gContent->mDb->GetOne( "SELECT `role_id` FROM `".BIT_DB_PREFIX."liberty_content_role_map` WHERE `content_id`=?", [ $parentContentId ] ) ) !== false ) {
				$_REQUEST['protector']['role_id'] = $parentRole;
			}
		}
	}
	if( $gContent->store( $_REQUEST ) ) {
		$gContent->storePreference( 'is_public', !empty( $_REQUEST['is_public'] ) ? $_REQUEST['is_public'] : null );
		$gContent->storePreference( 'allow_comments', !empty( $_REQUEST['allow_comments'] ) ? $_REQUEST['allow_comments'] : null );
		$gContent->storePreference( 'assembly_pagination', !empty( $_REQUEST['assembly_pagination'] ) ? $_REQUEST['assembly_pagination'] : null );
		$gContent->storePreference( 'total_per_page', !empty( $_REQUEST['total_per_page'] ) ? (int)$_REQUEST['total_per_page'] : null );
		// make sure var is fully stuffed with current data
		$gContent->load();
		// set the mappings, or if nothing checked, nuke them all
		$gContent->addToAssemblies( !empty( $_REQUEST['gallery_additions'] ) ? $_REQUEST['gallery_additions'] : null );

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

$gBitSystem->setOnloadScript( 'updateGalleryPagination();' );

$gallery = $gContent->getParentAssemblies();
$gBitSmarty->assign( 'parentGalleries', $gallery );
$getHash = [
	'user_id'       => $gBitUser->mUserId,
//	'max_records'   => -1,
//	'no_thumbnails' => true,
//	'sort_mode'     => 'title_asc',
//	'show_empty'    => true,
];
if( $gContent->mContentId ) {
	$getHash['contain_item'] = $gContent->mContentId;
}
// modify listHash according to global preferences
if( $gBitSystem->isFeatureActive( 'stock_show_all_to_admins' ) && $gBitUser->hasPermission( 'p_stock_admin' ) ) {
	unset( $getHash['user_id'] );
} elseif( $gBitSystem->isFeatureActive( 'stock_show_public_on_upload' ) ) {
//	$getHash['show_public'] = true;
}
$galleryTree = $gContent->generateList( $getHash,  [ 'name' => "assembly_id", 'id' => "gallerylist", 'item_attributes' => [ 'class'=>'listingtitle'], 'radio_checkbox' => true, ] );
$gBitSmarty->assign( 'galleryTree', $galleryTree );

$gContent->mInfo['stockassembly_types'] = $gContent->getXrefGroupList();

$gContent->invokeServices( 'content_edit_function' );

$gBitSystem->display( 'bitpackage:stock/edit_assembly.tpl', KernelTools::tra('Edit Assembly: ').$gContent->getTitle() , [ 'display_mode' => 'edit' ]);
