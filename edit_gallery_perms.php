<?php
/**
 * @version $Header$
 * @package stock
 * @subpackage functions
 */

/**
 * required setup
 */
namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';
use Bitweaver\KernelTools;

global $gBitSystem, $stockPermNameMap;

// Make sure an gallery has been specified
if (empty($_REQUEST['assembly_id'])) {
	$gBitSmarty->assign('msg', KernelTools::tra("No gallery specified") );
	$gBitSystem->display( "error.tpl" , null, [ 'display_mode' => 'edit' ] );
	die;
}

include_once STOCK_PKG_INCLUDE_PATH.'gallery_lookup_inc.php';

if (empty($gContent->mContentId)) {
	$gBitSmarty->assign( 'msg', KernelTools::tra( "The specified gallery does not exist" ));
	$gBitSystem->display("error.tpl", null, [ 'display_mode' => 'edit' ] );
	die;
} elseif ($gContent->mInfo['user_id'] != $gBitUser->mUserId && $gContent->mInfo['perm_level'] < STOCK_PERM_ADMIN) {
	// This user does not own this gallery and they have not been granted the permission to edit user permissions for this gallery
	$gBitSmarty->assign( 'msg', KernelTools::tra( "You cannot edit this image gallery" ) );
	$gBitSystem->display( "error.tpl" , null, [ 'display_mode' => 'edit' ]);
	die;
}

if (!empty($_REQUEST['submitNewPermissions'])) {
	$gContent->grantUserPermissions($_REQUEST['new_perm_user_id'], $_REQUEST['new_perm_level']);
	$stockSuccess[] = $_REQUEST['found_username']." given ".$stockPermNameMap[$_REQUEST['name_perm_level']]." permissions";
}elseif (!empty($_REQUEST['remove_perm_user_id'])) {
	$gContent->revokeUserPermission($_REQUEST['remove_perm_user_id']);
	$stockSuccess[] = KernelTools::tra("User permissions successfully revoked");
}

$userPerms = $gContent->loadPermissions();
$gBitSmarty->assign('userPerms', $gContent->mPerms);

if (!empty($_REQUEST['submitUpdatePerms'])) {
	$existingPerms = $_REQUEST['existingPerms'];
	foreach ($userPerms as $userPerm) {
		if ($existingPerms[$userPerm['user_id']]['perm_level'] != $userPerm['perm_level']) {
			// Permisson level for this user has been altered
			$gContent->grantUserPermissions($userPerm['user_id'], $existingPerms[$userPerm['user_id']]);
			$stockSuccess[] = $userPerm['real_name']." given ".$stockPermNameMap[$existingPerms[$userPerm['user_id']]]." permissions.";
		}
	}
	$userPerms = $gContent->getAllUserPermissions();
}

$gBitSystem->display('bitpackage:stock/edit_gallery_perms.tpl', null, [ 'display_mode' => 'edit' ] );
