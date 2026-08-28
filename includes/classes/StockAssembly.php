<?php
/**
 * A BOM / kit / assembly — a named group of components with quantities.
 *
 * Stored as a pure liberty_content record (content_type_guid='stockassembly').
 * BOM quantities live in liberty_xref (x_group='quantity', items SGL/PRT/SHT/VOL) —
 * that's the only component/assembly relationship this class deals with.
 *
 * @package stock
 */
namespace Bitweaver\Stock;

use Bitweaver\BitBase;
use Bitweaver\Liberty\LibertyContent;

define('STOCKASSEMBLY_CONTENT_TYPE_GUID', 'stockassembly');


#[\AllowDynamicProperties]
class StockAssembly extends StockBase {
	protected $mXrefTypeKey = 'stockassembly_types';

	/**
	 * @param int|null $pAssemblyId  Legacy param — use $pContentId instead.
	 * @param int|null $pContentId   liberty_content.content_id to load.
	 */
	public function __construct($pAssemblyId = null, $pContentId = null) {
		parent::__construct();
		$this->mContentTypeGuid = STOCKASSEMBLY_CONTENT_TYPE_GUID;
		$pContentId = $pContentId ?? $pAssemblyId;
		if( $this->verifyId( $pContentId ) ) {
			$this->mContentId = (int)$pContentId;
		}
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

	/**
	 * @return bool TRUE when mContentId refers to a real liberty_content row of this
	 *              content type — not just an id that looks syntactically valid.
	 */
	public function isValid() {
		if( !@$this->verifyId( $this->mContentId ) ) {
			return false;
		}
		return (bool)$this->mDb->getOne(
			"SELECT 1 FROM `".BIT_DB_PREFIX."liberty_content` WHERE `content_id` = ? AND `content_type_guid` = ?",
			[ $this->mContentId, STOCKASSEMBLY_CONTENT_TYPE_GUID ]
		);
	}

	/**
	 * Enrich a BOM xref row with component title, description, and pack size.
	 *
	 * Calls parent for supplier enrichment, then adds xref_title, xref_data,
	 * part_size, and part_size_ext from the linked component's liberty_content + PCK xref.
	 *
	 * @param array $pXrefInfo  Xref display row; modified in place.
	 */
	public function enrichXrefDisplay( array &$pXrefInfo ): void {
		parent::enrichXrefDisplay( $pXrefInfo );
		if( !empty( $pXrefInfo['xref'] ) ) {
			if( $comp = $this->mDb->getRow(
				"SELECT lc.`title`, lc.`data`, pck.`xkey` AS `part_size`, pck.`xkey_ext` AS `part_size_ext`
				 FROM `".BIT_DB_PREFIX."liberty_content` lc
				 LEFT JOIN `".BIT_DB_PREFIX."liberty_xref` pck ON pck.`content_id` = lc.`content_id` AND pck.`item` = 'PRT'
				 WHERE lc.`content_id` = ?",
				[ (int)$pXrefInfo['xref'] ]
			) ) {
				$pXrefInfo['xref_title'] = $comp['title'];
				$pXrefInfo['xref_data']  = $comp['data'];
				$pXrefInfo['part_size']     = $comp['part_size'];
				$pXrefInfo['part_size_ext'] = $comp['part_size_ext'];
			}
		}
	}

	/**
	 * Load xref groups then enrich the 'quantity' BOM group — sorts by xorder and
	 * resolves each component content_id to title, description, and pack size.
	 */
	public function loadXrefInfo(): void {
		parent::loadXrefInfo();
		if( empty( $this->mXrefInfo ) ) return;
		$bomGroup = $this->mXrefInfo->mGroups['quantity'] ?? null;
		if( !$bomGroup || empty( $bomGroup->mXrefs ) ) return;
		usort( $bomGroup->mXrefs, fn($a,$b) => ( $a['xorder'] <=> $b['xorder'] ) ?: strcmp( $a['item'], $b['item'] ) );
		// title and data come from linked_title/linked_data (lc_linked JOIN in loadContent)
		// only need a separate query for part_size/part_size_ext from the PRT xref
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
	 * @param  array $pLookupHash       Must contain 'content_id'.
	 * @param  bool  $pLoadFromCache    Whether to use LibertyContent's object cache.
	 * @return static|null              Loaded object, or null if not found.
	 */
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

	/**
	 * Load assembly record into $this->mInfo, including pagination config and component count.
	 *
	 * @param  int|null   $pContentId    Unused; mContentId must be set before calling.
	 * @param  array|null $pPluginParams Unused.
	 * @return bool        TRUE on success, FALSE if no record found or mContentId invalid.
	 */
	public function load( $pContentId = null, $pPluginParams = null ) {
		global $gBitSystem;
		$bindVars = [];
		$selectSql = $joinSql = $whereSql = '';

		if( !$this->verifyId( $this->mContentId ) ) {
			return false;
		}

		$whereSql = " WHERE lc.`content_id` = ? AND lc.`content_type_guid` = '".STOCKASSEMBLY_CONTENT_TYPE_GUID."'";
		$bindVars = [ $this->mContentId ];

		$this->getServicesSql( 'content_load_sql_function', $selectSql, $joinSql, $whereSql, $bindVars, $this );

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

			$this->mInfo['creator'] = $rs['creator_real_name'] ?? $rs['creator_user'];
			$this->mInfo['editor']  = $rs['modifier_real_name'] ?? $rs['modifier_user'];

			$this->mInfo['access_answer'] = '';
		}

		return !empty( $this->mInfo );
	}

	/**
	 * Validate $pParamHash before storing — requires a non-empty title.
	 *
	 * @param  array $pParamHash  Modified in place to set content_type_guid.
	 * @return bool
	 */
	public function verifyGalleryData(&$pParamHash) {
		if( empty($pParamHash['title']) ) {
			$this->mErrors[] = "You must specify a title for this assembly";
		}
		$pParamHash['content_type_guid'] = $this->getContentType();
		return count($this->mErrors) == 0;
	}

	/**
	 * Persist assembly data inside a transaction via LibertyContent::store().
	 *
	 * @param  array $pParamHash  Data to persist; modified in place.
	 * @return bool
	 */
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

	/**
	 * Delete this assembly.
	 *
	 * @return bool Always TRUE (errors recorded in $this->mErrors).
	 */
	public function expunge(): bool {
		if( $this->isValid() ) {
			$this->StartTrans();
			if( LibertyContent::expunge() ) {
				$this->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
				error_log( "Error expunging stock gallery: " . \Bitweaver\vc($this->mErrors ) );
			}
		}
		return true;
	}


	/** @return string  Absolute path to the display_stock_assembly_inc.php setup file. */
	public function getRenderFile() {
		return STOCK_PKG_INCLUDE_PATH.'display_stock_assembly_inc.php';
	}

	/** @return string  Smarty bitpackage: path to the assembly view template. */
	public function getRenderTemplate() {
		return 'bitpackage:stock/view_assembly.tpl';
	}

	/** @return string  URL to edit_assembly.php for this assembly. */
	public function getEditUrl( $pContentId = null, $pMixed = null ): string {
		if( $this->verifyId( $this->mContentId ) ) {
			return STOCK_PKG_URL.'edit_assembly.php?content_id='.$this->mContentId;
		}
		return STOCK_PKG_URL.'edit_assembly.php';
	}

	/**
	 * @param  array $pParamHash  Must contain 'content_id'.
	 * @return string             URL to view_assembly.php (or pretty URL).
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

	/**
	 * Return a paged, keyed list of assemblies.
	 *
	 * Recognised filter keys: user_id, find, show_public, sort_mode, stgrp.
	 * Sets $pListHash['cant'] on return.
	 *
	 * @param  array $pListHash  Filter and pagination params; modified in place.
	 * @return array             content_id-keyed result rows.
	 */
	public function getList( &$pListHash ) {
		global $gBitUser,$gBitSystem, $gBitDbType;

		$pListHash['valid_sort_modes'] = [ 'real_name', 'login', 'hits', 'title', 'created', 'last_modified', 'last_hit', 'event_time', 'ip' ];

		LibertyContent::prepGetList( $pListHash );
		$bindVars = [];
		$selectSql = $joinSql = $whereSql = $sortSql = '';

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

		if( !empty( $pListHash['show_public'] ) ) {
			$joinSql .= " LEFT OUTER JOIN  `".BIT_DB_PREFIX."liberty_content_prefs` lcp ON( lcp.`content_id`=lc.`content_id` )";
			$whereSql .= " OR  ( lcp.`pref_name`=? AND lcp.`pref_value`=? ) ";
			$bindVars[] = 'is_public';
			$bindVars[] = 'y';
		}

		$whereSql .= " AND lc.`content_type_guid` = '".STOCKASSEMBLY_CONTENT_TYPE_GUID."'";

		if ( !empty( $pListHash['sort_mode'] ) ) {
			//converted in prepGetList()
			$sortSql .= " ORDER BY ".$this->mDb->convertSortmode( $pListHash['sort_mode'] )." ";
		}
		$X = BIT_DB_PREFIX;
		$selectSql .= ", (SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x WHERE x.`content_id` = lc.`content_id` AND x.`item` = '#SUP' ORDER BY x.`xorder`) AS `part_number`";
		$selectSql .= ", (SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x WHERE x.`content_id` = lc.`content_id` AND x.`item` = 'KLID') AS `klid`";
		$selectSql .= ", (SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x WHERE x.`content_id` = lc.`content_id` AND x.`item` = 'KLSGL') AS `klsgl`";
		$selectSql .= ", (SELECT FIRST 1 x.`xkey` FROM `{$X}liberty_xref` x WHERE x.`content_id` = lc.`content_id` AND x.`item` = 'KL3M') AS `kl3m`";
		$selectSql .= ", (SELECT COUNT(*) FROM `{$X}liberty_xref` x WHERE x.`content_id` = lc.`content_id` AND x.`item` IN ('SGL','PRT','SHT','VOL')) AS `component_count`";
		$selectSql .= ", (SELECT COALESCE(SUM(CAST(xasm.`xkey` AS DOUBLE PRECISION)), 0)
		     FROM `{$X}liberty_xref` xasm
		     INNER JOIN `{$X}liberty_content` mc ON mc.`content_id` = xasm.`content_id`
		         AND mc.`content_type_guid` = 'stockmovement' AND mc.`user_id` = lc.`user_id`
		     INNER JOIN `{$X}liberty_xref` xpbld ON xpbld.`content_id` = xasm.`content_id` AND xpbld.`item` = 'PBLD'
		     WHERE xasm.`item` = 'ASSEMBLY' AND xasm.`xref` = lc.`content_id`) AS `prebuild_count`";
		$selectSql .= ", (SELECT FIRST 1 xpbld.`content_id`
		     FROM `{$X}liberty_xref` xasm
		     INNER JOIN `{$X}liberty_content` mc ON mc.`content_id` = xasm.`content_id`
		         AND mc.`content_type_guid` = 'stockmovement' AND mc.`user_id` = lc.`user_id`
		     INNER JOIN `{$X}liberty_xref` xpbld ON xpbld.`content_id` = xasm.`content_id` AND xpbld.`item` = 'PBLD'
		     WHERE xasm.`item` = 'ASSEMBLY' AND xasm.`xref` = lc.`content_id`
		     ORDER BY mc.`created` DESC) AS `prebuild_content_id`";

		if( !empty( $pListHash['stgrp'] ) ) {
			$whereSql .= " AND EXISTS (SELECT 1 FROM `".BIT_DB_PREFIX."liberty_xref` sx WHERE sx.`content_id` = lc.`content_id` AND sx.`item` = ?)";
			$bindVars[] = $pListHash['stgrp'];
		}

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
					$joinSql
				$whereSql $sortSql";
			$data = [];
			if( $rows = $this->mDb->query( $query, $bindVars, $pListHash['max_records'], $pListHash['offset'] ) ) {
				foreach( $rows as $row ) {
					$data[$row['content_id']] = $row;
				}
			}
			if( !empty( $data ) ) {
				foreach( array_keys( $data ) as $assemblyId ) {
					$data[$assemblyId]['display_url'] = static::getDisplayUrlFromHash( $data[$assemblyId] );
					$data[$assemblyId]['display_uri'] = static::getDisplayUriFromHash( $data[$assemblyId] );
					if( !empty( $data[$assemblyId]['data'] ) ) {
						$parseHash = [
							'data'        => $data[$assemblyId]['data'],
							'format_guid' => $data[$assemblyId]['format_guid'] ?? 'bithtml',
						];
						$data[$assemblyId]['parsed_data'] = LibertyContent::parseDataHash( $parseHash );
					}
				}
			}

		// count galleries
		$query_c = "SELECT COUNT( lc.`content_id` )
					FROM `".BIT_DB_PREFIX."liberty_content` lc
						INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON (uu.`user_id` = lc.`user_id`)
				$joinSql
				$whereSql";
		$cant = $this->mDb->getOne( $query_c, $bindVars );

		// add all pagination info to $ret
		$pListHash['cant'] = $cant;
		LibertyContent::postGetList( $pListHash );
		return $data;
	}

	/**
	 * Batched lookup of BOM components at 0 or negative stock, for a set of assemblies.
	 *
	 * Stock level per component/qty_type is the same signed-movement sum used by
	 * list_stock.php (TRANS/ORDER = +, REQN/PBLD = -). A component with no movement history
	 * at all has no stock_level row and is treated as 0 (i.e. included).
	 *
	 * @param  int[] $pAssemblyIds
	 * @return array  assembly_content_id => list of [ content_id, title, qty_type, level ]
	 */
	public function getShortageComponents( array $pAssemblyIds ): array {
		$ret = [];
		if( !$pAssemblyIds ) {
			return $ret;
		}
		$X = BIT_DB_PREFIX;
		$in = implode( ',', array_fill( 0, count( $pAssemblyIds ), '?' ) );

		$rows = $this->mDb->query(
			"SELECT bom.`content_id` AS assembly_id, bom.`xref` AS component_id, comp.`title`, bom.`item` AS qty_type,
					(SELECT SUM( CASE WHEN EXISTS (
							SELECT 1 FROM `{$X}liberty_xref` r
							WHERE r.`content_id` = mx.`content_id` AND r.`item` IN ('TRANS','ORDER')
						) THEN CAST(mx.`xkey` AS DOUBLE PRECISION)
						  ELSE -CAST(mx.`xkey` AS DOUBLE PRECISION) END )
					 FROM `{$X}liberty_xref` mx
					 INNER JOIN `{$X}liberty_content` mc ON mc.`content_id` = mx.`content_id`
					 	AND mc.`content_type_guid` = 'stockmovement'
					 WHERE mx.`xref` = bom.`xref` AND mx.`item` = bom.`item`
					   AND mx.`xkey` SIMILAR TO '[0-9]+([.][0-9]+)?') AS stock_level
			 FROM `{$X}liberty_xref` bom
				INNER JOIN `{$X}liberty_content` comp ON comp.`content_id` = bom.`xref`
			 WHERE bom.`content_id` IN ($in) AND bom.`item` IN ('SGL','PRT','SHT','VOL')
			 ORDER BY comp.`title`",
			$pAssemblyIds
		);

		foreach( $rows as $row ) {
			$level = $row['stock_level'] !== null ? (float)$row['stock_level'] : 0.0;
			if( $level > 0 ) {
				continue;
			}
			$ret[$row['assembly_id']][] = [
				'content_id' => (int)$row['component_id'],
				'title'      => $row['title'],
				'qty_type'   => $row['qty_type'],
				'level'      => $level,
			];
		}

		return $ret;
	}

	/** @return string  Font-Awesome icon HTML for use in service menus. */
	public static function getServiceIcon() {
		return '<i class="fa fal fa-camera"></i>';
	}

	/** @return string Always 'stock'. */
	public static function getServiceKey() {
		return 'stock';
	}
}
