<?php
/**
 * JSON autocomplete endpoint — returns component titles matching ?q=
 * Used by add_component.tpl datalist.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

use Bitweaver\Liberty\LibertyContent;

require_once '../../kernel/includes/setup_inc.php';

global $gBitUser;

if( !$gBitUser->hasPermission( 'p_stock_view' ) ) {
	header( 'Content-Type: application/json' );
	echo '[]';
	exit;
}

$q = trim( $_GET['q'] ?? '' );
if( strlen( $q ) < 2 ) {
	header( 'Content-Type: application/json' );
	echo '[]';
	exit;
}

header( 'Content-Type: application/json' );
echo json_encode( LibertyContent::lookupTitles( [ 'stockcomponent' ], $q ) );
exit;
