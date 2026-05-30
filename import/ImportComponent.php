<?php
/**
 * Stock component CSV import record loader.
 *
 * CSV column layout (0-based):
 *   0  title           Component name / description
 *   1  data            Long description (optional)
 *   2  part_number     Supplier part number → #SUP xkey  (optional)
 *   3  quantity_value  Numeric stock quantity             (optional, default 1)
 *   4  quantity_item   SGL / PCK / SHT / VOL             (optional, default SGL)
 *
 * @package stock
 */

use Bitweaver\Stock\StockComponent;
use Bitweaver\Liberty\LibertyContent;

function StockComponentRecordLoad( array $data ): bool {
	$title = trim( $data[0] ?? '' );
	if( empty( $title ) ) {
		return false;
	}

	$component = new StockComponent();

	$pHash = [
		'title'      => $title,
		'edit'       => trim( $data[1] ?? '' ),
		'format_guid' => 'plain',
	];

	if( !$component->store( $pHash ) ) {
		return false;
	}

	// Supplier xref (#SUP) — part number in xkey; xref (contact_id) left empty for manual linking
	$partNumber = trim( $data[2] ?? '' );
	if( $partNumber !== '' ) {
		$xrefHash = [
			'content_id' => $component->mContentId,
			'item'       => '#SUP',
			'xkey'       => $partNumber,
			'fAddXref'   => 1,
		];
		$component->storeXref( $xrefHash );
	}

	// Quantity xref (SGL/PCK/SHT/VOL)
	$qtyValue = isset( $data[3] ) && is_numeric( trim( $data[3] ) ) ? (float)trim( $data[3] ) : null;
	$qtySrc   = strtoupper( trim( $data[4] ?? 'SGL' ) );
	if( !in_array( $qtySrc, [ 'SGL', 'PCK', 'SHT', 'VOL' ] ) ) {
		$qtySrc = 'SGL';
	}
	if( $qtyValue !== null ) {
		$xrefHash = [
			'content_id' => $component->mContentId,
			'item'       => $qtySrc,
			'xkey'       => $qtyValue,
			'fAddXref'   => 1,
		];
		$component->storeXref( $xrefHash );
	}

	return true;
}
