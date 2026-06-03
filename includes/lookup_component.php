<?php
/**
 * JSON autocomplete endpoint — returns component titles matching ?q=
 * Used by add_component.tpl datalist.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../../kernel/includes/setup_inc.php';

global $gBitDb, $gBitUser;

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

$rows = $gBitDb->getArray(
	"SELECT FIRST 30 lc.content_id, lc.title
	 FROM liberty_content lc
	 WHERE lc.content_type_guid=? AND LOWER(lc.title) LIKE ?
	 ORDER BY lc.title",
	[ 'stockcomponent', '%'.strtolower( $q ).'%' ]
);

header( 'Content-Type: application/json' );
echo json_encode( array_values( $rows ?? [] ) );
exit;
