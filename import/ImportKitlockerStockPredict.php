<?php
/**
 * Kitlocker "Stock predict" HTML importer.
 *
 * Parses the raw HTML export from the live site (a series of <table class="generalTable">
 * blocks, one per product section) rather than requiring a manual CSV conversion — the
 * markup is consistent enough (Code, Name, Current Stock, No. sold in 3 months, weeks
 * remaining) to read directly with DOMDocument.
 *
 * Matched against existing kitlocker items by KLID (item code, e.g. "36A") — not KLPR,
 * which is unrelated (Kitlocker Price). Only KLSGL (Current Stock) and KL3M (No. sold in
 * 3 months) are upserted.
 *
 * Codes with no matching KLID are reported rather than silently created, since the HTML
 * export has no assembly/component distinction — the caller must say which via an explicit
 * code => 'A'|'C' map (see stockImportKitlockerStockPredictRow's $pCreateType param).
 *
 * @package stock
 */

require_once __DIR__.'/ImportKitlockerAssemblies.php';

/**
 * Parse the Stock predict HTML export into [ code, klsgl, kl3m ] rows.
 */
function stockParseKitlockerStockPredictHtml( string $html ): array {
	$rows = [];

	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	$dom->loadHTML( $html );
	libxml_clear_errors();

	$xpath  = new DOMXPath( $dom );
	$tables = $xpath->query( '//table[@class="generalTable"]' );

	foreach( $tables as $table ) {
		$trs = $xpath->query( './/tr', $table );
		foreach( $trs as $i => $tr ) {
			if( $i === 0 ) {
				continue; // header row
			}
			$tds = $xpath->query( './/td', $tr );
			if( $tds->length < 4 ) {
				continue;
			}
			$code = trim( $tds->item( 0 )->textContent );
			if( $code === '' ) {
				continue;
			}
			$rows[] = [
				'code'  => $code,
				'name'  => trim( $tds->item( 1 )->textContent ),
				'klsgl' => trim( $tds->item( 2 )->textContent ),
				'kl3m'  => trim( $tds->item( 3 )->textContent ),
			];
		}
	}

	return $rows;
}

/**
 * Match one parsed row to an existing kitlocker item by KLID and upsert KLSGL/KL3M.
 *
 * If no KLID matches and $pCreateType is 'A' or 'C', a new StockAssembly/StockComponent is
 * created (title = code, body = name) via stockImportKitlockerItem(), same as the original
 * catalogue importer. Without a type, unmatched rows are left alone and reported as such —
 * the export gives no way to tell an assembly from a component.
 *
 * @return array{matched:bool,created:bool,content_id:?int}
 */
function stockImportKitlockerStockPredictRow( array $row, ?string $pCreateType = null ): array {
	global $gBitDb;

	$contentId = $gBitDb->getOne(
		"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_xref` WHERE `item` = 'KLID' AND `xkey` = ?",
		[ $row['code'] ]
	);

	if( !$contentId ) {
		if( !in_array( $pCreateType, [ 'A', 'C' ], true ) ) {
			return [ 'matched' => false, 'created' => false, 'content_id' => null ];
		}
		$result = stockImportKitlockerItem(
			[ $row['code'], $row['code'], $row['name'], $row['klsgl'], $row['kl3m'], 0, $pCreateType ],
			0
		);
		if( !$result['loaded'] ) {
			return [ 'matched' => false, 'created' => false, 'content_id' => null ];
		}
		$contentId = $gBitDb->getOne(
			"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_xref` WHERE `item` = 'KLID' AND `xkey` = ?",
			[ $row['code'] ]
		);
		return [ 'matched' => true, 'created' => true, 'content_id' => (int)$contentId ];
	}

	$contentId = (int)$contentId;
	stockKitlockerXrefUpsert( $contentId, 'KLSGL', $row['klsgl'] );
	stockKitlockerXrefUpsert( $contentId, 'KL3M',  $row['kl3m'] );

	return [ 'matched' => true, 'created' => false, 'content_id' => $contentId ];
}
