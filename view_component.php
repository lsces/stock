<?php
/**
 * @package stock
 * @subpackage functions
 */

/**
 * required setup
 */

require_once '../kernel/includes/setup_inc.php';
global $gBitSystem, $gDebug;

$gBitSystem->verifyPackage( 'stock' );

if( !empty( $_REQUEST['highlight'] ) ) {
	$gBitSmarty->assign( 'highlight', $_REQUEST['highlight'] );
}

include_once STOCK_PKG_INCLUDE_PATH.'component_lookup_inc.php';

if( $gContent && $gContent->isValid() ) {
	$gBitSystem->setCanonicalLink( $gContent->getDisplayUrl() );
}

if( is_object( $gGallery ) && $gGallery->isCommentable() ) {
	$commentsParentId = $gContent->mContentId;
	$comments_vars = [ 'stockcomponent' ];
	$comments_prefix_var='stockcomponent:';
	$comments_object_var='stockcomponent';
	$comments_return_url = $_SERVER['SCRIPT_NAME']."?content_id=".$gContent->mContentId;
	include_once LIBERTY_PKG_INCLUDE_PATH.'comments_inc.php';
}

$gContent->addHit();

$gContent->loadXrefInfo();
$gBitSmarty->assign( 'gXrefInfo', $gContent->mXrefInfo );

$isKitlocker = (bool)$gBitDb->getOne(
	"SELECT COUNT(*) FROM `".BIT_DB_PREFIX."liberty_xref` WHERE `content_id`=? AND `item`='KLID'",
	[ $gContent->mContentId ]
);
$gBitSmarty->assign( 'isKitlocker', $isKitlocker );

// Stock levels for this component, calculated from movement xrefs
if( $gContent->isValid() ) {
	$X    = BIT_DB_PREFIX;
	$rows = $gBitDb->query(
		"SELECT x.`item` AS qty_type,
				SUM( CASE WHEN EXISTS (
					SELECT 1 FROM `{$X}liberty_xref` r
					WHERE r.`content_id` = x.`content_id` AND r.`item` IN ('TRANS','ORDER')
				) THEN CAST(x.`xkey` AS DOUBLE PRECISION)
				  ELSE -CAST(x.`xkey` AS DOUBLE PRECISION) END ) AS stock_level
		 FROM `{$X}liberty_xref` x
		 INNER JOIN `{$X}liberty_content` mc ON mc.`content_id` = x.`content_id`
		 	AND mc.`content_type_guid` = 'stockmovement'
		 WHERE x.`xref` = ? AND x.`item` IN ('SGL','PRT','SHT','VOL')
		   AND x.`xkey` SIMILAR TO '[0-9]+(\.[0-9]+)?'
		 GROUP BY x.`item`",
		[ $gContent->mContentId ]
	);
	$stockLevels = [];
	foreach( $rows as $row ) {
		$stockLevels[$row['qty_type']] = (float)$row['stock_level'];
	}
	$ps = $gBitDb->getOne(
		"SELECT CAST(x.`xkey` AS DOUBLE PRECISION) FROM `".BIT_DB_PREFIX."liberty_xref` x
		 WHERE x.`content_id` = ? AND x.`item` = 'PRT'",
		[ $gContent->mContentId ]
	);
	$gBitSmarty->assign( 'componentStockLevels', $stockLevels );
	$gBitSmarty->assign( 'partSize', $ps ? (float)$ps : null );
}

require_once STOCK_PKG_INCLUDE_PATH.'display_stock_component_inc.php';
