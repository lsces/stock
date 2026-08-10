<?php
/**
 * @package stock
 */

namespace Bitweaver\Stock;

require_once '../kernel/includes/setup_inc.php';

global $gBitSystem, $gBitSmarty, $gBitDb;

$gBitSystem->verifyPackage( 'stock' );
$gBitSystem->verifyPermission( 'p_stock_view' );

include_once STOCK_PKG_INCLUDE_PATH.'movement_lookup_inc.php';

if( !$gContent->isValid() ) {
	$gBitSystem->display( 'error.tpl', 'Movement not found' );
	die;
}

$gBitSystem->setCanonicalLink( $gContent->getDisplayUrl() );

$gContent->loadXrefInfo();
$gBitSmarty->assign( 'gXrefInfo', $gContent->mXrefInfo );
$refType = $gContent->mInfo['ref_type'] ?? '';
$gBitSmarty->assign( 'isReqn',  $refType === 'REQN' );
$gBitSmarty->assign( 'isPbld',  $refType === 'PBLD' );
$gBitSmarty->assign( 'isBuild', in_array( $refType, [ 'REQN', 'PBLD' ] ) );
$gBitSmarty->assign( 'refType', $refType );

// One tab per real assembly on this movement — same per-assembly BOM breakdown as
// edit_movement.php, read-only here. See edit_movement.php for the entry_date matching
// rationale.
$assemblyTabs = [];
$asmGroup = $gContent->mXrefInfo->mGroups['assembly'] ?? null;
if( $asmGroup ) {
	foreach( $asmGroup->mXrefs as $asmXref ) {
		if( ( $asmXref['data'] ?? '' ) !== 'stockassembly' ) {
			continue;
		}
		$entryDate = $asmXref['entry_date'] ?? null;
		$bindVars  = [ $gContent->mContentId ];
		$entrySql  = 'AND x.`entry_date` ';
		if( $entryDate !== null ) {
			$entrySql   .= '= ?';
			$bindVars[]  = $entryDate;
		} else {
			$entrySql .= 'IS NULL';
		}
		$lines = $gBitDb->getAll(
			"SELECT x.`xref_id`, x.`item`, x.`xref`, x.`xkey`, lc.`title` AS component_title
			 FROM `".BIT_DB_PREFIX."liberty_xref` x
			 LEFT JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = x.`xref`
			 WHERE x.`content_id` = ? AND x.`item` IN ('SGL','PRT','SHT','VOL') $entrySql
			 ORDER BY x.`xorder`",
			$bindVars
		);
		$assemblyTabs[] = [
			'xref_id'    => (int)$asmXref['xref_id'],
			'content_id' => (int)$asmXref['xref'],
			'title'      => $asmXref['xkey_ext'],
			'kit_count'  => $asmXref['xkey'],
			'lines'      => $lines,
		];
	}
}
$gBitSmarty->assign( 'assemblyTabs', $assemblyTabs );

$gBitSystem->display( 'bitpackage:stock/view_movement.tpl', $gContent->getTitle() );
