<?php
/**
 * Stock assembly / BOM CSV import record loader.
 *
 * CSV column layout (0-based):
 *   0  assembly_title   Kit / assembly name
 *   1  component_title  Component name (must already exist as a stockcomponent content item)
 *   2  quantity_value   Numeric quantity in BOM  (optional, default 1)
 *   3  quantity_item  SGL / PCK / SHT / VOL   (optional, default SGL)
 *   4  item_position    Float position (e.g. 1.1, 1.2, 2.1) (optional)
 *
 * Rows are grouped by assembly_title. The assembly record is created once
 * the first time a new title is seen; subsequent rows with the same title
 * add further BOM lines to it.
 *
 * @package stock
 */

use Bitweaver\Stock\StockAssembly;
use Bitweaver\Liberty\LibertyContent;

/**
 * Process a batch of rows for a single assembly.
 * $pRows is an array of raw CSV row arrays, all sharing the same assembly_title.
 */
function StockAssemblyBatchLoad( string $assemblyTitle, array $pRows ): array {
	$result = [ 'loaded' => 0, 'skipped' => 0, 'errors' => [] ];

	// Create or find the assembly
	$assembly = new StockAssembly();
	$pHash = [
		'title'        => $assemblyTitle,
		'format_guid'  => 'plain',
		'rows_per_page'=> 5,
		'cols_per_page'=> 3,
	];
	if( !$assembly->store( $pHash ) ) {
		$result['errors'][] = "Failed to create assembly: $assemblyTitle";
		$result['skipped'] += count( $pRows );
		return $result;
	}

	foreach( $pRows as $rowNum => $data ) {
		$componentTitle = trim( $data[1] ?? '' );
		if( empty( $componentTitle ) ) {
			$result['skipped']++;
			$result['errors'][] = "Row $rowNum: empty component title.";
			continue;
		}

		// Look up component by title
		$componentContentId = _stockImportFindComponent( $componentTitle );
		if( !$componentContentId ) {
			$result['skipped']++;
			$result['errors'][] = "Row $rowNum: component not found — '$componentTitle'";
			continue;
		}

		$qtyValue = isset( $data[2] ) && is_numeric( trim($data[2]) ) ? (float)trim( $data[2] ) : 1.0;
		$qtySrc   = strtoupper( trim( $data[3] ?? 'SGL' ) );
		if( !in_array( $qtySrc, [ 'SGL', 'PCK', 'SHT', 'VOL' ] ) ) {
			$qtySrc = 'SGL';
		}
		$position = isset( $data[4] ) && is_numeric( trim($data[4]) ) ? (float)trim( $data[4] ) : null;

		// Insert BOM row directly — addItem() handles the assembly_content_id reference
		$assembly->addItem( $componentContentId, $position );

		// Update quantity_value and quantity_item on the map row just inserted
		global $gBitDb;
		$gBitDb->query(
			"UPDATE `".BIT_DB_PREFIX."stock_assembly_map`
			 SET `quantity_value` = ?, `quantity_item` = ?
			 WHERE `assembly_content_id` = ? AND `item_content_id` = ?",
			[ $qtyValue, $qtySrc, $assembly->mContentId, $componentContentId ]
		);

		$result['loaded']++;
	}

	return $result;
}

/** Finds a component's content_id by exact title match. */
function _stockImportFindComponent( string $title ): ?int {
	global $gBitDb;
	$contentId = $gBitDb->getOne(
		"SELECT lc.`content_id`
		 FROM `".BIT_DB_PREFIX."liberty_content` lc
		 WHERE lc.`content_type_guid` = '".STOCKCOMPONENT_CONTENT_TYPE_GUID."' AND lc.`title` = ?",
		[ $title ]
	);
	return $contentId ? (int)$contentId : null;
}
