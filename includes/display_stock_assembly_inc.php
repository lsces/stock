<?php
/**
 * @package stock
 * @subpackage functions
 */

use Bitweaver\KernelTools;

$displayHash = [ 'perm_name' => 'p_stock_view' ];
$gContent->invokeServices( 'content_display_function', $displayHash );

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
$gContent->addHit();

$gBitSystem->setBrowserTitle( $gContent->getTitle().' '.KernelTools::tra('Assembly') );
$gBitSystem->display( $gContent->getRenderTemplate() , null, [ 'display_mode' => 'display' ] );
