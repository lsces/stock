<?php
/**
 * @package stock
 */

/**
 * required setup
 */
namespace Bitweaver\Stock;

use Bitweaver\BitBase;
use Bitweaver\Liberty\LibertyContent;

define('STOCKASSEMBLY_CONTENT_TYPE_GUID', 'stockassembly');

define( 'STOCK_PAGINATION_FIXED_GRID', 'fixed_grid' );
define( 'STOCK_PAGINATION_AUTO_FLOW', 'auto_flow' );
define( 'STOCK_PAGINATION_POSITION_NUMBER', 'position_number' );
define( 'STOCK_PAGINATION_SIMPLE_LIST', 'simple_list' );


/**
 * @package stock
 */
#[\AllowDynamicProperties]
class StockAssembly extends StockBase {
	public $mItems;			// Array of StockComponent class instances which belong to this gallery
	public $mPaginationLookup;
	public $mPreviewImage;
	public $pRecursiveDelete;
	protected $mXrefTypeKey = 'stockassembly_types';

	public function __construct($pContentId = null) {
		parent::__construct();
		$this->mContentTypeGuid = STOCKASSEMBLY_CONTENT_TYPE_GUID;
		if( $this->verifyId( $pContentId ) ) {
			$this->mContentId = (int)$pContentId;
		}
		$this->mItems = [];					// Assume no images (if $pAutoLoad is true we will populate this array later)
		// This registers the content type for FishEye galleries
		// FYI: Any class which uses a table which inherits from liberty_content should create their own content type(s)
		$this->registerContentType(
			STOCKASSEMBLY_CONTENT_TYPE_GUID, [
				'content_type_guid' => STOCKASSEMBLY_CONTENT_TYPE_GUID,
				'content_name' => 'Assembly',
				'content_name_plural' => 'Assemblies',
				'handler_class' => 'StockAssembly',
				'handler_package' => 'stock',
				'handler_file' => 'StockAssembly.php',
				'maintainer_url' => 'https://www.bitweaver.org',
		], );

		// Permission setup
		$this->mViewContentPerm  = 'p_stock_view';
		$this->mCreateContentPerm  = 'p_stock_create';
		$this->mUpdateContentPerm  = 'p_stock_update';
		$this->mAdminContentPerm = 'p_stock_admin';
	}

	public function __wakeup() {
		return parent::__wakeup();
	}

	public function __sleep() {
		return parent::__sleep();
	}

	public function isValid() {
		return @$this->verifyId( $this->mContentId );
	}

	public function enrichXrefDisplay( array &$pXrefInfo ): void {
		parent::enrichXrefDisplay( $pXrefInfo );
		if( !empty( $pXrefInfo['xref'] ) ) {
			if( $comp = $this->mDb->getRow(
				"SELECT lc.`title`, lc.`data`, pck.`xkey` AS `pack_size`, pck.`xkey_ext` AS `pack_size_ext`
				 FROM `".BIT_DB_PREFIX."liberty_content` lc
				 LEFT JOIN `".BIT_DB_PREFIX."liberty_xref` pck ON pck.`content_id` = lc.`content_id` AND pck.`item` = 'PCK'
				 WHERE lc.`content_id` = ?",
				[ (int)$pXrefInfo['xref'] ]
			) ) {
				$pXrefInfo['xref_title'] = $comp['title'];
				$pXrefInfo['xref_data']  = $comp['data'];
				$pXrefInfo['pack_size']     = $comp['pack_size'];
				$pXrefInfo['pack_size_ext'] = $comp['pack_size_ext'];
			}
		}
	}

	public function loadXrefList(): void {
		parent::loadXrefList();
		if( !empty( $this->mInfo['quantity'] ) ) {
			usort( $this->mInfo['quantity'], fn($a,$b) => ($a['xorder'] <=> $b['xorder']) ?: strcmp($a['item'], $b['item']) );

			$componentIds = array_values( array_unique( array_filter( array_column( $this->mInfo['quantity'], 'xref' ) ) ) );
			if( $componentIds ) {
				$placeholders = implode( ',', array_fill( 0, count( $componentIds ), '?' ) );
				$components = $this->mDb->getAssoc(
					"SELECT lc.`content_id`, lc.`title`, lc.`data`, pck.`xkey` AS `pack_size`, pck.`xkey_ext` AS `pack_size_ext`
					 FROM `".BIT_DB_PREFIX."liberty_content` lc
					 LEFT JOIN `".BIT_DB_PREFIX."liberty_xref` pck ON pck.`content_id` = lc.`content_id` AND pck.`item` = 'PCK'
					 WHERE lc.`content_id` IN ($placeholders)",
					$componentIds
				);
				foreach( $this->mInfo['quantity'] as &$row ) {
					if( !empty( $row['xref'] ) && isset( $components[$row['xref']] ) ) {
						$row['xref_title'] = $components[$row['xref']]['title'];
						$row['xref_data']  = $components[$row['xref']]['data'];
						$row['pack_size']     = $components[$row['xref']]['pack_size'];
					$row['pack_size_ext'] = $components[$row['xref']]['pack_size_ext'];
					}
				}
				unset( $row );
			}
		}
	}

	public static function lookup( $pLookupHash, $pLoadFromCache=true ) {
		global $gBitDb;
		$ret = null;

		$lookupContentId = null;
		if( !empty($pLookupHash['content_id']) && is_numeric($pLookupHash['content_id']) ) {
			$lookupContentId = (int)$pLookupHash['content_id'];
		}

		if( static::verifyId( $lookupContentId ) ) {
			$ret = parent::getLibertyObject( $lookupContentId, STOCKASSEMBLY_CONTENT_TYPE_GUID, $pLoadFromCache );
		}

		return $ret;
	}

	public function load( $pContentId = null, $pPluginParams = null ) {
		global $gBitSystem;
		$bindVars = [];
		$selectSql = $joinSql = $whereSql = '';

		if( !$this->verifyId( $this->mContentId ) ) {
			return false;
		}

		$whereSql = " WHERE lc.`content_id` = ? AND lc.`content_type_guid` = '".STOCKASSEMBLY_CONTENT_TYPE_GUID."'";
		$bindVars = [ $this->mContentId ];

		$this->getServicesSql( 'content_load_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

		$query = "SELECT lc.* $selectSql
					, uue.`login` AS modifier_user, uue.`real_name` AS `modifier_real_name`
					, uuc.`login` AS creator_user, uuc.`real_name` AS `creator_real_name`
				FROM `".BIT_DB_PREFIX."liberty_content` lc $joinSql
					LEFT JOIN `".BIT_DB_PREFIX."users_users` uue ON (uue.`user_id` = lc.`modifier_user_id`)
					LEFT JOIN `".BIT_DB_PREFIX."users_users` uuc ON (uuc.`user_id` = lc.`user_id`)
				$whereSql";
		$rs = $this->mDb->getRow( $query, $bindVars );
		if( !empty($rs) ) {
			$this->mInfo = $rs;
			$this->mContentId       = $rs['content_id'];
			$this->mContentTypeGuid = $rs['content_type_guid'];
			LibertyContent::load();
			$this->loadXrefList();

			$this->mInfo['creator'] = $rs['creator_real_name'] ?? $rs['creator_user'];
			$this->mInfo['editor']  = $rs['modifier_real_name'] ?? $rs['modifier_user'];

			$this->mInfo['rows_per_page'] = $gBitSystem->getConfig( 'stock_gallery_default_rows_per_page', STOCK_DEFAULT_ROWS_PER_PAGE );
			$this->mInfo['cols_per_page'] = $gBitSystem->getConfig( 'stock_gallery_default_cols_per_page', STOCK_DEFAULT_COLS_PER_PAGE );
			if( empty( $this->mInfo['thumbnail_size'] ) ) {
				$this->mInfo['thumbnail_size'] = $this->getPreference( 'stock_gallery_default_thumbnail_size', null );
			}
			$this->mInfo['access_answer'] = '';

			$this->mInfo['num_components'] = $this->getComponentCount();
			if( $this->getPreference( 'assembly_pagination' ) == STOCK_PAGINATION_POSITION_NUMBER ) {
				$this->mInfo['num_pages'] = $this->mDb->getOne( "SELECT COUNT( distinct( floor(`item_position`) ) ) FROM `".BIT_DB_PREFIX."stock_assembly_component_map` WHERE assembly_content_id=?", [ $this->mContentId ] );
			} else {
				$pagination = $this->getPreference( 'assembly_pagination' );
				if( in_array( $pagination, [ STOCK_PAGINATION_AUTO_FLOW, STOCK_PAGINATION_SIMPLE_LIST ] ) ) {
					$this->mInfo['images_per_page'] = (int)$this->getPreference( 'total_per_page', $this->mInfo['rows_per_page'] );
				} else {
					$this->mInfo['images_per_page'] = $this->mInfo['cols_per_page'] * $this->mInfo['rows_per_page'];
				}
				$this->mInfo['num_pages'] = (int)$this->mInfo['num_components'] / $this->mInfo['images_per_page'] + ($this->mInfo['num_components'] % $this->mInfo['images_per_page'] == 0 ? 0 : 1);
			}
		}

		return !empty( $this->mInfo );
	}

	public function loadComponents( &$pListHash = [] ) {
		global $gLibertySystem, $gBitSystem, $gBitUser;
		if( !$this->isValid() ) {
			return null;
		}

		$pListHash['cant'] = $this->mInfo['num_components'];
		LibertyContent::prepGetList( $pListHash );

		if( empty( $this->mItems ) || !empty( $pListHash['refresh'] ) ) {
			$bindVars = [ $this->mContentId ];
			$whereSql = $selectSql = $joinSql = $orderSql = '';
			$offset = $pListHash['offset'];
			$rowCount = 0;
			$this->getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

			$orderSql = $gBitSystem->isFeatureActive( 'stock_gallery_default_sort_mode' )
				? ", ".$this->mDb->convertSortmode( $gBitSystem->getConfig( 'stock_gallery_default_sort_mode' ) )
				: ", fgim.`item_content_id`";

			// load for just a single page
			if( $pListHash['page'] != -1 ) {
				if( $this->getLayout() == STOCK_PAGINATION_POSITION_NUMBER ) {
					$query = "SELECT DISTINCT(FLOOR(`item_position`))
							  FROM `".BIT_DB_PREFIX."stock_assembly_component_map`
							  WHERE assembly_content_id=?
							  ORDER BY floor(item_position)";
					$mantissa = $this->mDb->getOne( $query, [ $this->mContentId ], 1, $pListHash['page'] - 1 );
					// gallery image order with no positions set will have null mantissa, and all images will be shown
					if( !is_null( $mantissa ) ) {
						$whereSql .= " AND floor(item_position)=? ";
						array_push( $bindVars, $mantissa );
					}
				} elseif( $this->getLayout() == STOCK_PAGINATION_FIXED_GRID ) {
					$rowCount = ($this->mInfo['rows_per_page'] ?? 3) * ($this->mInfo['cols_per_page'] ?? 3);
					$offset = $rowCount * ( (int) $pListHash['page'] - 1);
				} else {
					$rowCount = $pListHash['max_records'];
					$offset = $rowCount * ( (int) $pListHash['page'] - 1);
				}
			}
			if( empty($rowCount) ) $rowCount = $pListHash['max_records'] ?? 10;
			$this->mItems = [];

			$query = "SELECT fgim.*, lc.`user_id`, lct.*, ufm.`favorite_content_id` AS is_favorite $selectSql
					FROM `".BIT_DB_PREFIX."stock_assembly_component_map` fgim
						INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON ( lc.`content_id`=fgim.`item_content_id` )
						INNER JOIN `".BIT_DB_PREFIX."liberty_content_types` lct ON ( lct.`content_type_guid`=lc.`content_type_guid` )
						$joinSql
						LEFT OUTER JOIN `".BIT_DB_PREFIX."users_favorites_map` ufm ON ( ufm.`favorite_content_id`=lc.`content_id` AND lc.`user_id`=ufm.`user_id` )
					WHERE fgim.`assembly_content_id` = ? $whereSql
					ORDER BY fgim.`item_position` $orderSql";
			$rows = $this->mDb->query($query, $bindVars, $rowCount, $offset);
			foreach ($rows as $row) {
				$pass = true;
				if( $gBitSystem->isPackageActive( 'gatekeeper' ) ) {
					$pass = $gBitUser->hasPermission( 'p_stock_admin' ) || !@$this->verifyId( $row['security_id'] ) || ( $row['user_id'] == $gBitUser->mUserId ) || @$this->verifyId( $_SESSION['gatekeeper_security'][$row['security_id']] );
				}
				if( $pass ) {
					if( $item = parent::getLibertyObject( $row['item_content_id'], $row['content_type_guid'], $this->isCacheableObject() ) ) {
						$item->loadThumbnail( $this->mInfo['thumbnail_size'] ?? 'small' );
						$item->setGalleryPath( $this->mAssemblyPath.'/'.$this->mContentId );
						$item->mInfo['item_position'] = $row['item_position'];
						$this->mItems[$row['item_content_id']] = $item;
					}
				}
			}
		}

		LibertyContent::postGetList( $pListHash );

		return \count ( $this->mItems ) > 0;
	}

	public function getComponentList() {
		global $gLibertySystem, $gBitSystem, $gBitUser;
		$ret = null;
		if( $this->isValid() ) {
			$bindVars = [ $this->mContentId ];
			$whereSql = $selectSql = $joinSql = $orderSql = '';
			$rows = $offset = null;
			$this->getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

			$orderSql = $gBitSystem->isFeatureActive( 'stock_gallery_default_sort_mode' )
				? ", ".$this->mDb->convertSortmode( $gBitSystem->getConfig( 'stock_gallery_default_sort_mode' ) )
				: ", fgim.`item_content_id`";

			$this->mItems = [];

			$query = "SELECT lc.`content_id` AS `has_key`, fgim.*, lc.*, lct.*, ufm.`favorite_content_id` AS is_favorite $selectSql
					FROM `".BIT_DB_PREFIX."stock_assembly_component_map` fgim
						INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON ( lc.`content_id`=fgim.`item_content_id` )
						INNER JOIN `".BIT_DB_PREFIX."liberty_content_types` lct ON ( lct.`content_type_guid`=lc.`content_type_guid` )
						$joinSql
						LEFT OUTER JOIN `".BIT_DB_PREFIX."users_favorites_map` ufm ON ( ufm.`favorite_content_id`=lc.`content_id` AND lc.`user_id`=ufm.`user_id` )
					WHERE fgim.`assembly_content_id` = ? $whereSql
					ORDER BY fgim.`item_position` $orderSql";
			$ret = $this->mDb->getAssoc($query, $bindVars, $rows, $offset);
		}
		return $ret;
	}

	public function exportHash( $pPaginate = false ) {
		if( $ret = parent::exportHash() ) {
			$ret['type'] = $this->getContentType();
			if( $this->loadComponents() ) {
				foreach( array_keys( $this->mItems ) as $key ) {
					if( $pPaginate ) {
						if( $exp = $this->mItems[$key]->exportHash( $pPaginate ) ) {
							$ret['content']['page'][$this->getItemPage($key)][] = $exp;
						}
					} else {
						$ret['content'][] = $this->mItems[$key]->exportHash( $pPaginate );
					}
				}
			}
		}
		return $ret;
	}

	public function getItemPage( $pItemContentId ) {
		$ret = null;
		if( empty( $this->mPaginationLookup ) ) {
			$this->mPaginationLookup = $this->mDb->getAssoc( "SELECT `item_content_id`, floor(`item_position`) FROM `".BIT_DB_PREFIX."stock_assembly_component_map` WHERE `assembly_content_id`=?", [ $this->mContentId ] );
		}
		if( !empty( $this->mPaginationLookup[$pItemContentId] ) ) {
			$ret = $this->mPaginationLookup[$pItemContentId];
		}
		return $ret;
	}

	public function getPreviewHash() {
		$ret = [];
		if( !empty( $this->mInfo['preview_content'] ) ) {
			$ret =  $this->mInfo['preview_content']->mInfo;
		}
		// override  $this->mInfo['preview_content']->mInfo['display_url'] so we don't drive directly to the image
		$ret['display_url'] = $this->getDisplayUrl();
		return $ret;
	}

	public function getComponentCount() {
		$ret = 0;

		if( $this->verifyId( $this->mContentId ) ) {
			$bindVars = [ $this->mContentId ];
			$whereSql = $selectSql = $joinSql = $orderSql = '';
			$rows = $offset = null;
			$paramHash['no_fatal'] = true;
			$this->getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars, null, $paramHash );
			$query = 'SELECT COUNT(*) AS "count"
					FROM `'.BIT_DB_PREFIX."stock_assembly_component_map` fgim
					INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON ( lc.`content_id`=fgim.`item_content_id` )
					$joinSql WHERE `assembly_content_id` = ? $whereSql";
			$rs = $this->mDb->getRow($query, $bindVars);
			$ret = $rs['count'];
		}
		return $ret;
	}

	public function verifyGalleryData(&$pParamHash) {
		if( empty($pParamHash['title']) ) {
			$this->mErrors[] = "You must specify a title for this assembly";
		}
		$pParamHash['content_type_guid'] = $this->getContentType();
		return count($this->mErrors) == 0;
	}


	public function getThumbnailContentId() {
		if( !$this->getField( 'thumbnail_content_id' ) ) {
			$this->getThumbnailImage();
		}
		return $this->getField( 'thumbnail_content_id' );
	}

	public function getThumbnailUri( $pSize='small', $pInfoHash = null ) {
		if( empty( $this->mInfo['preview_content'] ) ) {
			$this->loadThumbnail();
		}

		if( !empty( $this->mInfo['preview_content'] ) && is_object( $this->mInfo['preview_content'] ) ) {
			return $this->mInfo['preview_content']->getThumbnailUri( $pSize );
		}
	}

	public function getThumbnailUrl( string $pSize = 'small', ?array $pInfoHash = null, ?int $pSecondaryId = null, ?int $pDefault = null ): string|null {
		if( empty( $this->mInfo['preview_content'] ) ) {
			$this->loadThumbnail();
		}

		if( is_object( $this->mInfo['preview_content'] ) ) {
			return $this->mInfo['preview_content']->getThumbnailUrl( $pSize );
		}
		return '';
	}

	public function getThumbnailImage( $pContentId=null, $pThumbnailContentId=null, $pThumbnailContentType=null ) {
		global $gLibertySystem, $gBitUser;
		$ret = null;

		if( !@$this->verifyId( $pContentId ) && !empty( $this->mContentId ) ) {
			$pContentId = $this->mContentId;
		}

		if( !@$this->verifyId( $pThumbnailContentId ) ) {
			if( $this->mDb->isAdvancedPostgresEnabled() ) {
				$whereSql = '';
				$bindVars = [ $pContentId ];
				if( !$gBitUser->isAdmin() ) {
					$whereSql = " AND (cgm.`security_id` IS null OR lc.`user_id`=?) ";
					$bindVars[] = $gBitUser->mUserId;
				}
				$query = "SELECT lc.`content_id`, lc.`content_type_guid`
							FROM connectby('`".BIT_DB_PREFIX."stock_assembly_component_map`', '`item_content_id`', '`assembly_content_id`', ?, 0, '/') AS t(`cb_item_content_id` int, `cb_parent_content_id` int, `level` int, `branch` text)
							INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON (lc.`content_id`=cb_item_content_id)
							LEFT OUTER JOIN `".BIT_DB_PREFIX."gatekeeper_security_map` cgm ON (cgm.`content_id`=lc.`content_id`)
							WHERE `cb_parent_content_id`=? $whereSql";
				if( $row = $this->mDb->getRow( $query, $bindVars ) ) {
					$pThumbnailContentType = $row['content_type_guid'];
					$pThumbnailContentId   = $row['content_id'];
				}
			} else {
				$query = "SELECT fgim.`item_content_id`, lc.`content_type_guid`
						FROM `".BIT_DB_PREFIX."stock_assembly_component_map` fgim
						INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON ( fgim.`item_content_id`=lc.`content_id` )
						WHERE fgim.`assembly_content_id` = ?
						ORDER BY ".$this->mDb->convertSortmode('random');
				$rs = $this->mDb->getRow( $query, [ $pContentId ], 1 );
				if( !empty( $rs ) ) {
					$pThumbnailContentId   = $rs['item_content_id'];
					$pThumbnailContentType = $rs['content_type_guid'];
				}
			}
		}

		if( @$this->verifyId( $pThumbnailContentId ) ) {
			$ret = parent::getLibertyObject( $pThumbnailContentId, $pThumbnailContentType, $this->isCacheableObject() );
			if( is_a( $ret, '\Bitweaver\Stock\StockAssembly' ) ) {
				//recurse down in to find the first image
				if( $ret = $ret->getThumbnailImage() ) {
					$this->mInfo['thumbnail_content_id'] = $ret->getField( 'content_id' );
				}
			} else {
				$this->mInfo['thumbnail_content_id'] = $pThumbnailContentId;
			}
		}
		return $ret;
	}

	public function loadThumbnail( $pSize='small', $pContentId=null ) {
		if( $this->mPreviewImage = $this->getThumbnailImage( $pContentId ) ) {
			$this->mInfo['preview_content'] = &$this->mPreviewImage;
			$this->mInfo['image_file'] = &$this->mPreviewImage->mInfo['image_file'];
		}
	}

	public function storeGalleryThumbnail($pContentId = null) {
		// Preview image link will be implemented via liberty_xref when assembly images are built
		return false;
	}

	public function store( array &$pParamHash ): bool {
		if( $this->verifyGalleryData( $pParamHash ) ) {
			$this->StartTrans();
			if( LibertyContent::store( $pParamHash ) ) {
				$this->mContentId          = $pParamHash['content_id'];
				$this->mInfo['content_id'] = $this->mContentId;
				$this->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
				$this->mErrors[] = "There were errors while attempting to save this assembly";
			}
		}
		return count($this->mErrors) == 0;
	}

	public function getComponentMapList( string $pSortMode = 'item_position_asc' ): array {
		$ret = [];
		if( $this->verifyId( $this->mContentId ) ) {
			$orderby = match( $pSortMode ) {
				'title_asc'          => 'lc.`title` ASC',
				'title_desc'         => 'lc.`title` DESC',
				'item_position_desc' => 'fgim.`item_position` DESC, fgim.`item_content_id` DESC',
				default              => 'fgim.`item_position` ASC, fgim.`item_content_id` ASC',
			};
			if( $rows = $this->mDb->query(
				"SELECT fgim.`item_content_id`, fgim.`item_position`, lc.`title`
				 FROM `".BIT_DB_PREFIX."stock_assembly_component_map` fgim
				 INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON (lc.`content_id` = fgim.`item_content_id`)
				 WHERE fgim.`assembly_content_id` = ?
				 ORDER BY $orderby",
				[ $this->mContentId ]
			) ) {
				foreach( $rows as $row ) {
					$ret[$row['item_content_id']] = $row;
				}
			}
		}
		return $ret;
	}

	public function removeItem( $pContentId ) {
		$ret = false;
		if( $this->isValid() && @$this->verifyId( $pContentId ) ) {
			$query = "DELETE FROM `".BIT_DB_PREFIX."stock_assembly_component_map`
					  WHERE `item_content_id`=? AND `assembly_content_id`=?";
			$rs = $this->mDb->getOne($query, [ $pContentId, $this->mContentId ] );
			$ret = true;
		}
		return $ret;
	}

	/**
	* Adds a new item (image or gallery) to this gallery. We check to make sure we are not a member
	* of this gallery and this gallery is not a member of the new item to avoid infinite recursion scenarios
	* @return bool wheter or not the item was added
	*/
	public function addItem( $pContentId, $pPosition=null ) {
		global $gBitSystem;
		$ret = false;
		if( @$this->verifyId( $this->mContentId ) && @$this->verifyId( $pContentId ) && ( $this->mContentId != $pContentId ) && !$this->isInAssembly( $this->mContentId, $pContentId  )  && !$this->isInAssembly( $pContentId, $this->mContentId ) ) {
			$query = "INSERT INTO `".BIT_DB_PREFIX."stock_assembly_component_map` (`item_content_id`, `assembly_content_id`, `item_position`) VALUES (?,?,?)";
			$rs = $this->mDb->getOne($query, [ $pContentId, $this->mContentId, $pPosition ] );
			$query = "UPDATE `".BIT_DB_PREFIX."liberty_content` SET `last_modified`=? WHERE `content_id`=?";
			$rs = $this->mDb->getOne( $query, [ $gBitSystem->getUTCTime(), $this->mContentId ] );
			$ret = true;
		}
		return $ret;
	}

	public function expunge(): bool {
		if( $this->isValid() ) {
			$this->StartTrans();

			if( $this->loadComponents() ) {
				foreach( array_keys( $this->mItems ) as $key ) {
// TODO Recersive delete needs another implementation
//					if( !empty($pRecursiveDelete) ) {
//						$this->mItems[$key]->expunge( $pRecursiveDelete );
//					} else
					if( is_a( $this->mItems[$key], '\Bitweaver\Stock\StockAssembly' ) ) {
						// make sure we have a valid content_id before we exec
						if( is_numeric( $this->mItems[$key]->mContentId ) ) {
							$query = "SELECT COUNT(`item_content_id`) AS `other_gallery`
									  FROM `".BIT_DB_PREFIX."stock_assembly_component_map`
									  WHERE `item_content_id`=? AND `assembly_content_id`!=?";
							if( !($inOtherGallery = $this->mDb->getOne($query, [ $this->mItems[$key]->mContentId, $this->mContentId ] )) ) {
								$this->mItems[$key]->expunge();
							}
						}
					}
				}
			}

			$this->mDb->getOne( "DELETE FROM `".BIT_DB_PREFIX."stock_assembly_component_map` WHERE `assembly_content_id`=?", [ $this->mContentId ] );
			$this->mDb->getOne( "DELETE FROM `".BIT_DB_PREFIX."stock_assembly_component_map` WHERE `item_content_id`=?", [ $this->mContentId ] );
			if( LibertyContent::expunge() ) {
				$this->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
				error_log( "Error expunging stock gallery: " . \Bitweaver\vc($this->mErrors ) );
			}
		}
		return true;
	}


	/**
	* Returns the layout of the gallery accounting for various defaults
	* @return string the layout string preference
	*/
	public function getLayout() {
		global $gBitSystem;
		return $this->getPreference( 'assembly_pagination', $gBitSystem->getConfig( 'default_assembly_pagination', STOCK_PAGINATION_FIXED_GRID ) );
	}

	public static function getAllLayouts() {
		return [
			STOCK_PAGINATION_FIXED_GRID      => 'Fixed Grid',
			STOCK_PAGINATION_AUTO_FLOW       => 'Auto-Flow',
			STOCK_PAGINATION_POSITION_NUMBER => 'Position Number',
			STOCK_PAGINATION_SIMPLE_LIST     => 'Simple List',
		];
	}

	/**
	* Returns include file that will setup the object for rendering
	* @return string the fully specified path to file to be included
	*/
	public function getRenderFile() {
		return STOCK_PKG_INCLUDE_PATH.'display_stock_assembly_inc.php';
	}

	/**
	* Returns template file used for display
	* @return string the fully specified path to file to be included
	*/
	public function getRenderTemplate() {
		return 'bitpackage:stock/view_assembly.tpl';
	}

	/**
	* Function that returns link to display a piece of content
	* @param array pAssemblyId id of gallery to link
	* @return string the url to display the gallery.
	*/
	public static function getDisplayUrlFromHash( &$pParamHash ) {
		$ret = '';
		global $gBitSystem;
		if( BitBase::verifyId( $pParamHash['content_id'] ?? 0 ) ) {
			$ret = STOCK_PKG_URL;
			$ret .= $gBitSystem->isFeatureActive( 'pretty_urls' )
				? 'assembly/'.$pParamHash['content_id']
				: 'view_assembly.php?content_id='.$pParamHash['content_id'];
		}
		return $ret;
	}

	public function getTree( $pListHash ) {
		global $gBitDb;

		$ret = [];
		if( $this->mDb->isAdvancedPostgresEnabled() ) {
			$bindVars = [];
			$containVars = [];
			$selectSql = '';
			$joinSql = '';
			$whereSql = '';
			if( !empty( $pListHash['contain_item'] ) ) {
				$selectSql = " , tfgim3.`item_content_id` AS `in_gallery` ";
				$joinSql .= " LEFT OUTER JOIN  `".BIT_DB_PREFIX."stock_assembly_component_map` tfgim3 ON (tfgim3.`assembly_content_id`=lc.`content_id`) AND tfgim3.`item_content_id`=? ";
				$bindVars[] = $pListHash['contain_item'];
				$containVars[] = $pListHash['contain_item'];
			}
			if( isset( $pListHash['contain_item'] ) ) {
				// contain item might have squeaked in as 0, clear our from pListHash
				unset( $pListHash['contain_item'] );
			}
			foreach( $pListHash as $key=>$val ) {
				$whereSql .= " $key=? AND ";
				$bindVars[] = $val;
			}

			$query =   "SELECT lc.`content_id` AS `hash_key`, lc.* $selectSql
						FROM `".BIT_DB_PREFIX."liberty_content` lc
							$joinSql
						WHERE lc.`content_type_guid` = '".STOCKASSEMBLY_CONTENT_TYPE_GUID."' AND $whereSql NOT EXISTS (SELECT assembly_content_id FROM stock_assembly_component_map tfgim2 WHERE tfgim2.item_content_id=lc.content_id)
						ORDER BY lc.title";
			$rootContent = $gBitDb->GetAssoc( $query, $bindVars );

			foreach( array_keys( $rootContent ) as $conId ) {
				$splitVars = [];
				$query = "SELECT branch AS hash_key, * $selectSql
						  FROM connectby('`".BIT_DB_PREFIX."stock_assembly_component_map`', '`item_content_id`', '`assembly_content_id`', ?, 0, '/') AS t(cb_item_content_id int,cb_assembly_content_id int, level int, branch text)
							INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON(lc.`content_id`=cb_item_content_id AND lc.`content_type_guid`='".STOCKASSEMBLY_CONTENT_TYPE_GUID."')
							$joinSql
						  ORDER BY branch, lc.`title`";
				$splitVars[] = $conId;
				if( !empty( $containVars ) ) {
					$splitVars[] = $containVars[0];
				}

				StockAssembly::splitConnectByTree( $ret, $gBitDb->GetAssoc( $query, $splitVars ) );
				StockAssembly::getTreeSort( $ret );
			}
		} else if ( $this->mDb->mType == 'firebird' || $this->mDb->mType == 'pdo' ) {
			$bindVars = [];
			$containVars = [];
			$selectSql = '';
			$joinSql = '';
			$whereSql = '';

			if( !empty( $pListHash['contain_item'] ) ) {
				$selectSql = " , tfgim3.`item_content_id` AS `in_gallery` ";
				$joinSql .= " LEFT OUTER JOIN  `".BIT_DB_PREFIX."stock_assembly_component_map` tfgim3 ON (tfgim3.`assembly_content_id`=lc.`content_id`) AND tfgim3.`item_content_id`=? ";
				$bindVars[] = $pListHash['contain_item'];
				$containVars[] = $pListHash['contain_item'];
			}
			$this->getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

			if( isset( $pListHash['contain_item'] ) ) {
				// contain item might have squeaked in as 0, clear our from pListHash
				unset( $pListHash['contain_item'] );
			}
			foreach( $pListHash as $key=>$val ) {
				$whereSql .= " AND lc.$key=? ";
				$bindVars[] = $val;
			}

			$splitVars = [];
					$query = "WITH RECURSIVE
								GALLERY_TREE AS (
								SELECT lcp.`content_id` AS assembly_content_id, lcp.`content_id` AS item_content_id, 0 AS BLEVEL, CAST( lcp.`title` AS VARCHAR(255) ) AS BRANCH, 0 AS gallery_parent_id
								FROM `".BIT_DB_PREFIX."liberty_content` lcp
								WHERE lcp.`content_type_guid` = '".STOCKASSEMBLY_CONTENT_TYPE_GUID."'
								AND NOT EXISTS (SELECT assembly_content_id FROM `".BIT_DB_PREFIX."stock_assembly_component_map` tfgim2 WHERE tfgim2.item_content_id=lcp.content_id)

								UNION ALL

								SELECT G1.`item_content_id` AS assembly_content_id, G1.`item_content_id`, G.BLEVEL + 1, G.BRANCH || '/' || G1.`item_content_id` AS BRANCH, G1.`assembly_content_id` AS gallery_parent_id
								FROM `".BIT_DB_PREFIX."stock_assembly_component_map` G1
								JOIN GALLERY_TREE G ON G1.`assembly_content_id` = G.`item_content_id`
								INNER JOIN `".BIT_DB_PREFIX."liberty_content` lcg1 ON(lcg1.`content_id`=G1.`item_content_id` AND lcg1.`content_type_guid` = '".STOCKASSEMBLY_CONTENT_TYPE_GUID."')
							)
							SELECT T.BRANCH AS hash_key, T.BLEVEL, lc.* $selectSql
							FROM GALLERY_TREE T
							INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON (lc.`content_id`=T.`item_content_id`)
							LEFT OUTER JOIN `".BIT_DB_PREFIX."stock_assembly_component_map` fgimo ON (fgimo.`assembly_content_id`=T.gallery_parent_id AND fgimo.`item_content_id`=T.assembly_content_id)
							$joinSql
							WHERE lc.`content_type_guid` = '".STOCKASSEMBLY_CONTENT_TYPE_GUID."' $whereSql
						  ORDER BY T.BRANCH, fgimo.`item_position`";

			if( !empty( $bindVars ) ) {
				StockAssembly::splitConnectByTree( $ret, $gBitDb->GetAssoc( $query, $bindVars ) );
			} else {
				StockAssembly::splitConnectByTree( $ret, $gBitDb->GetAssoc( $query ) );
			}

		} else {
// this needs replacing with a more suitable list query ...
			$pListHash['show_empty'] = true;
			$galList = $this->getList( $pListHash );
			// index by content_id
			foreach( $galList as $galId => $gal ) {
				$ret[$gal['content_id']] = $gal;
			}
			StockAssembly::splitConnectByTree( $ret, $ret );
			StockAssembly::getTreeSort( $ret );
		}
		return $ret;
	}

	public function getTreeSort( &$pTree ) {
		if( $pTree ) {
			foreach( array_keys( $pTree ) as $k ) {
				if( !empty( $pTree[$k]['children'] ) ) {
					StockAssembly::getTreeSort( $pTree[$k]['children'] );
				}
			}
			uasort( $pTree, [ '\Bitweaver\Stock\StockAssembly', 'getTreeSortCmp' ] );
		}
	}

	public static function getTreeSortCmp( $a, $b ) {
		return strcmp( $a['content']['title'], $b['content']['title'] );
	}

	public function splitConnectByTree( &$pRet, $pTreeHash ) {
		if( $pTreeHash ) {
			foreach( array_keys( $pTreeHash ) as $conId ) {
				$path = explode( '/', $conId );
				StockAssembly::recurseConnectByPath( $pRet, $pTreeHash[$conId], $path );
			}
		}
	}

	public function recurseConnectByPath( &$pRet, $pTreeHash, $pPath ) {
		$popId = array_shift( $pPath );
		if( count( $pPath ) > 0 ) {
			if( empty( $pRet[$popId]['children'] ) ) {
				$pRet[$popId]['children'] = [];
			}
			StockAssembly::recurseConnectByPath( $pRet[$popId]['children'], $pTreeHash, $pPath );
		} else {

			$pRet[$popId]['content'] = $pTreeHash;
		}
	}

	// Generate a nested ul list of listed galleries
	public function generateList( $pListHash, $pOptions, $pLocate = false ) {
		$ret = '';
		if( $hash = StockAssembly::getTree( $pListHash ) ) {

			$class = ' structure-toc';
			$ret = "<ul ";
			foreach( [ 'class', 'name', 'id', 'onchange' ] as $key ) {
				if( !empty( $pOptions[$key] ) ) {
					if( $key == 'class' ) {
						$class .= ' '.$pOptions[$key];
					} else {
						$ret .= " $key=\"$pOptions[$key]\" ";
					}
				}
			}
			$ret .= ' class="'.$class.'">';
			$ret .= self::generateListItems( $hash, $pOptions, $pLocate );
			$ret .= "</ul>";
		}
		return $ret;
	}

	// Helper method for generateMenu. See that method. Is Recursive
	public function generateListItems( &$pHash, $pOptions, $pLocate ) {
		$ret = '';
		foreach( array_keys( $pHash ) as $conId ) {
			$class = !empty( $pOptions['radio_checkbox'] ) ? 'checkbox' : '';
			$ret .= '<li id="stockassembly'.$pHash[$conId]['content']['content_id'].'" content_id="'.$pHash[$conId]['content']['content_id'].'" ';
			if( !empty( $pOptions['item_attributes'] ) ) {
				foreach( $pOptions['item_attributes'] as $key=>$value ) {
					if( $key == 'class' ) {
						$class .= ' '.$value;
					} else {
						$ret .= " $key=\"$value\" ";
					}
				}
			}
			$ret .= ' class="'.$class.'"><label>';
			if ( $pLocate || $pHash[$conId]['content']['content_id'] != $this->mContentId ) {
				if( !empty( $pOptions['radio_checkbox'] ) ) {
					$ret .= '<input type="checkbox" name="gallery_additions[]" value="'.$pHash[$conId]['content']['content_id'].'" ';
					if( !empty( $pHash[$conId]['content']['in_gallery'] ) || $pHash[$conId]['content']['content_id'] == $this->mContentId ) {
						$ret .=	' checked="checked" ';
					}
					$ret .= '/>';
				}
			}
			if ( $pHash[$conId]['content']['content_id'] == $this->mContentId
				or ( isset( $pHash[$conId]['content']['in_gallery'] ) and $pHash[$conId]['content']['in_gallery'] ) ) {
				$ret .= '<span class="active">'.htmlspecialchars( $pHash[$conId]['content']['title'] ).'</span>';
			} else {
				$ret .= htmlspecialchars( $pHash[$conId]['content']['title'] );
			}
			$ret .= '</label></li>';
			if( !empty( $pHash[$conId]['children'] ) ) {
				$ret .= '<li><ul>'.StockAssembly::generateListItems( $pHash[$conId]['children'], $pOptions, $pLocate ).'</ul></li>';
			}
		}
		return $ret;
	}

	// Generate a select drop menu of listed galleries
	public function generateMenu( $pListHash, $pOptions, $pLocate=null ) {
		$ret = "<select class='form-control' ";
		foreach( [ 'class', 'name', 'id', 'onchange' ] as $key ) {
			if( !empty( $pOptions[$key] ) ) {
				$ret .= " $key=\"$pOptions[$key]\" ";
			}
		}
		$ret .= ">";
		$ret .= !empty( $pOptions['first_option'] ) ? $pOptions['first_option'] : '';
		if( $hash = StockAssembly::getTree( $pListHash ) ) {
			$ret .= StockAssembly::generateMenuOptions( $hash, $pOptions, $pLocate );
		}
		$ret .= "</select>";
		return $ret;
	}

	// Helper method for generateMenu. See that method. Is Recursive
	public function generateMenuOptions( &$pHash, $pOptions, $pLocate, $pPrefix='' ) {
		$ret = '';
		foreach( array_keys( $pHash ) as $conId ) {
			$ret .= '<option content_id="'.$pHash[$conId]['content']['content_id'].'" value="'.$pHash[$conId]['content']['content_id'].'"';
			if( !empty( $pOptions['item_attributes'] ) ) {
				foreach( $pOptions['item_attributes'] as $key=>$value ) {
					$ret .= " $key=\"$value\" ";
				}
			}
			if ( $pLocate && $pLocate == $pHash[$conId]['content']['content_id'] ) {
				$ret .=	' selected="selected" ';
			}
			$ret .= ' >'.($pPrefix?$pPrefix.'&raquo; ':'').htmlspecialchars( $pHash[$conId]['content']['title'] ).'</option>';

			if( !empty( $pHash[$conId]['children'] ) ) {
				$ret .= StockAssembly::generateMenuOptions( $pHash[$conId]['children'], $pOptions, $pLocate, $pPrefix.'-' );
			}
		}
		return $ret;
	}

	public function getList( &$pListHash ) {
		global $gBitUser,$gBitSystem, $gBitDbType;

		$pListHash['valid_sort_modes'] = [ 'real_name', 'login', 'hits', 'title', 'created', 'last_modified', 'last_hit', 'event_time', 'ip' ];

		LibertyContent::prepGetList( $pListHash );
		$bindVars = [];
		$selectSql = $joinSql = $whereSql = $sortSql = '';

		if( $gBitDbType == 'mysql' ) {
			// loser mysql without subselects
			if( !empty( $pListHash['root_only'] ) ) {
				$joinSql .= " LEFT OUTER JOIN  `".BIT_DB_PREFIX."stock_assembly_component_map` tfgim2 ON (tfgim2.`item_content_id`=lc.`content_id`)";
				$whereSql .= ' AND tfgim2.`item_content_id` IS null ';
			}
		}

		if( !empty( $pListHash['contain_item'] ) ) {
			$selectSql = " , tfgim3.`item_content_id` AS `in_gallery` ";
			$joinSql .= " LEFT OUTER JOIN  `".BIT_DB_PREFIX."stock_assembly_component_map` tfgim3 ON (tfgim3.`assembly_content_id`=lc.`content_id`) AND tfgim3.`item_content_id`=? ";
			$bindVars[] = $pListHash['contain_item'];
		}

		if( @$this->verifyId( $pListHash['user_id'] ?? 0 ) ) {
			$whereSql .= " AND lc.`user_id` = ? ";
			$bindVars[] = (int)$pListHash['user_id'];
		}

		if( !empty( $pListHash['find'] ) ) {
			$term = '%'.strtoupper( $pListHash['find'] ).'%';
			$whereSql .= " AND (UPPER(lc.`title`) LIKE ? OR UPPER(lc.`data`) LIKE ?) ";
			$bindVars[] = $term;
			$bindVars[] = $term;
		}

		if( !empty( $pListHash['parent_content_id'] ) ) {
			if( $gBitDbType != 'mysql' ) {
				$whereSql .= " AND EXISTS (SELECT 1 FROM `".BIT_DB_PREFIX."stock_assembly_component_map` sacm WHERE sacm.`assembly_content_id`=? AND sacm.`item_content_id`=lc.`content_id`)";
			} else {
				$joinSql .= " INNER JOIN `".BIT_DB_PREFIX."stock_assembly_component_map` sacmp ON sacmp.`item_content_id`=lc.`content_id`";
				$whereSql .= " AND sacmp.`assembly_content_id`=?";
			}
			$bindVars[] = (int)$pListHash['parent_content_id'];
		}

		if( !empty( $pListHash['show_public'] ) ) {
			$joinSql .= " LEFT OUTER JOIN  `".BIT_DB_PREFIX."liberty_content_prefs` lcp ON( lcp.`content_id`=lc.`content_id` )";
			$whereSql .= " OR  ( lcp.`pref_name`=? AND lcp.`pref_value`=? ) ";
			$bindVars[] = 'is_public';
			$bindVars[] = 'y';
		}

		$whereSql .= " AND lc.`content_type_guid` = '".STOCKASSEMBLY_CONTENT_TYPE_GUID."'";

		$mapJoin = "";
		if( $gBitDbType != 'mysql' ) {
			// weed out empty galleries if we don't need them. DO NOT get clever and change the IN and EXISTS choices here.
			if( empty( $pListHash['show_empty'] ) ) {
				$whereSql .= " AND lc.`content_id` IN (SELECT `assembly_content_id` FROM `".BIT_DB_PREFIX."stock_assembly_component_map` fgim WHERE fgim.`assembly_content_id`=lc.`content_id`)";
			}
			if( !empty( $pListHash['root_only'] ) ) {
				$whereSql .= " AND NOT EXISTS (SELECT `assembly_content_id` FROM `".BIT_DB_PREFIX."stock_assembly_component_map` tfgim2 WHERE tfgim2.`item_content_id`=lc.`content_id`)";
			}
		} else {
			// weed out empty galleries if we don't need them
			if( empty( $pListHash['show_empty'] ) ) {
				$mapJoin = "INNER JOIN `".BIT_DB_PREFIX."stock_assembly_component_map` fgim ON (fgim.`assembly_content_id`=lc.`content_id`)";
			}
		}

		if ( !empty( $pListHash['sort_mode'] ) ) {
			//converted in prepGetList()
			$sortSql .= " ORDER BY ".$this->mDb->convertSortmode( $pListHash['sort_mode'] )." ";
		}
		$selectSql .= ", (SELECT COUNT(*) FROM `".BIT_DB_PREFIX."stock_assembly_component_map` sacmc WHERE sacmc.`assembly_content_id` = lc.`content_id`) AS `child_count`";

		// Putting in the below hack because mssql cannot select distinct on a text blob column.
		$selectSql .= $gBitDbType == 'mssql' ? " ,CAST(lc.`data` AS VARCHAR(250)) as `data` " : " ,lc.`data` ";

		$this->getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

		if( !empty( $whereSql ) ) {
			$whereSql = substr_replace( $whereSql, ' WHERE ', 0, 4 );
		}

		$query = "SELECT lc.`content_id`,
					lc.`user_id`, lc.`modifier_user_id`, lc.`created`, lc.`last_modified`,
					lc.`content_type_guid`, lc.`format_guid`, lch.`hits`, lch.`last_hit`, lc.`event_time`, lc.`version`,
					lc.`lang_code`, lc.`title`, lc.`ip`, uu.`login`, uu.`real_name`
					$selectSql
				FROM `".BIT_DB_PREFIX."liberty_content` lc
					INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON (uu.`user_id` = lc.`user_id`)
					LEFT JOIN `".BIT_DB_PREFIX."liberty_content_hits` lch ON (lch.`content_id` = lc.`content_id`)
					$mapJoin $joinSql
				$whereSql $sortSql";
			$data = [];
			if( $rows = $this->mDb->query( $query, $bindVars, $pListHash['max_records'], $pListHash['offset'] ) ) {
				foreach( $rows as $row ) {
					$data[$row['content_id']] = $row;
				}
			}
			if( !empty( $data ) ) {
				$thumbsize = !empty( $pListHash['thumbnail_size'] ) ? $pListHash['thumbnail_size'] : 'small';
				foreach( array_keys( $data ) as $assemblyId ) {
					$data[$assemblyId]['display_url'] = static::getDisplayUrlFromHash( $data[$assemblyId] );
					$data[$assemblyId]['display_uri'] = static::getDisplayUriFromHash( $data[$assemblyId] );
					if( empty( $pListHash['no_thumbnails'] ) ) {
						if( $thumbImage = $this->getThumbnailImage( $data[$assemblyId]['content_id'] ) ) {
							$data[$assemblyId]['thumbnail_url'] = $thumbImage->getThumbnailUrl( $thumbsize );
							$data[$assemblyId]['thumbnail_uri'] = $thumbImage->getThumbnailUri( $thumbsize );
						} elseif( !empty( $pListHash['show_empty'] ) ) {
							$data[$assemblyId]['thumbnail_url'] = STOCK_PKG_URL.'image/no_image.png';
						} else {
							unset( $data[$assemblyId] );
						}
					}
				}
			}

		// count galleries
		$query_c = "SELECT COUNT( lc.`content_id` )
					FROM `".BIT_DB_PREFIX."liberty_content` lc
						INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON (uu.`user_id` = lc.`user_id`)
				$mapJoin $joinSql
				$whereSql";
		$cant = $this->mDb->getOne( $query_c, $bindVars );

		// add all pagination info to $ret
		$pListHash['cant'] = $cant;
		LibertyContent::postGetList( $pListHash );
		return $data;
	}

	public static function getServiceIcon() {
		return '<i class="fa fal fa-camera"></i>';
	}

	public static function getServiceKey() {
		return 'stock';
	}
}
