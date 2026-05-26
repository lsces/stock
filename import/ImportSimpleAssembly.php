<?php
/**
 * Simplified assembly CSV importer — title, description, KLPR, KLURL only.
 *
 * CSV column layout (0-based, no header row):
 *   0  title        Assembly / kit name (used as the liberty content title)
 *   1  description  Plain-text description (stored as content body)
 *   2  KLPR         Kitlocker price (stored in xkey, max 32 chars)
 *   3  KLURL        Kitlocker datasheet URL (stored in xkey_ext, max 250 chars)
 *
 * Existing assemblies (matched by title) are skipped.
 * KLPR / KLURL xrefs are only inserted when the column is non-empty.
 *
 * @package stock
 */

use Bitweaver\Stock\StockAssembly;

function stockImportSimpleAssembly( array $data, int $rowNum ): array {
	global $gBitDb;

	$result = [ 'loaded' => 0, 'skipped' => 0, 'errors' => [] ];

	$title = trim( $data[0] ?? '' );
	if( empty( $title ) ) {
		$result['skipped']++;
		$result['errors'][] = "Row $rowNum: empty title, skipped.";
		return $result;
	}

	// Skip if already exists
	$exists = $gBitDb->getOne(
		"SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."stock_assembly` sa
		 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = sa.`content_id`
		 WHERE lc.`title` = ?",
		[ $title ]
	);
	if( $exists ) {
		$result['skipped']++;
		$result['errors'][] = "Row $rowNum: '$title' already exists, skipped.";
		return $result;
	}

	$description = trim( $data[1] ?? '' );
	$klpr        = trim( $data[2] ?? '' );
	$klurl       = trim( $data[3] ?? '' );

	$assembly = new StockAssembly();
	$pHash = [
		'title'       => $title,
		'edit'        => $description,
		'format_guid' => 'bithtml',
	];
	if( !$assembly->store( $pHash ) ) {
		$result['skipped']++;
		$result['errors'][] = "Row $rowNum: failed to create assembly '$title'.";
		return $result;
	}

	$contentId = $assembly->mContentId;

	if( !empty( $klpr ) ) {
		$xrefId = $gBitDb->GenID( 'liberty_xref_seq' );
		$gBitDb->associateInsert( BIT_DB_PREFIX.'liberty_xref', [
			'xref_id'          => $xrefId,
			'content_id'       => $contentId,
			'item'             => 'KLPR',
			'xorder'           => 0,
			'xkey'             => substr( $klpr, 0, 32 ),
			'last_update_date' => $gBitDb->NOW(),
		] );
	}

	if( !empty( $klurl ) ) {
		$xrefId = $gBitDb->GenID( 'liberty_xref_seq' );
		$gBitDb->associateInsert( BIT_DB_PREFIX.'liberty_xref', [
			'xref_id'          => $xrefId,
			'content_id'       => $contentId,
			'item'             => 'KLURL',
			'xorder'           => 0,
			'xkey_ext'         => substr( $klurl, 0, 250 ),
			'last_update_date' => $gBitDb->NOW(),
		] );
	}

	$result['loaded']++;
	return $result;
}
