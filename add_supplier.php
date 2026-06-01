<?php
/**
 * @package stock
 * @subpackage functions
 */

namespace Bitweaver\Stock;

use Bitweaver\Contact\Contact;
use Bitweaver\KernelTools;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitUser, $gContent;

include_once STOCK_PKG_INCLUDE_PATH.'component_lookup_inc.php';

if( !$gContent->isValid() ) {
	$gBitSystem->fatalError( 'No valid component specified.' );
}

$gContent->verifyUpdatePermission();

if( !empty( $_REQUEST['fCancel'] ) ) {
	header( 'Location: '.$gContent->getEditUrl() );
	die;
}

if( !empty( $_REQUEST['fAddSupplier'] ) ) {
	if( empty( $_REQUEST['supplier_content_id'] ) || !is_numeric( $_REQUEST['supplier_content_id'] ) ) {
		$gContent->mErrors[] = KernelTools::tra( 'Please select a supplier.' );
	} else {
		$supHash = [
			'content_id' => $gContent->mContentId,
			'item'       => '#SUP',
			'xref'       => (int)$_REQUEST['supplier_content_id'],
			'xkey'       => trim( $_REQUEST['part_number'] ?? '' ),
			'xkey_ext'   => trim( $_REQUEST['price'] ?? '' ),
			'edit'       => trim( $_REQUEST['note'] ?? '' ),
			'fAddXref'   => 1,
		];
		$gContent->storeXref( $supHash );

		if( empty( $gContent->mErrors ) ) {
			header( 'Location: '.$gContent->getEditUrl() );
			die;
		}
	}
}

$contact = new Contact();
$listHash = [ 'contact_type_guid' => ['$04'], 'sort_mode' => 'title_asc', 'max_records' => 500 ];
$supplierList = $contact->getList( $listHash );

$gBitSmarty->assign( 'supplierList', $supplierList );
$gBitSmarty->assign( 'errors', $gContent->mErrors );

$gBitSystem->display( 'bitpackage:stock/add_supplier.tpl', KernelTools::tra( 'Add Supplier' ), [ 'display_mode' => 'edit' ] );
