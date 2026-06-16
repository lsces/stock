<?php
/**
 * A stock movement — an inbound order/transfer or outbound requisition.
 *
 * Stored as a pure liberty_content record (content_type_guid='stockmovement').
 * Direction is inferred from the reference xref item: REQN = outbound,
 * TRANS/ORDER = inbound. Status (open vs received) is stored in lc.event_time:
 * 0 = open, positive Unix timestamp = received.
 *
 * Component lines live in liberty_xref (x_group='quantity', items SGL/PRT/SHT/VOL).
 * The reference xref (x_group='reference') carries the from/ref/date/contact data.
 *
 * @package stock
 */
namespace Bitweaver\Stock;

use Bitweaver\BitBase;
use Bitweaver\Liberty\LibertyContent;

defined( 'STOCKMOVEMENT_CONTENT_TYPE_GUID' ) || define( 'STOCKMOVEMENT_CONTENT_TYPE_GUID', 'stockmovement' );

#[\AllowDynamicProperties]
class StockMovement extends LibertyContent {
	protected $mXrefTypeKey = 'stockmovement_types';

	/**
	 * @param int|null $pMovementId  Legacy param — use $pContentId instead.
	 * @param int|null $pContentId   liberty_content.content_id to load.
	 */
	public function __construct( $pMovementId = null, $pContentId = null ) {
		parent::__construct();
		$this->mContentTypeGuid = STOCKMOVEMENT_CONTENT_TYPE_GUID;
		$pContentId = $pContentId ?? $pMovementId;
		if( $this->verifyId( $pContentId ) ) {
			$this->mContentId = (int)$pContentId;
		}
		$this->registerContentType(
			STOCKMOVEMENT_CONTENT_TYPE_GUID, [
				'content_type_guid'   => STOCKMOVEMENT_CONTENT_TYPE_GUID,
				'content_name'        => 'Movement',
				'content_name_plural' => 'Movements',
				'handler_class'       => 'StockMovement',
				'handler_package'     => 'stock',
				'handler_file'        => 'StockMovement.php',
				'maintainer_url'      => 'https://www.bitweaver.org',
		] );
		$this->mViewContentPerm   = 'p_stock_view';
		$this->mCreateContentPerm = 'p_stock_create';
		$this->mUpdateContentPerm = 'p_stock_update';
		$this->mAdminContentPerm  = 'p_stock_admin';
	}

	/** @return bool TRUE when mContentId is a valid positive integer. */
	public function isValid(): bool {
		return (bool)$this->verifyId( $this->mContentId );
	}

	/**
	 * @param  array $pLookupHash      Must contain 'content_id'.
	 * @param  bool  $pLoadFromCache   Whether to use LibertyContent's object cache.
	 * @return static|null             Loaded object, or null if not found.
	 */
	public static function lookup( $pLookupHash, $pLoadFromCache = true ) {
		$lookupContentId = null;
		if( !empty( $pLookupHash['content_id'] ) && is_numeric( $pLookupHash['content_id'] ) ) {
			$lookupContentId = (int)$pLookupHash['content_id'];
		}
		if( static::verifyId( $lookupContentId ) ) {
			return parent::getLibertyObject( $lookupContentId, STOCKMOVEMENT_CONTENT_TYPE_GUID, $pLoadFromCache );
		}
		return null;
	}

	/**
	 * Load movement record into $this->mInfo, including reference xref scalars
	 * (ref_type, ref_key, ref_start_date, ref_contact_id, ref_contact_name, ref_from_data).
	 *
	 * @param  int|null   $pContentId    Override mContentId for this load.
	 * @param  array|null $pPluginParams Passed through to getServicesSql().
	 * @return bool        TRUE on success, FALSE if not found or mContentId invalid.
	 */
	public function load( $pContentId = null, $pPluginParams = null ) {
		if( $pContentId ) $this->mContentId = (int)$pContentId;
		if( !$this->verifyId( $this->mContentId ) ) return false;

		$whereSql = " WHERE lc.`content_id` = ? AND lc.`content_type_guid` = '".STOCKMOVEMENT_CONTENT_TYPE_GUID."'";
		$bindVars = [ $this->mContentId ];
		$selectSql = $joinSql = '';
		$this->getServicesSql( 'content_load_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

		$X = BIT_DB_PREFIX;
		$sql = "SELECT lc.* $selectSql
					, uue.`login` AS `modifier_user`, uue.`real_name` AS `modifier_real_name`
					, uuc.`login` AS `creator_user`,  uuc.`real_name` AS `creator_real_name`
					, (SELECT FIRST 1 x.`start_date` FROM `{$X}liberty_xref` x
					   WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER','PBLD')
					   ORDER BY x.`xorder`) AS ref_start_date
					, (SELECT FIRST 1 xi.`cross_ref_title` FROM `{$X}liberty_xref` x
					   JOIN `{$X}liberty_xref_item` xi ON xi.`item` = x.`item` AND xi.`content_type_guid` = 'stockmovement'
					   WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER','PBLD')
					   ORDER BY x.`xorder`) AS ref_type_title
					, (SELECT FIRST 1 x.`xref` FROM `{$X}liberty_xref` x
					   WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER','PBLD')
					   ORDER BY x.`xorder`) AS ref_contact_id
					, (SELECT FIRST 1 lc2.`title` FROM `{$X}liberty_xref` x
					   JOIN `{$X}liberty_content` lc2 ON lc2.`content_id` = x.`xref`
					   WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER','PBLD')
					   ORDER BY x.`xorder`) AS ref_contact_name
					, (SELECT FIRST 1 x.`item` FROM `{$X}liberty_xref` x
					   WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER','PBLD')
					   ORDER BY x.`xorder`) AS ref_type
					, (SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x
					   WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER','PBLD')
					   ORDER BY x.`xorder`) AS ref_key
					, (SELECT FIRST 1 x.`data` FROM `{$X}liberty_xref` x
					   WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER','PBLD')
					   ORDER BY x.`xorder`) AS ref_from_data
				FROM `".BIT_DB_PREFIX."liberty_content` lc
					LEFT JOIN `".BIT_DB_PREFIX."users_users` uue ON uue.`user_id` = lc.`modifier_user_id`
					LEFT JOIN `".BIT_DB_PREFIX."users_users` uuc ON uuc.`user_id` = lc.`user_id`
					$joinSql
				$whereSql";

		if( $rs = $this->mDb->getRow( $sql, $bindVars ) ) {
			$this->mInfo = $rs;
			$this->mContentId       = $rs['content_id'];
			$this->mContentTypeGuid = $rs['content_type_guid'];
			$this->mInfo['creator'] = $rs['creator_real_name'] ?? $rs['creator_user'];
			$this->mInfo['editor']  = $rs['modifier_real_name'] ?? $rs['modifier_user'];
			LibertyContent::load();
		}
		return !empty( $this->mInfo );
	}

	/**
	 * Persist movement data inside a transaction via LibertyContent::store().
	 * Requires a non-empty title (the movement reference).
	 *
	 * @param  array $pParamHash  Data to persist; modified in place.
	 * @return bool
	 */
	public function store( array &$pParamHash ): bool {
		$pParamHash['content_type_guid'] = STOCKMOVEMENT_CONTENT_TYPE_GUID;
		if( empty( $pParamHash['title'] ) ) {
			$this->mErrors['title'] = 'A movement reference is required.';
		}
		if( count( $this->mErrors ) == 0 ) {
			$this->StartTrans();
			if( LibertyContent::store( $pParamHash ) ) {
				$this->mContentId          = $pParamHash['content_id'];
				$this->mInfo['content_id'] = $this->mContentId;
				$this->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
			}
		}
		return count( $this->mErrors ) == 0;
	}

	/**
	 * Delete this movement. LibertyContent::expunge() handles xref cleanup.
	 *
	 * @return bool Always TRUE (errors recorded in $this->mErrors).
	 */
	public function expunge(): bool {
		if( $this->isValid() ) {
			$this->StartTrans();
			if( LibertyContent::expunge() ) {
				$this->CompleteTrans();
				$this->mContentId = null;
			} else {
				$this->mDb->RollbackTrans();
			}
		}
		return true;
	}

	/**
	 * Enrich the quantity group with part_size/part_size_ext from the PRT xref.
	 * linked_title and linked_data come from the lc_linked JOIN in loadContent().
	 */
	public function loadXrefInfo(): void {
		parent::loadXrefInfo();
		if( empty( $this->mXrefInfo ) ) return;
		$bomGroup = $this->mXrefInfo->mGroups['quantity'] ?? null;
		if( !$bomGroup || empty( $bomGroup->mXrefs ) ) return;
		$componentIds = array_values( array_unique( array_filter(
			array_map( fn($r) => $r['xref'], $bomGroup->mXrefs )
		) ) );
		if( !$componentIds ) return;
		$components = $this->mDb->getAssoc(
			"SELECT lc.`content_id`, pck.`xkey` AS `part_size`, pck.`xkey_ext` AS `part_size_ext`
			 FROM `".BIT_DB_PREFIX."liberty_content` lc
			 LEFT JOIN `".BIT_DB_PREFIX."liberty_xref` pck ON pck.`content_id` = lc.`content_id` AND pck.`item` = 'PRT'
			 WHERE lc.`content_id` IN (".implode( ',', array_fill( 0, count( $componentIds ), '?' ) ).")",
			$componentIds
		);
		foreach( $bomGroup->mXrefs as &$row ) {
			if( !empty( $row['xref'] ) && isset( $components[$row['xref']] ) ) {
				$row['part_size']     = $components[$row['xref']]['part_size'];
				$row['part_size_ext'] = $components[$row['xref']]['part_size_ext'];
			}
		}
		unset( $row );
	}

	/**
	 * Infer movement direction from the reference xref item code.
	 *
	 * @return string  'O' (outbound/REQN) or 'I' (inbound/TRANS or ORDER).
	 */
	public function getDirection(): string {
		$refType = $this->mInfo['ref_type'] ?? null;
		if( in_array( $refType, [ 'REQN', 'PBLD' ] ) )     return 'O';
		if( in_array( $refType, [ 'TRANS', 'ORDER' ] ) )   return 'I';
		return '';
	}

	/** @return bool  TRUE when lc.event_time is non-zero (movement has been received). */
	public function isReceived(): bool {
		return !empty( $this->mInfo['event_time'] );
	}

	/**
	 * Set lc.event_time to the current Unix timestamp, marking this movement as received.
	 *
	 * @return bool FALSE if the movement is not valid; TRUE otherwise.
	 */
	public function markReceived(): bool {
		if( !$this->isValid() ) return false;
		$now = time();
		$this->mDb->query(
			"UPDATE `".BIT_DB_PREFIX."liberty_content` SET `event_time` = ? WHERE `content_id` = ?",
			[ $now, $this->mContentId ]
		);
		$this->mInfo['event_time'] = $now;
		return true;
	}

	/**
	 * Append quantity xrefs from an assembly BOM into this movement, scaled by kit count.
	 *
	 * Reads SGL/PRT/SHT/VOL xrefs from the assembly and inserts them as new movement
	 * quantity lines, starting xorder after any existing items. Safe to call multiple
	 * times (e.g. for multi-assembly requisitions) — lines are only appended.
	 *
	 * @param  int   $pAssemblyContentId  Source assembly content_id.
	 * @param  float $pKitCount           Multiplier applied to each BOM quantity.
	 * @return bool  FALSE if movement or assembly id is invalid.
	 */
	public function explodeFromAssembly( int $pAssemblyContentId, float $pKitCount = 1 ): bool {
		if( !$this->isValid() || !$this->verifyId( $pAssemblyContentId ) ) {
			return false;
		}

		$nextXorder = (int)$this->mDb->getOne(
			"SELECT COALESCE( MAX(x.`xorder`) + 1, 1 ) FROM `".BIT_DB_PREFIX."liberty_xref` x
			 WHERE x.`content_id` = ? AND x.`item` IN ('SGL','PRT','SHT','VOL')",
			[ $this->mContentId ]
		) ?: 1;

		$rows = $this->mDb->query(
			"SELECT x.`xref` AS item_content_id, x.`item` AS quantity_item,
			        CAST(x.`xkey` AS DOUBLE PRECISION) AS quantity_value
			 FROM `".BIT_DB_PREFIX."liberty_xref` x
			 WHERE x.`content_id` = ? AND x.`item` IN ('SGL','PRT','SHT','VOL')
			   AND x.`xkey` SIMILAR TO '[0-9]+(\.[0-9]+)?'
			 ORDER BY x.`xorder`",
			[ $pAssemblyContentId ]
		);

		$this->StartTrans();
		foreach( $rows as $row ) {
			$bomHash = [
				'content_id' => $this->mContentId,
				'item'       => $row['quantity_item'],
				'xref'       => (int)$row['item_content_id'],
				'xkey'       => $row['quantity_value'] * $pKitCount,
				'xorder'     => $nextXorder,
			];
			$this->storeXref( $bomHash );
			$nextXorder++;
		}
		$this->CompleteTrans();
		return true;
	}

	/**
	 * Return a paged, keyed list of movements.
	 *
	 * Recognised filter keys: ref_type (REQN/TRANS/ORDER), assembly_content_id,
	 * component_content_id, user_id, find, sort_mode.
	 * When component_content_id is set, cmp_qty_type and cmp_qty are included per row.
	 * Sets $pListHash['cant'] on return.
	 *
	 * @param  array $pListHash  Filter and pagination params; modified in place.
	 * @return array             content_id-keyed result rows.
	 */
	public function getList( array &$pListHash ): array {
		global $gBitUser;
		LibertyContent::prepGetList( $pListHash );
		$ret = $bindVars = [];
		$selectSql = $whereSql = $joinSql = '';

		$whereSql = " AND lc.`content_type_guid` = '".STOCKMOVEMENT_CONTENT_TYPE_GUID."'";

		if( !empty( $pListHash['ref_type'] ) && in_array( $pListHash['ref_type'], [ 'REQN', 'TRANS', 'ORDER', 'PBLD' ] ) ) {
			$joinSql  .= " INNER JOIN `".BIT_DB_PREFIX."liberty_xref` xrf ON xrf.`content_id` = lc.`content_id` AND xrf.`item` = ?";
			$bindVars[] = $pListHash['ref_type'];
		}

		$partId    = 0;
		$partIsAsm = false;
		if( $this->verifyId( $pListHash['assembly_content_id'] ?? 0 ) ) {
			$partId    = (int)$pListHash['assembly_content_id'];
			$partIsAsm = true;
			$joinSql  .= " INNER JOIN `".BIT_DB_PREFIX."liberty_xref` xasm ON xasm.`content_id` = lc.`content_id` AND xasm.`item` = 'ASSEMBLY' AND xasm.`xref` = ?";
			$bindVars[] = $partId;
		} elseif( $this->verifyId( $pListHash['component_content_id'] ?? 0 ) ) {
			$partId    = (int)$pListHash['component_content_id'];
			$whereSql .= " AND EXISTS (SELECT 1 FROM `".BIT_DB_PREFIX."liberty_xref` xcf
				WHERE xcf.`content_id` = lc.`content_id` AND xcf.`item` IN ('SGL','PRT','SHT','VOL') AND xcf.`xref` = $partId)";
		}
		if( $this->verifyId( $pListHash['user_id'] ?? 0 ) ) {
			$whereSql .= " AND lc.`user_id` = ?";
			$bindVars[] = (int)$pListHash['user_id'];
		}
		if( !empty( $pListHash['find'] ) ) {
			$whereSql .= " AND UPPER(lc.`title`) LIKE ?";
			$bindVars[] = '%'.strtoupper( $pListHash['find'] ).'%';
		}

		$this->getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

		$sortMode = $pListHash['sort_mode'] ?? '';
		$orderby = match( $sortMode ) {
			'event_time_asc'       => ' ORDER BY lc.event_time ASC',
			'event_time_desc'      => ' ORDER BY lc.event_time DESC',
			'ref_start_date_asc'   => ' ORDER BY (SELECT FIRST 1 x.start_date FROM '.BIT_DB_PREFIX.'liberty_xref x WHERE x.content_id=lc.content_id AND x.item IN (\'REQN\',\'TRANS\',\'ORDER\',\'PBLD\') ORDER BY x.xorder) ASC',
			'ref_start_date_desc'  => ' ORDER BY (SELECT FIRST 1 x.start_date FROM '.BIT_DB_PREFIX.'liberty_xref x WHERE x.content_id=lc.content_id AND x.item IN (\'REQN\',\'TRANS\',\'ORDER\',\'PBLD\') ORDER BY x.xorder) DESC',
			default => !empty( $sortMode )
				? ' ORDER BY '.$this->mDb->convertSortmode( $sortMode )
				: ' ORDER BY lc.last_modified DESC',
		};

		if( !empty( $whereSql ) ) {
			$whereSql = substr_replace( $whereSql, ' WHERE ', 0, 4 );
		}

		$pListHash['cant'] = (int)$this->mDb->getOne(
			"SELECT COUNT(DISTINCT lc.`content_id`)
			 FROM `".BIT_DB_PREFIX."liberty_content` lc
				INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON uu.`user_id` = lc.`user_id`
				$joinSql
			 $whereSql",
			$bindVars
		);

		$X = BIT_DB_PREFIX;
		if( $partIsAsm ) {
			$partQtySelect = ", CAST(NULL AS VARCHAR(4)) AS part_qty_type
			     , CAST(xasm.`xkey` AS DOUBLE PRECISION) AS part_qty";
		} elseif( $partId ) {
			$partQtySelect = ", (SELECT FIRST 1 x.`item` FROM `{$X}liberty_xref` x
			      WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('SGL','PRT','SHT','VOL') AND x.`xref` = $partId
			      ORDER BY x.`xorder`) AS part_qty_type
			     , (SELECT SUM(CAST(x.`xkey` AS DOUBLE PRECISION)) FROM `{$X}liberty_xref` x
			      WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('SGL','PRT','SHT','VOL') AND x.`xref` = $partId) AS part_qty";
		} else {
			$partQtySelect = ", CAST(NULL AS VARCHAR(4)) AS part_qty_type, CAST(NULL AS DOUBLE PRECISION) AS part_qty";
		}

		$query = "SELECT lc.`content_id`, lc.`title`, lc.`created`, lc.`last_modified`, lc.`event_time`,
						lc.`user_id`, uu.`login`, uu.`real_name`,
						(SELECT FIRST 1 x.`item` FROM `{$X}liberty_xref` x
						 WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER','PBLD')
						 ORDER BY x.`xorder`) AS ref_type,
						(SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x
						 WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER','PBLD')
						 ORDER BY x.`xorder`) AS ref_key,
						(SELECT FIRST 1 x.`start_date` FROM `{$X}liberty_xref` x
						 WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER','PBLD')
						 ORDER BY x.`xorder`) AS ref_start_date
						$partQtySelect
						$selectSql
				FROM `{$X}liberty_content` lc
					INNER JOIN `{$X}users_users` uu ON uu.`user_id` = lc.`user_id`
					$joinSql
				$whereSql $orderby";

		if( $rows = $this->mDb->query( $query, $bindVars, $pListHash['max_records'], $pListHash['offset'] ) ) {
			foreach( $rows as $row ) {
				$row['display_url']      = static::getDisplayUrlFromHash( $row );
				$ret[$row['content_id']] = $row;
			}
		}
		LibertyContent::postGetList( $pListHash );
		return $ret;
	}

	/**
	 * @param  array $pParamHash  Must contain 'content_id'.
	 * @return string             URL to view_movement.php, or '' if id invalid.
	 */
	public static function getDisplayUrlFromHash( &$pParamHash ) {
		if( BitBase::verifyId( $pParamHash['content_id'] ?? 0 ) ) {
			return STOCK_PKG_URL.'view_movement.php?content_id='.$pParamHash['content_id'];
		}
		return '';
	}

	/** @return string  Display URL for this movement. */
	public function getDisplayUrl(): string {
		return static::getDisplayUrlFromHash( $this->mInfo );
	}

	/** @return string  URL to edit_movement.php for this movement. */
	public function getEditUrl( $pContentId = null, $pMixed = null ): string {
		if( $this->verifyId( $this->mContentId ) ) {
			return STOCK_PKG_URL.'edit_movement.php?content_id='.$this->mContentId;
		}
		return STOCK_PKG_URL.'edit_movement.php';
	}

	/** @return string Always 'stock'. */
	public static function getServiceKey(): string {
		return 'stock';
	}

	/**
	 * Process an uploaded CSV file into this movement's reference and quantity xrefs.
	 *
	 * Row 1: from (SCREF), ref, order_date (dd/mm/yy), received_date (dd/mm/yy optional).
	 * Rows 2+: component_title, quantity, [qty_type override (SGL/PCK/SHT/VOL)].
	 *
	 * The reference row updates or creates the REQN/TRANS/ORDER xref. Order date sets
	 * xref.start_date; received date sets lc.event_time. Unknown components are skipped.
	 *
	 * @param  string[] $pQtyTypes  Allowed qty type codes (e.g. ['SGL','PRT','SHT','VOL']).
	 * @return array{loaded:int, skipped:int, errors:string[]}
	 */
	public function importCsv( array $pQtyTypes ): array {
		$result = [ 'loaded' => 0, 'skipped' => 0, 'errors' => [] ];
		$file   = $_FILES['csv_file'] ?? null;
		if( !$file || $file['error'] !== UPLOAD_ERR_OK ) {
			return $result;
		}
		$handle = fopen( $file['tmp_name'], 'r' );
		if( $handle === false ) {
			return $result;
		}

		$nextXorder = (int)$this->mDb->getOne(
			"SELECT COALESCE( MAX(x.`xorder`) + 1, 1 ) FROM `".BIT_DB_PREFIX."liberty_xref` x
			 WHERE x.`content_id` = ? AND x.`item` IN ('SGL','PRT','SHT','VOL')",
			[ $this->mContentId ]
		) ?: 1;

		$rowNum = 0;
		while( ( $data = fgetcsv( $handle, 1000, ',', '"', '' ) ) !== false ) {
			$rowNum++;
			if( $rowNum === 1 ) {
				$from         = trim( $data[0] ?? '' );
				$ref          = trim( $data[1] ?? '' );
				$orderDateStr = trim( $data[2] ?? '' );
				$recvDateStr  = trim( $data[3] ?? '' );
				if( $ref !== '' ) {
					$existingRow = $this->mDb->getRow(
						"SELECT `xref_id`, `item` FROM `".BIT_DB_PREFIX."liberty_xref`
						 WHERE `content_id` = ? AND `item` IN ('REQN','TRANS','ORDER','PBLD') ORDER BY `xorder`",
						[ $this->mContentId ]
					);
					// Preserve existing type if already set; default to TRANS for new rows
					$refItem = $existingRow['item'] ?? 'TRANS';
					// Look up contact by SCREF short name
					$contactId = $from !== '' ? (int)$this->mDb->getOne(
						"SELECT `content_id` FROM `".BIT_DB_PREFIX."liberty_xref`
						 WHERE `item`='SCREF' AND `xkey`=?",
						[ $from ]
					) : 0;
					$refHash = [ 'content_id' => $this->mContentId, 'item' => $refItem, 'xkey' => $ref, 'edit' => $from ];
					if( $contactId ) $refHash['xref'] = $contactId;
					$existingRow ? $refHash['xref_id'] = $existingRow['xref_id'] : $refHash['fAddXref'] = 1;
					$this->storeXref( $refHash );
				}
				// Order date (col 3) → xref.start_date
				if( $orderDateStr !== '' ) {
					$parts = explode( '/', $orderDateStr );
					if( count( $parts ) === 3 ) {
						$year = (int)$parts[2] < 100 ? 2000 + (int)$parts[2] : (int)$parts[2];
						$ts   = mktime( 0, 0, 0, (int)$parts[1], (int)$parts[0], $year );
						if( $ts ) {
							$this->mDb->query(
								"UPDATE `".BIT_DB_PREFIX."liberty_xref`
								 SET `start_date` = ?
								 WHERE `content_id` = ? AND `item` IN ('REQN','TRANS','ORDER','PBLD')",
								[ date( 'Y-m-d H:i:s', $ts ), $this->mContentId ]
							);
						}
					}
				}
				// Received date (col 4) → lc.event_time
				if( $recvDateStr !== '' ) {
					$parts = explode( '/', $recvDateStr );
					if( count( $parts ) === 3 ) {
						$year = (int)$parts[2] < 100 ? 2000 + (int)$parts[2] : (int)$parts[2];
						$ts   = mktime( 0, 0, 0, (int)$parts[1], (int)$parts[0], $year );
						if( $ts ) {
							$this->mDb->query(
								"UPDATE `".BIT_DB_PREFIX."liberty_content` SET `event_time` = ? WHERE `content_id` = ?",
								[ $ts, $this->mContentId ]
							);
						}
					}
				}
				continue;
			}

			$componentName = trim( $data[0] ?? '' );
			$qty           = (float)trim( $data[1] ?? '' );
			$qtyOverride   = strtoupper( trim( $data[2] ?? '' ) );

			if( $componentName === '' ) { $result['skipped']++; continue; }
			if( $qty <= 0 ) {
				$result['errors'][] = "Row $rowNum: '$componentName' — invalid quantity, skipped.";
				$result['skipped']++;
				continue;
			}

			$contentId = $this->mDb->getOne(
				"SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."liberty_content` lc
				 WHERE lc.`content_type_guid` = 'stockcomponent' AND lc.`title` = ?",
				[ $componentName ]
			);
			if( !$contentId ) {
				$result['errors'][] = "Row $rowNum: '$componentName' not found, skipped.";
				$result['skipped']++;
				continue;
			}

			$qtySrc = in_array( $qtyOverride, $pQtyTypes ) ? $qtyOverride : null;
			if( !$qtySrc ) {
				$placeholders = implode( ',', array_fill( 0, count( $pQtyTypes ), '?' ) );
				$qtySrc = $this->mDb->getOne(
					"SELECT x.`item` FROM `".BIT_DB_PREFIX."liberty_xref` x
					 WHERE x.`content_id` = ? AND x.`item` IN ($placeholders) ORDER BY x.`xorder`",
					array_merge( [ (int)$contentId ], $pQtyTypes )
				) ?: 'SGL';
			}

			$itemHash = [ 'content_id' => $this->mContentId, 'item' => $qtySrc, 'xref' => (int)$contentId, 'xkey' => $qty, 'xorder' => $nextXorder ];
			$this->storeXref( $itemHash );
			$nextXorder++;
			$result['loaded']++;
		}
		fclose( $handle );
		$this->load();
		return $result;
	}
}
