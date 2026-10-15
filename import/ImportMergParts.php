<?php
/**
 * MERG "Part Sources" report importer (Report_Parts_Sources.xls, saved as a flat CSV).
 *
 * The report is block-shaped, not one line per record: a part line (long name, per-unit
 * cost, annual usage), a "Supplier,Part No,Description,Cost,Qty,Notes" header line, then one
 * line per supplier of that part. Cost is the pack price as listed, Qty the pack size.
 *
 * Raw import, merged by hand later (the same approach as the earlier BOM imports):
 *  - a part with a supplier line already on a library component (same supplier contact and
 *    part number) is that component - its missing supplier lines are added, nothing else
 *    changes. A part whose lines hit more than one component is reported and skipped.
 *  - a supplier part number held by more than one library component (e.g. the generic AliExpress
 *    listing on both TBPL-10A-3.5-4 and TBSE-10A-3.5-4) is not used for matching, and a line whose
 *    number belongs to a different component is not copied onto the matched one.
 *  - STOCK_MERG_PARTS_OVERRIDES pins a long name to a component where the numbers can't decide.
 *  - a long name ending in a bracketed MERG number ("CANFLIM IO E Rev C PCB [911]") is the
 *    component titled (or Kitlocker-ID'd) with that number: kits and PCBs already in the library.
 *    Those parts' supplier lines are production batches (same part number, several prices):
 *    all of a part's batch lines are added when the component has none for that supplier/number.
 *  - otherwise a stub component is created, the long name as both title and description,
 *    with PCK set from the pack size.
 *  - supplier lines are written as #SUP rows (xref=supplier contact, xkey=part number,
 *    xkey_ext=price as listed, data=the Notes column, e.g. "100+", plus the pack/batch size).
 *  - only suppliers in STOCK_MERG_PARTS_SUPPLIERS are used; other lines are counted and
 *    skipped (just "Multi CB (DON'T USE)"). Listed suppliers with no contact yet are created name-only on apply.
 * Not stored: the per-unit cost and annual usage on the part line.
 *
 * @package stock
 */

namespace Bitweaver\Stock;

use Bitweaver\Contact\ContactBusiness;
use Bitweaver\Liberty\LibertyContent;

/** report supplier name => contact title. Names not listed here are skipped (see class docblock). */
const STOCK_MERG_PARTS_SUPPLIERS = [
	'Rapid Electronics' => 'Rapid Online',
	'Farnell'           => 'Farnell',
	'Mouser'            => 'Mouser Electronics',
	'AliExpress'        => 'Ali Express',
	'JLCPCB'            => 'JiaLiChuang (HongKong) Co., Limited',
	'CPC'               => 'CPC',
	'JPR Electronics'   => 'JPR Electronics Ltd',
	'Microchip'         => 'Microchip',
	'Digikey'           => 'Digikey',
	'Merefields'        => 'Merefields',
	'Tecbridge'         => 'Tecbridge',
	'RS Online'         => 'RS Online',
	'Anglia'            => 'Anglia',
	'Shed Works'        => 'Shed Works',
	'Amazon'            => 'Amazon',
	'Ebay'              => 'Ebay',
	'Switch Electronics'    => 'Switch Electronics',
	'Hobby Comp'            => 'Hobby Comp',
	'Precision Technical S' => 'Precision Technical S', // name is cut short in the report itself
	'Filamentive Limited'   => 'Filamentive Limited',
	'EMA Models'            => 'EMA Models',
	'customark'             => 'customark',
	'ChrisInDen Models'     => 'ChrisInDen Models',
	'Polybags'              => 'Polybags',
	'Kays'                  => 'Kays',
	'Westfield'             => 'Westfield',
	'First4Magnets'         => 'First4Magnets',
	'Enrgtech'              => 'Enrgtech',
	'Coilcraft'             => 'Coilcraft',
	'ukpackaging'           => 'ukpackaging',
	'Simon West'            => 'Simon West',
	'Arrow'                 => 'Arrow',
	'Alibaba'               => 'Alibaba',
	'SLEC Ltd'              => 'SLEC Ltd',
	'Toolstation'           => 'Toolstation',
	'Fraser Smith'          => 'Fraser Smith',
	'Hobbyking'             => 'Hobbyking',
	// not listed: 'Multi CB (DON'T USE)' - marked as not to be used in the report itself
];

/**
 * Suppliers whose "part number" is a shop page that covers several variants (AliExpress: plug,
 * header, vertical and side entry on one listing). Never used for matching, and the same number
 * on several components is expected, so it never counts as held by another component.
 */
const STOCK_MERG_PARTS_PAGE_SUPPLIERS = [ 'AliExpress', 'Alibaba' ];

/**
 * Hand-decided matches, long name => component title, for parts whose supplier numbers point at
 * more than one component.
 */
const STOCK_MERG_PARTS_OVERRIDES = [
	'Terminal Block 2 Way 16A 5mm Camden Type' => 'TBLP-16A-5-2',
];

/**
 * Parse the flat CSV into parts, each with its supplier lines.
 *
 * @return array<int,array{title:string,unit_cost:string,usage:string,lines:array<int,array>}>
 */
function stockMergPartsParse( string $pFile ): array {
	$parts = [];
	$fh = fopen( $pFile, 'r' );
	if( !$fh ) {
		return $parts;
	}
	while( ( $r = fgetcsv( $fh, 0, ',', '"', '' ) ) !== false ) {
		$r = array_pad( $r, 6, '' );
		if( $r[0] === 'Supplier' && $r[1] === 'Part No' ) {
			continue; // block header
		}
		if( trim( $r[0] ) === '' ) {
			continue;
		}
		if( $r[1] === '' && $r[2] === '' ) {
			if( preg_match( '/^MERG Part Sources/', $r[0] ) ) {
				continue; // report title line, present in the .xls but not the flat copy
			}
			$parts[] = [ 'title' => trim( $r[0] ), 'unit_cost' => ltrim( trim( $r[3] ), '£' ), 'usage' => trim( $r[4] ), 'lines' => [] ];
			continue;
		}
		if( !$parts ) {
			continue;
		}
		$parts[count( $parts ) - 1]['lines'][] = [
			'supplier'    => trim( $r[0] ),
			'pn'          => trim( $r[1] ),
			'description' => trim( $r[2] ),
			'price'       => ltrim( trim( $r[3] ), '£' ),
			'pack'        => trim( $r[4] ),
			'note'        => trim( $r[5] ),
		];
	}
	fclose( $fh );
	return $parts;
}

/** contact content_id for a supplier contact title (businesses only, case-insensitive), or null */
function stockMergPartsFindContact( string $pTitle ): ?int {
	global $gBitDb;
	$id = $gBitDb->getOne(
		"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_content`
		 WHERE `content_type_guid` = 'contactbusiness' AND UPPER(`title`) = ?",
		[ strtoupper( $pTitle ) ]
	);
	return $id ? (int)$id : null;
}

/** name-only supplier contact, typed B03 like the existing stock suppliers; details are filled in by hand */
function stockMergPartsCreateContact( string $pTitle ): ?int {
	$contact = new ContactBusiness();
	$hash = [ 'organisation' => $pTitle, 'xkey' => '', 'contact_types' => [ 'B03' ], 'fContactTypesSubmitted' => 1 ];
	return $contact->store( $hash ) ? (int)$contact->mContentId : null;
}

/** comparable price: the number as a fixed string, so "0.60" equals "0.6"; non-numeric text is kept as is */
function stockMergPartsPrice( string $pPrice ): string {
	$p = trim( str_replace( '£', '', $pPrice ) );
	return is_numeric( $p ) ? number_format( (float)$p, 4, '.', '' ) : $p;
}

/** the library component for a bracketed MERG number: title equal to it, or its Kitlocker ID (KLID) */
function stockMergPartsFindByCode( string $pCode ): ?int {
	global $gBitDb;
	$id = $gBitDb->getOne(
		"SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."liberty_content` lc
		  WHERE lc.`content_type_guid` = 'stockcomponent'
		    AND ( UPPER(lc.`title`) = ? OR EXISTS ( SELECT 1 FROM `".BIT_DB_PREFIX."liberty_xref` k
		          WHERE k.`content_id` = lc.`content_id` AND k.`item` = 'KLID' AND UPPER(k.`xkey`) = ? ) )
		  ORDER BY lc.`content_id`",
		[ strtoupper( $pCode ), strtoupper( $pCode ) ]
	);
	return $id ? (int)$id : null;
}

/**
 * Run (or preview) the import.
 *
 * @return array{rows:array,summary:array,contacts:array,skippedSuppliers:array,errors:array}
 */
function stockMergPartsImport( string $pFile, bool $pApply ): array {
	global $gBitDb;

	$out = [ 'rows' => [], 'summary' => [ 'stub' => 0, 'attach' => 0, 'lines' => 0, 'ambiguous' => 0, 'nolines' => 0, 'unchanged' => 0, 'failed' => 0 ],
			 'contacts' => [], 'skippedSuppliers' => [], 'errors' => [] ];

	// Resolve supplier contacts once (creating missing ones on apply).
	$contactIds = [];
	foreach( STOCK_MERG_PARTS_SUPPLIERS as $name => $title ) {
		$id = stockMergPartsFindContact( $title );
		if( !$id ) {
			if( $pApply ) {
				$id = stockMergPartsCreateContact( $title );
				$out['contacts'][$title] = $id ? 'created' : 'FAILED';
			} else {
				$out['contacts'][$title] = 'would create';
			}
		}
		$contactIds[$name] = $id; // null in a dry run for contacts still to be created
	}

	// Existing supplier lines: supplier content_id + part number => component content_ids.
	$have = [];
	$rs = $gBitDb->getAll(
		"SELECT x.`content_id`, x.`xref`, x.`xkey`, x.`xkey_ext`
		   FROM `".BIT_DB_PREFIX."liberty_xref` x
		   JOIN `".BIT_DB_PREFIX."liberty_content` c ON ( c.`content_id` = x.`content_id` AND c.`content_type_guid` = 'stockcomponent' )
		  WHERE x.`item` = '#SUP'"
	);
	foreach( $rs as $row ) {
		$have[(int)$row['xref'].'|'.strtolower( trim( (string)$row['xkey'] ) )][(int)$row['content_id']] = true;
	}

	foreach( stockMergPartsParse( $pFile ) as $part ) {
		$usable = [];
		foreach( $part['lines'] as $line ) {
			if( !array_key_exists( $line['supplier'], STOCK_MERG_PARTS_SUPPLIERS ) ) {
				$out['skippedSuppliers'][$line['supplier']] = ( $out['skippedSuppliers'][$line['supplier']] ?? 0 ) + 1;
			} elseif( $line['pn'] === '' ) {
				$out['errors'][] = $part['title'].': '.$line['supplier'].' line has no part number, skipped.';
			} else {
				$usable[] = $line;
			}
		}

		// Which library component(s) already carry one of these lines?
		$matched = [];
		if( preg_match( '/\[([0-9A-Za-z]+)\]\s*$/', $part['title'], $cm ) && ( $codeId = stockMergPartsFindByCode( $cm[1] ) ) ) {
			$matched[$codeId] = true;
		}
		foreach( $usable as $line ) {
			$sid = $contactIds[$line['supplier']];
			$holders = $sid && !in_array( $line['supplier'], STOCK_MERG_PARTS_PAGE_SUPPLIERS, true ) ? ( $have[$sid.'|'.strtolower( $line['pn'] )] ?? [] ) : [];
			if( count( $holders ) === 1 ) { // a number held by several components says nothing
				$matched[(int)array_key_first( $holders )] = true;
			}
		}
		if( isset( STOCK_MERG_PARTS_OVERRIDES[$part['title']] ) ) {
			$pinned = stockMergPartsFindByCode( STOCK_MERG_PARTS_OVERRIDES[$part['title']] );
			$matched = $pinned ? [ $pinned => true ] : $matched;
		}
		if( !$matched ) {
			$byTitle = $gBitDb->getOne(
				"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_content` WHERE `content_type_guid` = 'stockcomponent' AND `title` = ?",
				[ $part['title'] ]
			);
			if( $byTitle ) {
				$matched[(int)$byTitle] = true; // an earlier run of this import
			}
		}

		$row = [ 'part' => $part['title'], 'status' => '', 'target' => '', 'content_id' => null, 'added' => 0, 'held' => 0, 'lines' => count( $usable ) ];

		if( count( $matched ) > 1 ) {
			$titles = [];
			foreach( array_keys( $matched ) as $cid ) {
				$titles[] = $gBitDb->getOne( "SELECT `title` FROM `".BIT_DB_PREFIX."liberty_content` WHERE `content_id` = ?", [ $cid ] );
			}
			$row['status'] = 'ambiguous';
			$row['target'] = implode( ' / ', $titles );
			$out['summary']['ambiguous']++;
			$out['rows'][] = $row;
			continue;
		}

		$component = null;
		if( $matched ) {
			$cid = (int)array_key_first( $matched );
			$row['content_id'] = $cid;
			$row['target'] = (string)$gBitDb->getOne( "SELECT `title` FROM `".BIT_DB_PREFIX."liberty_content` WHERE `content_id` = ?", [ $cid ] );
			$row['status'] = 'attach';
			if( $pApply ) {
				$component = new StockComponent( $cid );
				$component->load();
			}
		} else {
			$row['status'] = 'stub';
			if( $pApply ) {
				$component = new StockComponent();
				$pHash = [ 'title' => $part['title'], 'edit' => $part['title'], 'format_guid' => 'bithtml' ];
				if( !$component->store( $pHash ) ) {
					$row['status'] = 'FAILED';
					$out['summary']['failed']++;
					$out['errors'][] = $part['title'].': '.implode( '; ', (array)$component->mErrors );
					$out['rows'][] = $row;
					continue;
				}
				$row['content_id'] = (int)$component->mContentId;
				$pack = null;
				foreach( $usable as $line ) {
					if( ctype_digit( $line['pack'] ) && (int)$line['pack'] > 1 ) {
						$pack = (int)$line['pack'];
						break;
					}
				}
				if( $pack ) {
					LibertyContent::upsertXrefByContentId( $row['content_id'], 'PCK', [ 'xkey' => (string)$pack, 'xorder' => 0 ] );
				}
			}
		}

		// Add the supplier lines the component doesn't have yet.
		$xorder = $row['content_id'] ? (int)$gBitDb->getOne(
			"SELECT MAX(`xorder`) FROM `".BIT_DB_PREFIX."liberty_xref` WHERE `content_id` = ? AND `item` = '#SUP'", [ $row['content_id'] ] ) : 0;
		$seen = []; // lines of this part already counted, so a repeated line in the sheet isn't added twice
		foreach( $usable as $line ) {
			$sid = $contactIds[$line['supplier']];
			$price = stockMergPartsPrice( $line['price'] );
			$lineKey = $line['supplier'].'|'.strtolower( $line['pn'] ).'|'.$price;
			if( isset( $seen[$lineKey] ) ) {
				continue;
			}
			$seen[$lineKey] = true;
			$holders = $sid ? ( $have[$sid.'|'.strtolower( $line['pn'] )] ?? [] ) : [];
			if( $row['content_id'] && !empty( $holders[$row['content_id']] ) ) {
				continue; // library already has this supplier part number, whatever its price
			}
			if( $row['status'] === 'attach' && $holders && !in_array( $line['supplier'], STOCK_MERG_PARTS_PAGE_SUPPLIERS, true ) ) {
				$row['held']++; // that number is on a different component
				continue;
			}
			$row['added']++;
			if( $pApply && $component && $sid ) {
				$xorder++;
				$xrefHash = [
					'content_id' => $row['content_id'],
					'item'       => '#SUP',
					'xref'       => $sid,
					'xkey'       => substr( $line['pn'], 0, 32 ),
					'xkey_ext'   => substr( $line['price'], 0, 250 ),
					'edit'       => implode( ', ', array_filter( [ $line['note'], $line['pack'] !== '' ? 'qty '.$line['pack'] : '' ] ) ),
					'xorder'     => $xorder,
					'fAddXref'   => 1,
				];
				$component->storeXref( $xrefHash );
			}
		}

		if( $row['status'] === 'attach' && $row['added'] === 0 ) {
			$row['status'] = 'unchanged';
			$out['summary']['unchanged']++;
		} elseif( $row['status'] === 'attach' ) {
			$out['summary']['attach']++;
		} else {
			$out['summary']['stub']++;
		}
		if( !$usable ) {
			$out['summary']['nolines']++;
		}
		$out['summary']['lines'] += $row['added'];
		$out['rows'][] = $row;
	}

	arsort( $out['skippedSuppliers'] );
	return $out;
}
