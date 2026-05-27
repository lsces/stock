<?php
/**
 * @package stock
 */

namespace Bitweaver\Stock;

use Bitweaver\BitBase;
use Bitweaver\Liberty\LibertyContent;

define( 'STOCKMOVEMENT_CONTENT_TYPE_GUID', 'stockmovement' );

define( 'STOCK_MOVEMENT_IN',  'I' );
define( 'STOCK_MOVEMENT_OUT', 'O' );

/**
 * @package stock
 */
#[\AllowDynamicProperties]
class StockMovement extends StockBase {
	public $mMovementId;
	protected $mXrefTypeKey = 'stockmovement_types';

	public function __construct( $pMovementId = null, $pContentId = null ) {
		parent::__construct();
		$this->mContentTypeGuid = STOCKMOVEMENT_CONTENT_TYPE_GUID;
		if( $this->verifyId( $pMovementId ) ) {
			$this->mMovementId = (int)$pMovementId;
		}
		if( $this->verifyId( $pContentId ) ) {
			$this->mContentId = (int)$pContentId;
		}

		$this->registerContentType(
			STOCKMOVEMENT_CONTENT_TYPE_GUID, [
				'content_type_guid'  => STOCKMOVEMENT_CONTENT_TYPE_GUID,
				'content_name'       => 'Movement',
				'content_name_plural'=> 'Movements',
				'handler_class'      => 'StockMovement',
				'handler_package'    => 'stock',
				'handler_file'       => 'StockMovement.php',
				'maintainer_url'     => 'https://www.bitweaver.org',
		] );

		$this->mViewContentPerm   = 'p_stock_view';
		$this->mCreateContentPerm = 'p_stock_create';
		$this->mUpdateContentPerm = 'p_stock_update';
		$this->mAdminContentPerm  = 'p_stock_admin';
	}

	public function __sleep() {
		return array_merge( parent::__sleep(), [ 'mMovementId' ] );
	}

	public function isValid(): bool {
		return @$this->verifyId( $this->mMovementId ) || @$this->verifyId( $this->mContentId );
	}

	public static function lookup( $pLookupHash ) {
		global $gBitDb;
		$ret = null;
		$lookupContentId = null;

		if( !empty( $pLookupHash['movement_id'] ) && is_numeric( $pLookupHash['movement_id'] ) ) {
			if( $lookup = $gBitDb->getRow(
				"SELECT lc.`content_id`, lc.`content_type_guid`
				 FROM `".BIT_DB_PREFIX."stock_movement` sm
				 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = sm.`content_id`
				 WHERE sm.`movement_id` = ?",
				[ $pLookupHash['movement_id'] ]
			) ) {
				$lookupContentId   = $lookup['content_id'];
				$lookupContentGuid = $lookup['content_type_guid'];
			}
		} elseif( !empty( $pLookupHash['content_id'] ) && is_numeric( $pLookupHash['content_id'] ) ) {
			$lookupContentId   = $pLookupHash['content_id'];
			$lookupContentGuid = null;
		}

		if( static::verifyId( $lookupContentId ) ) {
			$ret = static::getLibertyObject( $lookupContentId, $lookupContentGuid );
		}
		return $ret;
	}

	public function load() {
		if( $this->isValid() ) {
			$bindVars = [];

			if( @$this->verifyId( $this->mMovementId ) ) {
				$whereSql = " WHERE sm.`movement_id` = ?";
				$bindVars[] = $this->mMovementId;
			} elseif( @$this->verifyId( $this->mContentId ) ) {
				$whereSql = " WHERE sm.`content_id` = ?";
				$bindVars[] = $this->mContentId;
			}

			$selectSql = $joinSql = '';
			$this->getServicesSql( 'content_load_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

			$sql = "SELECT sm.*, lc.* $selectSql
						, uue.`login` AS `modifier_user`, uue.`real_name` AS `modifier_real_name`
						, uuc.`login` AS `creator_user`,  uuc.`real_name` AS `creator_real_name`
					FROM `".BIT_DB_PREFIX."stock_movement` sm
						INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = sm.`content_id`
						LEFT JOIN  `".BIT_DB_PREFIX."users_users` uue ON uue.`user_id` = lc.`modifier_user_id`
						LEFT JOIN  `".BIT_DB_PREFIX."users_users` uuc ON uuc.`user_id` = lc.`user_id`
						$joinSql
					$whereSql";

			if( $this->mInfo = $this->mDb->getRow( $sql, $bindVars ) ) {
				$this->mMovementId      = $this->mInfo['movement_id'];
				$this->mContentId       = $this->mInfo['content_id'];
				$this->mContentTypeGuid = $this->mInfo['content_type_guid'];
				$this->mInfo['creator'] = $this->mInfo['creator_real_name'] ?? $this->mInfo['creator_user'];
				$this->mInfo['editor']  = $this->mInfo['modifier_real_name'] ?? $this->mInfo['modifier_user'];
				LibertyContent::load();
				$this->loadXrefList();
				$this->mInfo['items']   = $this->loadItems();
			}
			return count( $this->mInfo );
		}
		return null;
	}

	protected function verifyMovementData( array &$pParamHash ): bool {
		$pParamHash['content_type_guid'] = STOCKMOVEMENT_CONTENT_TYPE_GUID;
		if( $this->isValid() ) {
			$pParamHash['content_id'] = $this->mContentId;
		}
		if( empty( $pParamHash['title'] ) ) {
			$this->mErrors['title'] = 'A title is required.';
		}
		if( empty( $pParamHash['direction'] ) || !in_array( $pParamHash['direction'], [ STOCK_MOVEMENT_IN, STOCK_MOVEMENT_OUT ] ) ) {
			$pParamHash['direction'] = STOCK_MOVEMENT_OUT;
		}
		$validStatuses = [ 'draft', 'pending', 'complete', 'cancelled' ];
		if( empty( $pParamHash['status'] ) || !in_array( $pParamHash['status'], $validStatuses ) ) {
			$pParamHash['status'] = 'draft';
		}
		return count( $this->mErrors ) == 0;
	}

	public function store( array &$pParamHash ): bool {
		if( $this->verifyMovementData( $pParamHash ) ) {
			$this->StartTrans();
			if( LibertyContent::store( $pParamHash ) ) {
				$this->mContentId          = $pParamHash['content_id'];
				$this->mInfo['content_id'] = $this->mContentId;

				if( $this->movementExistsInDatabase() ) {
					$this->mDb->associateUpdate(
						BIT_DB_PREFIX.'stock_movement',
						[
							'direction' => $pParamHash['direction'],
							'status'    => $pParamHash['status'],
							'parent_id' => $pParamHash['parent_id'] ?? $this->mInfo['parent_id'] ?? null,
						],
						[ 'movement_id' => $this->mMovementId ]
					);
				} else {
					$this->mMovementId           = $this->mDb->GenID( 'stock_movement_id_seq' );
					$this->mInfo['movement_id']  = $this->mMovementId;
					$this->mDb->associateInsert(
						BIT_DB_PREFIX.'stock_movement',
						[
							'movement_id' => $this->mMovementId,
							'content_id'  => $this->mContentId,
							'direction'   => $pParamHash['direction'],
							'status'      => $pParamHash['status'],
							'parent_id'   => $pParamHash['parent_id'] ?? null,
						]
					);
				}
				$this->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
			}
		}
		return count( $this->mErrors ) == 0;
	}

	public function expunge(): bool {
		if( $this->isValid() ) {
			$this->StartTrans();
			$this->mDb->query(
				"DELETE FROM `".BIT_DB_PREFIX."stock_movement_item` WHERE `movement_content_id` = ?",
				[ $this->mContentId ]
			);
			$this->mDb->query(
				"DELETE FROM `".BIT_DB_PREFIX."stock_movement` WHERE `content_id` = ?",
				[ $this->mContentId ]
			);
			if( LibertyContent::expunge() ) {
				$this->CompleteTrans();
				$this->mMovementId = null;
				$this->mContentId  = null;
			} else {
				$this->mDb->RollbackTrans();
			}
		}
		return true;
	}

	public function movementExistsInDatabase(): bool {
		if( $this->verifyId( $this->mMovementId ) ) {
			return (int)$this->mDb->getOne(
				"SELECT COUNT(`movement_id`) FROM `".BIT_DB_PREFIX."stock_movement` WHERE `movement_id` = ?",
				[ $this->mMovementId ]
			) > 0;
		}
		return false;
	}

	// Returns all items for this movement, keyed by item_content_id.
	// $pSortMode matches smartlink output: 'item_position_asc' (default), 'item_position_desc', 'title_asc', 'title_desc'
	public function loadItems( string $pSortMode = 'item_position_asc' ): array {
		$ret = [];
		if( $this->isValid() && $this->verifyId( $this->mContentId ) ) {
			$orderby = match( $pSortMode ) {
				'item_position_desc' => 'smi.`item_position` DESC, smi.`item_content_id` DESC',
				'title_asc'          => 'lc.`title` ASC',
				'title_desc'         => 'lc.`title` DESC',
				default              => 'smi.`item_position` ASC, smi.`item_content_id` ASC',
			};
			$rows = $this->mDb->query(
				"SELECT smi.*, lc.`title`, lc.`content_type_guid`
				 FROM `".BIT_DB_PREFIX."stock_movement_item` smi
				 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = smi.`item_content_id`
				 WHERE smi.`movement_content_id` = ?
				 ORDER BY $orderby",
				[ $this->mContentId ]
			);
			foreach( $rows as $row ) {
				$ret[$row['item_content_id']] = $row;
			}
		}
		return $ret;
	}

	public function addItem( int $pItemContentId, float $pQtyValue = 1, string $pQtySrc = 'SGL', ?float $pPosition = null ): bool {
		if( !$this->isValid() || !$this->verifyId( $pItemContentId ) ) {
			return false;
		}
		// Remove any existing row for this item first (one item per movement line)
		$this->mDb->query(
			"DELETE FROM `".BIT_DB_PREFIX."stock_movement_item` WHERE `movement_content_id` = ? AND `item_content_id` = ?",
			[ $this->mContentId, $pItemContentId ]
		);
		$this->mDb->associateInsert(
			BIT_DB_PREFIX.'stock_movement_item',
			[
				'movement_content_id' => $this->mContentId,
				'item_content_id'     => $pItemContentId,
				'item_position'       => $pPosition,
				'quantity_value'      => $pQtyValue,
				'quantity_item'     => $pQtySrc,
			]
		);
		return true;
	}

	public function removeItem( int $pItemContentId ): bool {
		if( $this->isValid() && $this->verifyId( $pItemContentId ) ) {
			$this->mDb->query(
				"DELETE FROM `".BIT_DB_PREFIX."stock_movement_item` WHERE `movement_content_id` = ? AND `item_content_id` = ?",
				[ $this->mContentId, $pItemContentId ]
			);
			return true;
		}
		return false;
	}

	// Populates movement items by exploding a kit BOM, scaled by $pKitCount
	public function explodeFromAssembly( int $pAssemblyContentId, float $pKitCount = 1 ): bool {
		if( !$this->isValid() || !$this->verifyId( $pAssemblyContentId ) ) {
			return false;
		}
		$this->StartTrans();

		$this->mDb->query(
			"DELETE FROM `".BIT_DB_PREFIX."stock_movement_item` WHERE `movement_content_id` = ?",
			[ $this->mContentId ]
		);

		$rows = $this->mDb->query(
			"SELECT `item_content_id`, `item_position`, `quantity_value`, `quantity_item`
			 FROM `".BIT_DB_PREFIX."stock_assembly_component_map`
			 WHERE `assembly_content_id` = ?
			 ORDER BY `item_position`",
			[ $pAssemblyContentId ]
		);
		foreach( $rows as $row ) {
			$this->mDb->associateInsert(
				BIT_DB_PREFIX.'stock_movement_item',
				[
					'movement_content_id' => $this->mContentId,
					'item_content_id'     => $row['item_content_id'],
					'item_position'       => $row['item_position'],
					'quantity_value'      => $row['quantity_value'] * $pKitCount,
					'quantity_item'     => $row['quantity_item'],
				]
			);
		}

		$this->mDb->query(
			"UPDATE `".BIT_DB_PREFIX."stock_movement` SET `parent_id` = ? WHERE `content_id` = ?",
			[ $pAssemblyContentId, $this->mContentId ]
		);
		$this->mInfo['parent_id'] = $pAssemblyContentId;

		$this->CompleteTrans();
		return true;
	}

	// Commits stock level changes for each item and marks the movement complete.
	// For each item: updates the component's current-stock xref and appends a MOV audit record.
	// Only runs when status is 'pending'; call store() to advance status first.
	public function processMovement(): bool {
		if( !$this->isValid() || ( $this->mInfo['status'] ?? '' ) !== 'pending' ) {
			$this->mErrors['status'] = 'Movement must be in pending status to process.';
			return false;
		}

		$items = $this->loadItems();
		if( empty( $items ) ) {
			$this->mErrors['items'] = 'No items to process.';
			return false;
		}

		$sign = $this->mInfo['direction'] === STOCK_MOVEMENT_IN ? 1 : -1;

		$this->StartTrans();
		foreach( $items as $itemContentId => $item ) {
			$qtySrc = $item['quantity_item'];
			$qtyVal = (float)$item['quantity_value'];

			// Load existing current-stock xref record for this source
			$existing = $this->mDb->getRow(
				"SELECT `xref_id`, `data` FROM `".BIT_DB_PREFIX."liberty_xref`
				 WHERE `content_id` = ? AND `item` = ? AND (`end_date` IS NULL OR `end_date` > CURRENT_TIMESTAMP)",
				[ $itemContentId, $qtySrc ]
			);

			$newQty = ( (float)( $existing['data'] ?? 0 ) ) + ( $sign * $qtyVal );

			if( $existing ) {
				$this->mDb->associateUpdate(
					BIT_DB_PREFIX.'liberty_xref',
					[ 'data' => $newQty, 'last_update_date' => $this->mDb->NOW() ],
					[ 'xref_id' => $existing['xref_id'] ]
				);
			} else {
				$this->mDb->associateInsert(
					BIT_DB_PREFIX.'liberty_xref',
					[
						'xref_id'          => $this->mDb->GenID( 'liberty_xref_seq' ),
						'content_id'       => $itemContentId,
						'item'           => $qtySrc,
						'xorder'           => 0,
						'data'             => $newQty,
						'last_update_date' => $this->mDb->NOW(),
					]
				);
			}

			// Append MOV audit record (multi=1 — always insert, never update)
			$movOrder = (int)$this->mDb->getOne(
				"SELECT COALESCE( MAX(`xorder`) + 1, 1 ) FROM `".BIT_DB_PREFIX."liberty_xref` WHERE `content_id` = ? AND `item` = 'MOV'",
				[ $itemContentId ]
			);
			$this->mDb->associateInsert(
				BIT_DB_PREFIX.'liberty_xref',
				[
					'xref_id'          => $this->mDb->GenID( 'liberty_xref_seq' ),
					'content_id'       => $itemContentId,
					'item'           => 'MOV',
					'xorder'           => $movOrder,
					'xref'             => $this->mContentId,
					'data'             => $qtyVal,
					'last_update_date' => $this->mDb->NOW(),
				]
			);
		}

		$this->mDb->query(
			"UPDATE `".BIT_DB_PREFIX."stock_movement` SET `status` = 'complete' WHERE `content_id` = ?",
			[ $this->mContentId ]
		);
		$this->mInfo['status'] = 'complete';
		$this->CompleteTrans();
		return true;
	}

	public function getList( array &$pListHash ): array {
		global $gBitUser, $gBitSystem;

		LibertyContent::prepGetList( $pListHash );
		$ret = $bindVars = [];
		$selectSql = $whereSql = $joinSql = '';

		if( !empty( $pListHash['direction'] ) && in_array( $pListHash['direction'], [ STOCK_MOVEMENT_IN, STOCK_MOVEMENT_OUT ] ) ) {
			$whereSql .= " AND sm.`direction` = ?";
			$bindVars[] = $pListHash['direction'];
		}

		if( !empty( $pListHash['status'] ) ) {
			$whereSql .= " AND sm.`status` = ?";
			$bindVars[] = $pListHash['status'];
		}

		if( $this->verifyId( $pListHash['parent_id'] ?? 0 ) ) {
			$whereSql .= " AND sm.`parent_id` = ?";
			$bindVars[] = (int)$pListHash['parent_id'];
		}

		if( $this->verifyId( $pListHash['user_id'] ?? 0 ) ) {
			$whereSql .= " AND lc.`user_id` = ?";
			$bindVars[] = (int)$pListHash['user_id'];
		}

		$this->getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

		$orderby = !empty( $pListHash['sort_mode'] )
			? " ORDER BY ".$this->mDb->convertSortmode( $pListHash['sort_mode'] )
			: ' ORDER BY lc.`last_modified` DESC';

		if( !empty( $whereSql ) ) {
			$whereSql = substr_replace( $whereSql, ' WHERE ', 0, 4 );
		}

		$query = "SELECT sm.`movement_id` AS `hash_key`, sm.*, lc.*, uu.`login`, uu.`real_name` $selectSql
				FROM `".BIT_DB_PREFIX."stock_movement` sm
					INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON lc.`content_id` = sm.`content_id`
					INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON uu.`user_id` = lc.`user_id`
					$joinSql
				$whereSql $orderby";

		if( $rows = $this->mDb->query( $query, $bindVars, $pListHash['max_records'], $pListHash['offset'] ) ) {
			foreach( $rows as $row ) {
				$row['display_url']        = static::getDisplayUrlFromHash( $row );
				$ret[$row['movement_id']] = $row;
			}
		}

		LibertyContent::postGetList( $pListHash );
		return $ret;
	}

	public static function getDisplayUrlFromHash( &$pParamHash ) {
		global $gBitSystem;
		if( BitBase::verifyId( $pParamHash['movement_id'] ?? 0 ) ) {
			return STOCK_PKG_URL.'view_movement.php?movement_id='.$pParamHash['movement_id'];
		} elseif( BitBase::verifyId( $pParamHash['content_id'] ?? 0 ) ) {
			return STOCK_PKG_URL.'view_movement.php?content_id='.$pParamHash['content_id'];
		}
		return '';
	}

	public function getDisplayUrl(): string {
		$info             = &$this->mInfo;
		$info['movement_id'] = $this->mMovementId;
		return static::getDisplayUrlFromHash( $info );
	}

	public static function getServiceKey(): string {
		return 'stock';
	}
}
