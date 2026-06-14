<?php
/**
 * Edit the data (description) field of a stgrp liberty_xref_item entry.
 * @package stock
 */

namespace Bitweaver\Stock;

use Bitweaver\KernelTools;

require_once '../kernel/includes/setup_inc.php';

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_admin' );

global $gBitSystem, $gBitSmarty, $gBitDb;

$item = trim( $_REQUEST['item'] ?? '' );
if( !preg_match( '/^[A-Z0-9]{2,8}$/', $item ) ) {
	$gBitSystem->fatalError( KernelTools::tra( 'Invalid item code.' ) );
}

$X = BIT_DB_PREFIX;

$row = $gBitDb->getRow(
	"SELECT `item`, `cross_ref_title`, `data`
	 FROM `{$X}liberty_xref_item`
	 WHERE `item` = ? AND `x_group` = 'stgrp' AND `content_type_guid` = 'stock'",
	[ $item ]
);
if( !$row ) {
	$gBitSystem->fatalError( KernelTools::tra( 'Stock group not found.' ) );
}

$errors = [];

if( !empty( $_POST['fSave'] ) ) {
	$data = trim( $_POST['edit'] ?? '' ) ?: null;
	$gBitDb->query(
		"UPDATE `{$X}liberty_xref_item` SET `data` = ?
		 WHERE `item` = ? AND `x_group` = 'stgrp' AND `content_type_guid` = 'stock'",
		[ $data, $item ]
	);
	header( 'Location: '.STOCK_PKG_URL.'view_kitlocker.php' );
	die;
}

if( !empty( $_POST['fCancel'] ) ) {
	header( 'Location: '.STOCK_PKG_URL.'view_kitlocker.php' );
	die;
}

$gBitSmarty->assign( 'stgrpItem', $row );
$gBitSmarty->assign( 'errors',    $errors );

$gBitSystem->setBrowserTitle( KernelTools::tra( 'Edit' ).': '.$row['cross_ref_title'] );
$gBitSystem->display( 'bitpackage:stock/edit_stgrp_item.tpl', null, [ 'display_mode' => 'edit' ] );
