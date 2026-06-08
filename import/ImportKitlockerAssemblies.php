<?php
/**
 * Kitlocker assemblies/components CSV importer.
 *
 * CSV column layout (0-based, header row skipped by loader):
 *   0  Title        MERG product code / designation (used as lc.title)
 *   1  KLID         Kitlocker numeric ID code (stored as KLID xref on both types)
 *   2  Description  Long description (stored as content body)
 *   3  KLSGL        Kitlocker single-unit stock count (stored as KLSGL xref on both types)
 *   4  KL3M         3-month sales count (stored as KL3M xref on both types)
 *   5  Group        Group number 1–99 → KLG01–KLG99 stgrp xref (both types)
 *   6  Type         'A' = StockAssembly, 'C' = StockComponent
 *
 * On reload, existing records are not re-created but their kitlocker xrefs are upserted,
 * so a fresh import run will tag components that were previously missing KLID etc.
 *
 * @package stock
 */

use Bitweaver\Stock\StockAssembly;
use Bitweaver\Stock\StockComponent;

function stockKitlockerXrefUpsert( int $contentId, string $item, string $value ): void {
	global $gBitDb;
	$existingId = $gBitDb->getOne(
		"SELECT `xref_id` FROM `".BIT_DB_PREFIX."liberty_xref`
		 WHERE `content_id` = ? AND `item` = ?",
		[ $contentId, $item ]
	);
	if( $existingId ) {
		$gBitDb->associateUpdate(
			BIT_DB_PREFIX.'liberty_xref',
			[ 'xkey' => substr( $value, 0, 32 ), 'last_update_date' => $gBitDb->NOW() ],
			[ 'xref_id' => $existingId ]
		);
	} else {
		$gBitDb->associateInsert( BIT_DB_PREFIX.'liberty_xref', [
			'xref_id'          => $gBitDb->GenID( 'liberty_xref_seq' ),
			'content_id'       => $contentId,
			'item'             => $item,
			'xkey'             => substr( $value, 0, 32 ),
			'xorder'           => 0,
			'last_update_date' => $gBitDb->NOW(),
		] );
	}
}

function stockExpungeKitlockerItemByTitle( string $title, string $type ): bool {
	global $gBitDb;

	$guid      = ( $type === 'A' ) ? 'stockassembly' : 'stockcomponent';
	$contentId = $gBitDb->getOne(
		"SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."liberty_content` lc
		 WHERE lc.`content_type_guid` = ? AND lc.`title` = ?",
		[ $guid, $title ]
	);
	if( !$contentId ) {
		return false;
	}

	$obj = ( $type === 'A' ) ? new StockAssembly( (int)$contentId ) : new StockComponent( (int)$contentId );
	$obj->expunge();
	return true;
}

function stockImportKitlockerItem( array $data, int $rowNum ): array {
	global $gBitDb;

	$result = [ 'loaded' => 0, 'skipped' => 0, 'errors' => [] ];

	$title = trim( $data[0] ?? '' );
	if( empty( $title ) ) {
		$result['skipped']++;
		return $result;
	}

	$type = strtoupper( trim( $data[6] ?? '' ) );
	if( !in_array( $type, [ 'A', 'C' ] ) ) {
		$result['skipped']++;
		$result['errors'][] = "Row $rowNum: '$title' — unknown type '$type', skipped.";
		return $result;
	}

	$guid  = ( $type === 'A' ) ? 'stockassembly' : 'stockcomponent';
	$klid  = trim( $data[1] ?? '' );
	$desc  = trim( $data[2] ?? '' );
	$klsgl = trim( $data[3] ?? '' );
	$kl3m  = trim( $data[4] ?? '' );
	$group = (int)( $data[5] ?? 0 );

	$contentId = (int)$gBitDb->getOne(
		"SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."liberty_content` lc
		 WHERE lc.`content_type_guid` = ? AND lc.`title` = ?",
		[ $guid, $title ]
	);

	if( !$contentId ) {
		$obj   = ( $type === 'A' ) ? new StockAssembly() : new StockComponent();
		$pHash = [
			'title'       => $title,
			'edit'        => $desc,
			'format_guid' => 'bithtml',
		];
		if( !$obj->store( $pHash ) ) {
			$result['skipped']++;
			$result['errors'][] = "Row $rowNum: failed to store '$title'.";
			return $result;
		}
		$contentId = $obj->mContentId;
	}

	// Group tag — both types
	if( $group >= 1 && $group <= 99 ) {
		stockKitlockerXrefUpsert( $contentId, sprintf( 'KLG%02d', $group ), '' );
	}

	// Kitlocker xrefs — same set for assemblies and components
	foreach( [ 'KLID' => $klid, 'KLSGL' => $klsgl, 'KL3M' => $kl3m ] as $item => $value ) {
		if( $value !== '' ) {
			stockKitlockerXrefUpsert( $contentId, $item, $value );
		}
	}

	$result['loaded']++;
	return $result;
}
