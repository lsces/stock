<?php
/**
 * @package stock
 */

namespace Bitweaver\Stock;

use Bitweaver\BitBase;
use Bitweaver\Liberty\LibertyContent;

define( 'STOCKMOVEMENT_CONTENT_TYPE_GUID', 'stockmovement' );

/**
 * @package stock
 */
#[\AllowDynamicProperties]
class StockMovement extends LibertyContent {
	protected $mXrefTypeKey = 'stockmovement_types';

	public function __construct( $pContentId = null ) {
		parent::__construct();
		$this->mContentTypeGuid = STOCKMOVEMENT_CONTENT_TYPE_GUID;
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

	public function isValid(): bool {
		return (bool)$this->verifyId( $this->mContentId );
	}

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

	public function load( $pContentId = null, $pPluginParams = null ) {
		if( $pContentId ) $this->mContentId = (int)$pContentId;
		if( !$this->verifyId( $this->mContentId ) ) return false;

		$whereSql = " WHERE lc.`content_id` = ? AND lc.`content_type_guid` = '".STOCKMOVEMENT_CONTENT_TYPE_GUID."'";
		$bindVars = [ $this->mContentId ];
		$selectSql = $joinSql = '';
		$this->getServicesSql( 'content_load_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

		$sql = "SELECT lc.* $selectSql
					, uue.`login` AS `modifier_user`, uue.`real_name` AS `modifier_real_name`
					, uuc.`login` AS `creator_user`,  uuc.`real_name` AS `creator_real_name`
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
			$this->loadXrefList();
		}
		return !empty( $this->mInfo );
	}

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

	public function loadXrefList(): void {
		parent::loadXrefList();
		if( !empty( $this->mInfo['quantity'] ) ) {
			$componentIds = array_values( array_unique( array_filter( array_column( $this->mInfo['quantity'], 'xref' ) ) ) );
			if( $componentIds ) {
				$placeholders = implode( ',', array_fill( 0, count( $componentIds ), '?' ) );
				$components   = $this->mDb->getAssoc(
					"SELECT lc.`content_id`, lc.`title`, lc.`data`
					 FROM `".BIT_DB_PREFIX."liberty_content` lc
					 WHERE lc.`content_id` IN ($placeholders)",
					$componentIds
				);
				foreach( $this->mInfo['quantity'] as &$row ) {
					if( !empty( $row['xref'] ) && isset( $components[$row['xref']] ) ) {
						$row['xref_title'] = $components[$row['xref']]['title'];
						$row['xref_data']  = $components[$row['xref']]['data'];
					}
				}
				unset( $row );
			}
		}
	}

	// Direction inferred from reference xref: REQN = out, TRANS/ORDER = in
	public function getDirection(): string {
		if( !empty( $this->mInfo['reference'] ) ) {
			foreach( $this->mInfo['reference'] as $row ) {
				if( $row['item'] === 'REQN' )                         return 'O';
				if( in_array( $row['item'], [ 'TRANS', 'ORDER' ] ) ) return 'I';
			}
		}
		return 'O';
	}

	// Movement is received/fulfilled when lc.event_time is set
	public function isReceived(): bool {
		return !empty( $this->mInfo['event_time'] );
	}

	public function markReceived(): bool {
		if( !$this->isValid() ) return false;
		$now = $this->mDb->NOW();
		$this->mDb->query(
			"UPDATE `".BIT_DB_PREFIX."liberty_content` SET `event_time` = ? WHERE `content_id` = ?",
			[ $now, $this->mContentId ]
		);
		$this->mInfo['event_time'] = $now;
		return true;
	}

	// Populate movement quantity xrefs from an assembly BOM, scaled by $pKitCount
	public function explodeFromAssembly( int $pAssemblyContentId, float $pKitCount = 1 ): bool {
		if( !$this->isValid() || !$this->verifyId( $pAssemblyContentId ) ) {
			return false;
		}
		$this->StartTrans();
		$this->mDb->query(
			"DELETE FROM `".BIT_DB_PREFIX."liberty_xref`
			 WHERE `content_id` = ? AND `item` IN ('SGL','PCK','SHT','VOL')",
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
			$bomHash = [
				'content_id' => $this->mContentId,
				'item'       => $row['quantity_item'],
				'xref'       => $row['item_content_id'],
				'xkey'       => $row['quantity_value'] * $pKitCount,
				'xorder'     => $row['item_position'],
			];
			$this->storeXref( $bomHash );
		}
		$this->CompleteTrans();
		return true;
	}

	public function getList( array &$pListHash ): array {
		global $gBitUser;
		LibertyContent::prepGetList( $pListHash );
		$ret = $bindVars = [];
		$selectSql = $whereSql = $joinSql = '';

		$whereSql = " AND lc.`content_type_guid` = '".STOCKMOVEMENT_CONTENT_TYPE_GUID."'";

		if( !empty( $pListHash['ref_type'] ) && in_array( $pListHash['ref_type'], [ 'REQN', 'TRANS', 'ORDER' ] ) ) {
			$joinSql  .= " INNER JOIN `".BIT_DB_PREFIX."liberty_xref` xrf ON xrf.`content_id` = lc.`content_id` AND xrf.`item` = ?";
			$bindVars[] = $pListHash['ref_type'];
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

		$orderby = !empty( $pListHash['sort_mode'] )
			? " ORDER BY ".$this->mDb->convertSortmode( $pListHash['sort_mode'] )
			: ' ORDER BY lc.`last_modified` DESC';

		if( !empty( $whereSql ) ) {
			$whereSql = substr_replace( $whereSql, ' WHERE ', 0, 4 );
		}

		$X = BIT_DB_PREFIX;
		$query = "SELECT lc.`content_id`, lc.`title`, lc.`created`, lc.`last_modified`, lc.`event_time`,
						uu.`login`, uu.`real_name`,
						(SELECT FIRST 1 x.`item` FROM `{$X}liberty_xref` x
						 WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER')
						 ORDER BY x.`xorder`) AS ref_type,
						(SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x
						 WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('REQN','TRANS','ORDER')
						 ORDER BY x.`xorder`) AS ref_key
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

	public static function getDisplayUrlFromHash( &$pParamHash ) {
		if( BitBase::verifyId( $pParamHash['content_id'] ?? 0 ) ) {
			return STOCK_PKG_URL.'view_movement.php?content_id='.$pParamHash['content_id'];
		}
		return '';
	}

	public function getDisplayUrl(): string {
		return static::getDisplayUrlFromHash( $this->mInfo );
	}

	public static function getServiceKey(): string {
		return 'stock';
	}
}
