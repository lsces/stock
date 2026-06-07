<?php
/**
 * Simplified component CSV importer — title, description, supplier, PN, price.
 *
 * CSV column layout (0-based, header row skipped by loader):
 *   0  title          Component name
 *   1  description    Plain-text description (stored as bithtml content body)
 *   2  supplier       Supplier contact title, case-insensitive (optional)
 *   3  supplier_pn    Supplier part number → xref #PN in xkey_ext (optional)
 *   4  supplier_price Supplier price       → xref #PR in xkey    (optional)
 *
 * Supplier name is matched against liberty_content.title for content_type_guid='contact'.
 * #SUP stores the contact content_id in the xref column; #PN and #PR share xorder=1
 * so they are grouped with the #SUP entry as one supplier set.
 *
 * Existing components (matched by title) are skipped unless cleared first.
 *
 * @package stock
 */

use Bitweaver\Stock\StockComponent;

// Cache supplier lookups — only 4 or so suppliers in the CSV
$_stockSupplierCache = [];

function stockImportFindSupplier( string $name ): ?int {
	global $gBitDb, $_stockSupplierCache;

	$key = strtolower( trim( $name ) );
	if( array_key_exists( $key, $_stockSupplierCache ) ) {
		return $_stockSupplierCache[$key];
	}

	$contentId = $gBitDb->getOne(
		"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_xref`
		 WHERE `item` = 'SCREF' AND UPPER( `xkey` ) = UPPER( ? )",
		[ trim( $name ) ]
	);

	$_stockSupplierCache[$key] = $contentId ? (int)$contentId : null;
	return $_stockSupplierCache[$key];
}

function stockExpungeComponentByTitle( string $title ): bool {
	global $gBitDb;

	$contentId = $gBitDb->getOne(
		"SELECT lc.`content_id`
		 FROM `".BIT_DB_PREFIX."liberty_content` lc
		 WHERE lc.`content_type_guid` = '".'stockcomponent'."' AND lc.`title` = ?",
		[ $title ]
	);
	if( !$contentId ) {
		return false;
	}

	$component = new StockComponent( (int)$contentId );
	$component->expunge();
	return true;
}

function stockImportSimpleComponent( array $data, int $rowNum ): array {
	global $gBitDb;

	$result = [ 'loaded' => 0, 'skipped' => 0, 'errors' => [] ];

	$title = trim( $data[0] ?? '' );
	if( empty( $title ) ) {
		$result['skipped']++;
		$result['errors'][] = "Row $rowNum: empty title, skipped.";
		return $result;
	}

	$exists = $gBitDb->getOne(
		"SELECT lc.`content_id`
		 FROM `".BIT_DB_PREFIX."liberty_content` lc
		 WHERE lc.`content_type_guid` = '".'stockcomponent'."' AND lc.`title` = ?",
		[ $title ]
	);
	if( $exists ) {
		$result['skipped']++;
		$result['errors'][] = "Row $rowNum: '$title' already exists, skipped.";
		return $result;
	}

	$description   = trim( $data[1] ?? '' );
	$supplierName  = trim( $data[2] ?? '' );
	$supplierPn    = trim( $data[3] ?? '' );
	$supplierPrice = trim( $data[4] ?? '' );
	$supplierUrl   = trim( $data[5] ?? '' );

	$component = new StockComponent();
	$pHash = [
		'title'       => $title,
		'edit'        => $description,
		'format_guid' => 'bithtml',
	];
	if( !$component->store( $pHash ) ) {
		$result['skipped']++;
		$result['errors'][] = "Row $rowNum: failed to create component '$title'.";
		return $result;
	}

	$contentId = $component->mContentId;

	if( !empty( $supplierName ) ) {
		$supplierContentId = stockImportFindSupplier( $supplierName );
		if( !$supplierContentId ) {
			$result['errors'][] = "Row $rowNum: '$title' — supplier '$supplierName' not found in contacts, xrefs skipped.";
		} else {
			$xrefId = $gBitDb->GenID( 'liberty_xref_seq' );
			$gBitDb->associateInsert( BIT_DB_PREFIX.'liberty_xref', [
				'xref_id'          => $xrefId,
				'content_id'       => $contentId,
				'item'             => '#SUP',
				'xorder'           => 1,
				'xref'             => $supplierContentId,
				'xkey'             => substr( $supplierPn,    0, 32  ),
				'xkey_ext'         => substr( $supplierPrice, 0, 250 ),
				'data'             => $supplierUrl ?: null,
				'last_update_date' => $gBitDb->NOW(),
			] );
		}
	}

	$result['loaded']++;
	return $result;
}
