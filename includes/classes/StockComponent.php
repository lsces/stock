<?php
/**
 * A single stock component — a physical part, material, or consumable.
 *
 * Stored as a pure liberty_content record (content_type_guid='stockcomponent').
 * Components are linked into assemblies via stock_assembly_map and carry quantity
 * type xrefs (SGL/PCK/SHT/VOL) used by BOM and movement calculations.
 *
 * @package stock
 */
namespace Bitweaver\Stock;

use Bitweaver\Liberty\LibertyContent;
use Bitweaver\BitBase;

class StockComponent extends StockBase {
	protected $mXrefTypeKey = 'stockcomponent_types';

	/**
	 * @param int|null $pComponentId  Legacy param — use $pContentId instead.
	 * @param int|null $pContentId    liberty_content.content_id to load.
	 */
	public function __construct($pComponentId = null, $pContentId = null) {
		parent::__construct();
		$this->mContentTypeGuid = STOCKCOMPONENT_CONTENT_TYPE_GUID;
		$this->mContentId = (int)($pContentId ?? $pComponentId);

		$this->registerContentType(
			STOCKCOMPONENT_CONTENT_TYPE_GUID, [
				'content_type_guid' => STOCKCOMPONENT_CONTENT_TYPE_GUID,
				'content_name' => 'Component',
				'content_name_plural' => 'Components',
				'handler_class' => 'StockComponent',
				'handler_package' => 'stock',
				'handler_file' => 'StockComponent.php',
				'maintainer_url' => 'https://www.bitweaver.org',
		], );

		// Permission setup
		$this->mViewContentPerm   = 'p_stock_view';
		$this->mCreateContentPerm = 'p_stock_create';
		$this->mUpdateContentPerm = 'p_stock_update';
		$this->mAdminContentPerm  = 'p_stock_admin';
	}

	/**
	 * @param  array $pLookupHash  Must contain 'content_id'.
	 * @return static|null         Loaded object, or null if not found.
	 */
	public static function lookup( $pLookupHash ) {
		$ret = null;
		$lookupContentId = null;
		if( !empty($pLookupHash['content_id']) && is_numeric($pLookupHash['content_id']) ) {
			$lookupContentId = (int)$pLookupHash['content_id'];
		}
		if( static::verifyId( $lookupContentId ) ) {
			$ret = static::getLibertyObject( $lookupContentId, STOCKCOMPONENT_CONTENT_TYPE_GUID );
		}
		return $ret;
	}

	/**
	 * Load component data into $this->mInfo from liberty_content.
	 *
	 * @return int|null  Row count (> 0) on success, or null if mContentId is not set.
	 */
	public function load() {
		if( $this->isValid() ) {
			$selectSql = $joinSql = $whereSql = '';
			$bindVars = [];

			$whereSql = " WHERE lc.`content_id` = ? AND lc.`content_type_guid` = '".STOCKCOMPONENT_CONTENT_TYPE_GUID."'";
			$bindVars[] = $this->mContentId;

			$this->getServicesSql( 'content_load_sql_function', $selectSql, $joinSql, $whereSql, $bindVars, $this );

			$X = BIT_DB_PREFIX;
			$sql = "SELECT lc.* $selectSql
						, uue.`login` AS `modifier_user`, uue.`real_name` AS `modifier_real_name`
						, uuc.`login` AS `creator_user`, uuc.`real_name` AS `creator_real_name`
						, (SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x WHERE x.`content_id` = lc.`content_id` AND x.`item` = 'KLID') AS `klid`
					FROM `".BIT_DB_PREFIX."liberty_content` lc
						LEFT JOIN `".BIT_DB_PREFIX."users_users` uue ON (uue.`user_id` = lc.`modifier_user_id`)
						LEFT JOIN `".BIT_DB_PREFIX."users_users` uuc ON (uuc.`user_id` = lc.`user_id`) $joinSql
					$whereSql";
			if( $this->mInfo = $this->mDb->getRow( $sql, $bindVars ) ) {
				$this->mContentId       = $this->mInfo['content_id'];
				$this->mContentTypeGuid = $this->mInfo['content_type_guid'];
				$this->mInfo['creator'] = $this->mInfo['creator_real_name'] ?? $this->mInfo['creator_user'];
				$this->mInfo['editor']  = $this->mInfo['modifier_real_name'] ?? $this->mInfo['modifier_user'];
				LibertyContent::load();
			}
			return count( $this->mInfo );
		}
		return null;
	}

	/**
	 * Validate $pParamHash before storing — requires a non-empty title.
	 *
	 * @param  array $pParamHash  Data to validate; modified in place.
	 * @return bool
	 */
	protected function verifyComponentData( array &$pParamHash ): bool {
		$pParamHash['content_type_guid'] = STOCKCOMPONENT_CONTENT_TYPE_GUID;
		if( $this->isValid() ) {
			$pParamHash['content_id'] = $this->mContentId;
		}
		if( empty( $pParamHash['title'] ) ) {
			$this->mErrors['title'] = 'A title is required.';
		}
		return count( $this->mErrors ) == 0;
	}

	/**
	 * Persist component data inside a transaction via LibertyContent::store().
	 *
	 * @param  array $pParamHash  Data to persist; modified in place.
	 * @return bool
	 */
	public function store( array &$pParamHash ): bool {
		if( $this->verifyComponentData( $pParamHash ) ) {
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
	 * Delete the component, removing it from all assemblies first.
	 *
	 * @return bool Always TRUE (errors are recorded in $this->mErrors).
	 */
	public function expunge(): bool {
		if( $this->isValid() ) {
			$this->StartTrans();
			$this->mDb->getOne( "DELETE FROM `".BIT_DB_PREFIX."stock_assembly_map` WHERE `item_content_id` = ?", [ $this->mContentId ] );
			if( LibertyContent::expunge() ) {
				$this->CompleteTrans();
				$this->mContentId = null;
			} else {
				$this->mDb->RollbackTrans();
			}
		}
		return true;
	}

	/** @return bool TRUE when mContentId is a valid positive integer. */
	public function isValid() {
		return @$this->verifyId( $this->mContentId );
	}

	/**
	 * Return a paged, keyed list of components.
	 *
	 * Recognised filter keys: user_id, assembly_content_id, search, sort_mode.
	 * Sets $pListHash['cant'] on return.
	 *
	 * @param  array $pListHash  Filter and pagination params; modified in place.
	 * @return array             content_id-keyed result rows.
	 */
	public function getList( &$pListHash ) {
		global $gBitUser, $gBitSystem;

		LibertyContent::prepGetList( $pListHash );
		$ret = $bindVars = [];
		$selectSql = $whereSql = $joinSql = '';

		$whereSql .= " AND lc.`content_type_guid` = '".STOCKCOMPONENT_CONTENT_TYPE_GUID."'";

		if( @$this->verifyId( $pListHash['user_id'] ?? 0 ) ) {
			$whereSql .= " AND lc.`user_id` = ? ";
			$bindVars[] = (int)$pListHash['user_id'];
		}

		if( @$this->verifyId( $pListHash['assembly_content_id'] ?? 0 ) ) {
			$whereSql .= " AND tfgim2.`assembly_content_id` = ? ";
			$bindVars[] = (int)$pListHash['assembly_content_id'];
		}

		if( !empty( $pListHash['search'] ) ) {
			$term = '%'.strtoupper( $pListHash['search'] ).'%';
			$whereSql .= " AND (UPPER(lc.`title`) LIKE ? OR UPPER(lc.`data`) LIKE ?) ";
			$bindVars[] = $term;
			$bindVars[] = $term;
		}

		if( !empty( $pListHash['kitlocker_only'] ) ) {
			$whereSql .= " AND EXISTS (SELECT 1 FROM `".BIT_DB_PREFIX."liberty_xref` kx WHERE kx.`content_id` = lc.`content_id` AND kx.`item` = 'KLID')";
		}

		if( !empty( $pListHash['hide_kitlocker'] ) ) {
			// Exclude components that have KLID but no #SUP linking to the kitlocker contact (SCREF='kitlocker').
			// Components where kitlocker is the supplier are kept — those are parts kitlocker supplies to elves.
			$whereSql .= " AND NOT (
				EXISTS (
					SELECT 1 FROM `".BIT_DB_PREFIX."liberty_xref` kx
					WHERE kx.`content_id` = lc.`content_id` AND kx.`item` = 'KLID'
				)
				AND NOT EXISTS (
					SELECT 1 FROM `".BIT_DB_PREFIX."liberty_xref` sx
					INNER JOIN `".BIT_DB_PREFIX."liberty_xref` scref
						ON scref.`content_id` = sx.`xref`
						AND scref.`item` = 'SCREF'
						AND UPPER(scref.`xkey`) = 'KITLOCKER'
					WHERE sx.`content_id` = lc.`content_id` AND sx.`item` = '#SUP'
				)
			)";
		}

		$this->getServicesSql( 'content_list_sql_function', $selectSql, $joinSql, $whereSql, $bindVars );

		$X = BIT_DB_PREFIX;
		$selectSql .= ", (SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x WHERE x.`content_id` = lc.`content_id` AND x.`item` = 'KLID') AS `klid`";

		$orderby = !empty( $pListHash['sort_mode'] )
			? " ORDER BY ".$this->mDb->convertSortmode( $pListHash['sort_mode'] )
			: '';

		if( !empty( $whereSql ) ) {
			$whereSql = substr_replace( $whereSql, ' WHERE ', 0, 4 );
		}

		$pListHash['cant'] = (int)$this->mDb->getOne(
			"SELECT COUNT(DISTINCT lc.`content_id`)
			 FROM `".BIT_DB_PREFIX."liberty_content` lc
				INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON (uu.`user_id` = lc.`user_id`) $joinSql
				LEFT OUTER JOIN `".BIT_DB_PREFIX."stock_assembly_map` tfgim2 ON (tfgim2.`item_content_id`=lc.`content_id`)
			$whereSql",
			$bindVars
		);

		$query = "SELECT lc.`content_id` AS `hash_key`, lc.*, uu.`login`, uu.`real_name` $selectSql
				FROM `".BIT_DB_PREFIX."liberty_content` lc
					INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON (uu.`user_id` = lc.`user_id`) $joinSql
					LEFT OUTER JOIN `".BIT_DB_PREFIX."stock_assembly_map` tfgim2 ON (tfgim2.`item_content_id`=lc.`content_id`)
				$whereSql $orderby";
		if( $rows = $this->mDb->query( $query, $bindVars, $pListHash['max_records'], $pListHash['offset'] ) ) {
			foreach( $rows as $row ) {
				$row['display_url']    = static::getDisplayUrlFromHash( $row );
				$row['title']          = static::getTitleFromHash( $row );
				$ret[$row['hash_key']] = $row;
			}
		}
		LibertyContent::postGetList( $pListHash );
		return $ret;
	}

	/** @return string  Smarty bitpackage: path to the component view template. */
	public function getRenderTemplate() {
		return 'bitpackage:stock/view_component.tpl';
	}

	/**
	 * @param  array $pParamHash  Must contain 'content_id'.
	 * @return string             URL to view_component.php (or pretty URL).
	 */
	public static function getDisplayUrlFromHash( &$pParamHash ) {
		global $gBitSystem;
		$ret = '';
		if( BitBase::verifyId( $pParamHash['content_id'] ?? 0 ) ) {
			$ret = STOCK_PKG_URL;
			$ret .= $gBitSystem->isFeatureActive( 'pretty_urls' )
				? 'component/'.$pParamHash['content_id']
				: 'view_component.php?content_id='.$pParamHash['content_id'];
		}
		return $ret;
	}

	/** @return string  Display URL for this component. */
	public function getDisplayUrl() {
		return static::getDisplayUrlFromHash( $this->mInfo );
	}

	/** @return string  URL to edit_component.php for this component. */
	public function getEditUrl( $pContentId = null, $pMixed = null ): string {
		if( $this->verifyId( $this->mContentId ) ) {
			return STOCK_PKG_URL.'edit_component.php?content_id='.$this->mContentId;
		}
		return STOCK_PKG_URL.'edit_component.php';
	}

	/**
	 * @param  array  $pParamHash  Must contain 'content_id'; used to build the URL.
	 * @param  string $pTitle      Link text; falls back to getTitleFromHash() if empty.
	 * @param  null   $pAnchor    Unused.
	 * @return string             HTML anchor, or plain-text title if stock is inactive.
	 */
	public static function getDisplayLinkFromHash( &$pParamHash, $pTitle='', $pAnchor=null ) {
		global $gBitSystem;
		$pTitle = trim( $pTitle );
		if( empty( $pTitle ) ) {
			$pTitle = static::getTitleFromHash( $pParamHash );
		}
		if( $gBitSystem->isPackageActive( 'stock' ) ) {
			return '<a title="'.htmlspecialchars($pTitle).'" href="'.static::getDisplayUrlFromHash( $pParamHash ).'">'.htmlspecialchars($pTitle).'</a>';
		}
		return htmlspecialchars( $pTitle );
	}

	/**
	 * @param  array $pHash     mInfo-style hash; must contain 'content_type_guid'.
	 * @param  bool  $pDefault  When TRUE, falls back to content type name + content_id.
	 * @return string
	 */
	public static function getTitleFromHash( &$pHash, $pDefault=true ) {
		if( !empty( $pHash ) ) {
			$ret = trim( parent::getTitleFromHash( $pHash, $pDefault ) );
			if( empty( $ret ) && $pDefault ) {
				global $gLibertySystem;
				$ret = $gLibertySystem->getContentTypeName( $pHash['content_type_guid'] ?? 'empty' );
				if( !empty( $pHash['content_id'] ) ) {
					$ret .= ' '.$pHash['content_id'];
				}
			}
			if( !empty( $pHash['klid'] ) ) {
				$ret .= ' ('.$pHash['klid'].')';
			}
			return $ret;
		}
		return 'empty_component';
	}

	/** @return string|null  Component title, or null if not yet loaded. */
	public function getTitle() {
		return $this->isValid() ? static::getTitleFromHash( $this->mInfo ) : null;
	}

	/** @return bool  TRUE if the first parent assembly allows comments. */
	public function isCommentable() {
		global $gGallery;
		if( is_object( $gGallery ) ) {
			return $gGallery->isCommentable();
		}
		$ret = false;
		if( $parents = $this->getParentAssemblies() ) {
			$gal = current( $parents );
			$ret = $this->mDb->getOne(
				"SELECT `pref_value` FROM `".BIT_DB_PREFIX."liberty_content_prefs` WHERE `content_id` = ? AND `pref_name` = ?",
				[ $gal['content_id'], 'allow_comments' ]
			) == 'y';
		}
		return $ret;
	}

	/** @return string Always 'stock'. */
	public static function getServiceKey() {
		return 'stock';
	}
}
