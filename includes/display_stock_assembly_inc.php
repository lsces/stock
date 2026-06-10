<?php
/**
 * @package stock
 * @subpackage functions
 */

use Bitweaver\KernelTools;

$displayHash = [ 'perm_name' => 'p_stock_view' ];
$gContent->invokeServices( 'content_display_function', $displayHash );

$listHash = $_REQUEST;
$listHash['max_records'] = $max_records;

$gContent->loadComponents( $listHash );
$gContent->loadParentAssemblies();
$gContent->loadXrefInfo();
$gBitSmarty->assign( 'gXrefInfo', $gContent->mXrefInfo );
$gContent->addHit();

$gBitSmarty->assign( 'listInfo', $listHash['listInfo'] );

$gBitSystem->setBrowserTitle( $gContent->getTitle().' '.KernelTools::tra('Assembly') );
$gBitSystem->display( $gContent->getRenderTemplate() , null, [ 'display_mode' => 'display' ] );
