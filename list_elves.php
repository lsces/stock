<?php
/**
 * List kit elves — contact persons with a linked user account.
 * Shows assembly and movement counts with links to filtered stock views.
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

$gBitSystem->verifyPackage( 'stock' );
$gBitUser->requirePermission( 'p_contact_view' );

global $gBitSystem, $gBitSmarty, $gBitDb;

$X = BIT_DB_PREFIX;

$rs = $gBitDb->query(
	"SELECT con.`content_id`, con.`role_id` AS user_id,
		lc.`title`,
		x00.`xkey_ext` AS name_parts,
		uu.`login` AS linked_user_login,
		uu.`real_name` AS linked_user_name,
		(SELECT COUNT(*) FROM `{$X}liberty_content` ac
		 WHERE ac.`content_type_guid` = 'stockassembly' AND ac.`user_id` = con.`role_id`) AS assembly_count,
		(SELECT COUNT(*) FROM `{$X}liberty_content` mc
		 WHERE mc.`content_type_guid` = 'stockmovement' AND mc.`user_id` = con.`role_id`) AS movement_count
	 FROM `{$X}contact` con
	 INNER JOIN `{$X}liberty_content` lc ON lc.`content_id` = con.`content_id`
	 INNER JOIN `{$X}users_users` uu ON uu.`user_id` = con.`role_id`
	 LEFT JOIN `{$X}liberty_xref` x00 ON x00.`content_id` = con.`content_id` AND x00.`item` = 'P01'
	 WHERE con.`role_id` IS NOT NULL
	 ORDER BY lc.`title`"
);

$elves = [];
while( $row = $rs->fetchRow() ) {
	$parts = explode( '|', $row['name_parts'] ?? '' );
	$row['display_name'] = trim( implode( ' ', array_filter( [
		$parts[0] ?? '',
		$parts[1] ?? '',
		$parts[2] ?? '',
		$parts[3] ?? '',
	] ) ) ) ?: $row['linked_user_name'] ?: $row['title'];
	$elves[] = $row;
}

$gBitSmarty->assign( 'elves', $elves );
$gBitSystem->setBrowserTitle( 'Kit Elves' );
$gBitSystem->display( 'bitpackage:stock/list_elves.tpl', null, [ 'display_mode' => 'list' ] );
