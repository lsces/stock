<?php
/**
 * @package stock
 * @subpackage modules
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;
use Bitweaver\Users\RoleUser;
global $gQueryUserId, $gContent, $moduleParams;

$component = new StockComponent();

$display = true;

$listHash = $moduleParams->value;

if( !empty( $gContent ) && $gContent->getField( 'content_type_guid' ) == STOCKASSEMBLY_CONTENT_TYPE_GUID ) {
	$listHash['assembly_content_id'] = $gContent->mContentId;
}

if( $display ) {
	$listHash['max_records'] = $module_rows ?? 3;
	if( $gQueryUserId ) {
		$listHash['user_id'] = $gQueryUserId;
	} elseif( !empty( $_REQUEST['user_id'] ) ) {
		$gBitSmarty->assign( 'userComponents', $_REQUEST['user_id'] );
		$listHash['user_id'] = $_REQUEST['user_id'];
	} elseif( !empty( $listHash['recent_users'] ) ) {
		$listHash['recent_users'] = true;
	}

	$sort_options = [ 'hits', 'created' ];
	$sort_mode = !empty( $listHash['sort_mode'] ) && in_array( $listHash['sort_mode'], $sort_options )
		? $listHash['sort_mode'].'_desc' : 'random';

	$listHash['sort_mode'] = $sort_mode;

	$components = $component->getList( $listHash );

	if( empty( $title ) && $components ) {
		$moduleTitle = match( $sort_mode ) {
			'random'  => 'Random',
			'created_desc' => 'Recent',
			'hits_desc'    => 'Popular',
			default        => 'Random',
		};

		$moduleTitle .= ' Components';
		$moduleTitle = KernelTools::tra( $moduleTitle );

		if( !empty( $listHash['user_id'] ) ) {
			$moduleTitle .= ' '.KernelTools::tra('by').' '.RoleUser::getDisplayNameFromHash( current( $components ), true );
		} elseif( !empty( $listHash['recent_users'] ) ) {
			$moduleTitle .= ' '.KernelTools::tra( 'by' ).' <a href="'.USERS_PKG_URL.'">'.KernelTools::tra( 'New Users' ).'</a>';
		}

		$gBitSmarty->assign( 'moduleTitle', $moduleTitle );
	} else {
		$gBitSmarty->assign( 'moduleTitle', $title );
	}

	$gBitSmarty->assign( 'modComponents', $components );
	$gBitSmarty->assign( 'module_params', $listHash );
	$gBitSmarty->assign( 'maxlen', isset( $listHash['maxlen'] ) ? (int)$listHash['maxlen'] : 0 );
	$gBitSmarty->assign( 'maxlendesc', isset( $listHash['maxlendesc'] ) ? (int)$listHash['maxlendesc'] : 0 );
}
