<?php
/**
 * Abstract base for StockAssembly and StockComponent.
 *
 * Provides shared assembly-hierarchy navigation (parent lookup, breadcrumb building),
 * assembly membership queries, and supplier xref enrichment with contact titles.
 *
 * @package stock
 */
namespace Bitweaver\Stock;

use Bitweaver\Liberty\LibertyContent;

defined( 'STOCKASSEMBLY_CONTENT_TYPE_GUID' )  || define( 'STOCKASSEMBLY_CONTENT_TYPE_GUID',  'stockassembly' );
defined( 'STOCKCOMPONENT_CONTENT_TYPE_GUID' ) || define( 'STOCKCOMPONENT_CONTENT_TYPE_GUID', 'stockcomponent' );

#[\AllowDynamicProperties]
abstract class StockBase extends LibertyContent
{
	/** @var string Slash-separated content_id ancestry path; set by setGalleryPath(). */
	public $mAssemblyPath;

	/** @return string  Service key used by LibertyContent service hooks (always 'stock'). */
	abstract public static function getServiceKey();

	public function __sleep() {
		return array_merge( parent::__sleep(), [ 'mAssemblyPath' ] );
	}

	public function __construct() {
		$this->mAssemblyPath = '';
		parent::__construct();
	}

	/**
	 * Load xref groups then enrich the 'supplier' group by resolving each contact
	 * content_id (xref column) to the contact's lc.title.
	 */
	public function loadXrefInfo(): void {
		parent::loadXrefInfo();
		if( empty( $this->mXrefInfo ) ) return;
		$supplierGroup = $this->mXrefInfo->mGroups['supplier'] ?? null;
		if( !$supplierGroup || empty( $supplierGroup->mXrefs ) ) return;
		$contactIds = array_values( array_unique( array_filter( array_column( $supplierGroup->mXrefs, 'xref' ) ) ) );
		if( !$contactIds ) return;
		$contacts = $this->mDb->getAssoc(
			"SELECT lc.`content_id`, lc.`title` FROM `".BIT_DB_PREFIX."liberty_content` lc WHERE lc.`content_id` IN (".implode( ',', array_fill( 0, count( $contactIds ), '?' ) ).")",
			$contactIds
		);
		foreach( $supplierGroup->mXrefs as &$row ) {
			if( !empty( $row['xref'] ) && isset( $contacts[$row['xref']] ) ) {
				$row['xref_title'] = $contacts[$row['xref']];
			}
		}
		unset( $row );
	}

	/**
	 * Enrich a single xref display row with resolved title data.
	 *
	 * Base implementation handles the 'supplier' group (contact title).
	 * Subclasses override to add component title / pack size for BOM rows.
	 *
	 * @param array $pXrefInfo  Xref display row; modified in place.
	 */
	public function enrichXrefDisplay( array &$pXrefInfo ): void {
		if( !empty( $pXrefInfo['xref'] ) && ( $pXrefInfo['x_group'] ?? '' ) === 'supplier' ) {
			if( $contact = $this->mDb->getRow(
				"SELECT lc.`title` FROM `".BIT_DB_PREFIX."liberty_content` lc WHERE lc.`content_id` = ?",
				[ (int)$pXrefInfo['xref'] ]
			) ) {
				$pXrefInfo['xref_title'] = $contact['title'];
			}
		}
	}

	/**
	 * Return the parent assemblies this item belongs to, with prev/next sibling pointers.
	 *
	 * @param  int|null $pContentId  Defaults to $this->mContentId.
	 * @return array|null            Assoc array keyed by content_id, or null if none.
	 */
	public function getParentAssemblies( $pContentId=null ) {
		if( !$this->verifyId( $pContentId ) ) {
			$pContentId = $this->mContentId;
		}
		$ret = null;

		if( is_numeric( $pContentId ) ) {
			$sql = "SELECT lc.`content_id` AS `hash_key`, lc.*
					FROM `".BIT_DB_PREFIX."liberty_content` lc
					INNER JOIN `".BIT_DB_PREFIX."stock_assembly_map` fgim ON (fgim.`assembly_content_id`=lc.`content_id`)
					WHERE fgim.`item_content_id` = ? AND lc.`content_type_guid` = '".STOCKASSEMBLY_CONTENT_TYPE_GUID."'";
			$ret = $this->mDb->getAssoc( $sql, [ $pContentId ] );
		}
		if ( $ret ) {
			$parents = current( $ret );
			$sql = "WITH TREE AS
				( SELECT fgim.`item_content_id` AS assembly_content_id,
				LAG( fgim.`item_content_id`) OVER (ORDER BY fgim.`item_position`) AS PREVIOUS,
				LEAD( fgim.`item_content_id` ) OVER (ORDER BY fgim.`item_position`) AS NEXT
				FROM `".BIT_DB_PREFIX."stock_assembly_map` fgim
				WHERE fgim.`assembly_content_id` = ?
				order by fgim.`item_position` )
				SELECT pr.PREVIOUS, prec.`content_type_guid` AS PRE_T, pr.NEXT, posc.`content_type_guid` AS NEXT_T FROM TREE pr
				LEFT JOIN `".BIT_DB_PREFIX."liberty_content` prec ON prec.`content_id` = pr.PREVIOUS
				LEFT JOIN `".BIT_DB_PREFIX."liberty_content` posc ON posc.`content_id` = pr.NEXT
				WHERE pr.`assembly_content_id` = ?";
			if( $parents = $this->mDb->getRow($sql, [ $parents['content_id'], $pContentId ] ) ) {
				if ( $parents['pre_t'] == 'stockassembly' ) {
					$ret['previous_gallery_id'] = $parents['previous'];
				} else {
					$ret['previous_content_id'] = $parents['previous'];
				}
				if ( $parents['next_t'] == 'stockassembly' ) {
					$ret['next_gallery_id'] = $parents['next'];
				} else {
					$ret['next_content_id'] = $parents['next'];
				}
			}
		}
		return $ret;
	}

	/** Populate $this->mInfo['parent_galleries'] via getParentAssemblies(). */
	public function loadParentAssemblies() {
		if( $this->isValid() ) {
			$this->mInfo['parent_galleries'] = $this->getParentAssemblies();
		}
	}

	/** @param string $pPath  Slash-separated ancestry path; trailing slash is stripped. */
	public function setGalleryPath( $pPath ) {
		$this->setField( 'gallery_path', rtrim( $pPath, '/' ) );
	}

	/** @return int|null  content_id of the thumbnail item, or null if none loaded. */
	public function getThumbnailContentId() {
		// PURE VIRTUAL
	}

	/**
	 * Load thumbnail image data into $this->mInfo['preview_content'].
	 * Base implementation is a no-op; subclasses override.
	 *
	 * @param string   $pSize       Thumbnail size hint ('small', 'medium', etc.).
	 * @param int|null $pContentId  Override which content to use as thumbnail source.
	 */
	public function loadThumbnail( $pSize='small', $pContentId=null ) {
		// Default does nothing
	}

	// THis is a function that creates a mack daddy function to get a breadcrumb path with a single query.
	// Do not muck with this query unless you really, truly understand what is going on.
/*
not ready for primetime
	public function getPaths() {
		global $gBitDb;

		$ret = null;
		if( $this->isValid() ) {
			if( $this->mDb->isAdvancedPostgresEnabled() ) {
				$bindVars = [];
				$containVars = [];
				$selectSql = '';
				$joinSql = '';
				$whereSql = '';

				$query = "SELECT fg.assembly_id, branch
						  FROM connectby('`".BIT_DB_PREFIX."stock_assembly_map`', '`assembly_content_id`', '`item_content_id`', ?, 0, '/') AS t(cb_item_content_id int,cb_assembly_content_id int, level int, branch text)
							INNER JOIN `".BIT_DB_PREFIX."stock_assembly` fg ON (fg.`content_id`=cb_item_content_id)
							INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON(lc.`content_id`=fg.`content_id`)
						  ORDER BY level DESC, branch, lc.`title`";
				if( $ret = $gBitDb->GetAssoc( $query, [ $this->mContentId  ] ) ) {
				}
			}
		}
		return $ret;
	}
*/

	/**
	 * Return an ordered map of content_id → title for all ancestor assemblies.
	 *
	 * Walks stock_assembly_map upward (max 10 hops), caches the path via
	 * setGalleryPath(), then falls back to loading from the cached path on
	 * subsequent calls.
	 *
	 * @param  bool  $pIncludeSelf  Append this assembly's own entry when TRUE.
	 * @return array                content_id → title, ordered root → parent.
	 */
	public function getBreadcrumbLinks( $pIncludeSelf = false ) {
		$ret = [];
		if( !$this->getField( 'gallery_path' ) ) {
			if( $this->isValid() ) {
				$ancestors = [];
				$currentContentId = $this->mContentId;
				for( $depth = 0; $depth < 10; $depth++ ) {
					$parent = $this->mDb->getRow(
						"SELECT lc.`content_id`, lc.`title`
						 FROM `".BIT_DB_PREFIX."liberty_content` lc
						 INNER JOIN `".BIT_DB_PREFIX."stock_assembly_map` fgim ON (fgim.`assembly_content_id`=lc.`content_id`)
						 WHERE fgim.`item_content_id`=? AND lc.`content_type_guid`='".STOCKASSEMBLY_CONTENT_TYPE_GUID."'",
						[ $currentContentId ]
					);
					if( !$parent ) break;
					array_unshift( $ancestors, $parent );
					$currentContentId = $parent['content_id'];
				}
				if( $ancestors ) {
					$this->setGalleryPath( '/'.implode( '/', array_column( $ancestors, 'content_id' ) ) );
					foreach( $ancestors as $ancestor ) {
						$ret[$ancestor['content_id']] = $ancestor['title'];
					}
				}
			}
		}
		if( !$ret && $this->getField( 'gallery_path' ) ) {
			$path = array_filter( explode( '/', ltrim( $this->getField( 'gallery_path' ), '/' ) ) );
			if( $path ) {
				$placeholders = implode( ',', array_fill( 0, count( $path ), '?' ) );
				$rows = $this->mDb->getAssoc(
					"SELECT lc.`content_id`, lc.`title`
					 FROM `".BIT_DB_PREFIX."liberty_content` lc
					 WHERE lc.`content_id` IN ($placeholders)
					 AND lc.`content_type_guid`='".STOCKASSEMBLY_CONTENT_TYPE_GUID."'",
					array_map( 'intval', $path )
				);
				// preserve path order
				foreach( $path as $contentId ) {
					if( isset( $rows[$contentId] ) ) {
						$ret[$contentId] = $rows[$contentId]['title'];
					}
				}
			}
		}

		if( $this->isValid() && $pIncludeSelf && is_a( $this, '\Bitweaver\Stock\StockAssembly' ) ) {
			$ret[$this->mContentId] = $this->getTitle();
		}

		return $ret;
	}

	/**
	 * Sync this item's assembly membership to match $pAssemblyArray.
	 *
	 * Adds to assemblies not yet in the map; removes from assemblies no longer listed.
	 * Checks p_stock_upload permission before adding to each assembly.
	 *
	 * @param int[] $pAssemblyArray  List of assembly content_ids to be a member of.
	 */
	public function addToAssemblies( $pAssemblyArray ) {
		global $gBitSystem;
		if( $this->isValid() ) {
			$inGalleries = $this->mDb->getAssoc(
				"SELECT `assembly_content_id`, `assembly_content_id`
				 FROM `".BIT_DB_PREFIX."stock_assembly_map`
				 WHERE `item_content_id` = ?",
				[ $this->mContentId ]
			);
			$galleries = [];
			if( is_array( $pAssemblyArray ) && count( $pAssemblyArray ) ) {
				foreach( $pAssemblyArray as $contentId ) {
					if( !is_numeric( $contentId ) ) continue;
					$contentId = (int)$contentId;
					if( empty( $inGalleries[$contentId] ) ) {
						if( empty( $galleries[$contentId] ) ) {
							if( $galleries[$contentId] = StockAssembly::lookup( [ 'content_id' => $contentId ] ) ) {
								$galleries[$contentId]->load();
							}
						}
						if( $galleries[$contentId] && $galleries[$contentId]->isValid() ) {
							if( $galleries[$contentId]->hasUserPermission( 'p_stock_upload', true, false ) || $galleries[$contentId]->isPublic() ) {
								if( $gBitSystem->isFeatureActive( 'stock_gallery_default_sort_mode' ) ) {
									$pos = null;
								} else {
									$pos = $this->mDb->getOne(
										"SELECT MAX(`item_position`) FROM `".BIT_DB_PREFIX."stock_assembly_map` WHERE `assembly_content_id`=?",
										[ $contentId ]
									) + 10;
								}
								$galleries[$contentId]->addItem( $this->mContentId, $pos );
							} else {
								$this->mErrors[] = "You do not have permission to attach ".$this->getTitle()." to ".$galleries[$contentId]->getTitle();
							}
						}
					} else {
						unset( $inGalleries[$contentId] );
					}
				}
			}
			// remove from any unchecked assemblies
			foreach( array_keys( $inGalleries ) as $contentId ) {
				$this->mDb->getOne(
					"DELETE FROM `".BIT_DB_PREFIX."stock_assembly_map` WHERE `assembly_content_id` = ? AND `item_content_id` = ?",
					[ $contentId, $this->mContentId ]
				);
			}
		}
	}

	/** @return bool TRUE when the 'is_public' preference is set to 'y'. */
	public function isPublic() {
		if( $this->isValid() ) {
			return $this->getPreference( 'is_public' ) == 'y';
		}
		return false;
	}

	/**
	 * Check whether an item is a member (direct or recursive) of an assembly.
	 *
	 * @param  int      $pAssemblyContentId  Assembly to test membership in.
	 * @param  int|null $pItemContentId      Item to test; defaults to $this->mContentId.
	 * @return bool
	 */
	public function isInAssembly( $pAssemblyContentId, $pItemContentId = null) {
		if( !$this->verifyId( $pItemContentId ) ) {
			$pItemContentId = $this->mContentId;
		}
		$ret = false;
		if( is_numeric( $pAssemblyContentId ) ) {

			if( $this->mDb->isAdvancedPostgresEnabled() ) {
				global $gBitDb, $gBitSmarty;
				// This code pulls all branches for the current node and determines if there is a path from this content to the root
				// without hitting a security_id. If there is clear path it returns true. If there is a security_id, then
				// it determines if the current user has permission
				$query = "SELECT branch,level,cb_item_content_id,cb_assembly_content_id
						  FROM connectby('`".BIT_DB_PREFIX."stock_assembly_map`', '`assembly_content_id`', '`item_content_id`', ?, 0, '/') AS t(`cb_assembly_content_id` int,`cb_item_content_id` int, `level` int, `branch` text)
						  WHERE `cb_assembly_content_id`=?
						  ORDER BY branch
						";
				if ( $this->mDb->getOne($query, [  $pItemContentId, $pAssemblyContentId ] ) ) {
					$ret = true;
				}
			} else {
				$sql = "SELECT count(`item_content_id`) as `item_count`
						FROM `".BIT_DB_PREFIX."stock_assembly_map`
						WHERE `assembly_content_id` = ? AND `item_content_id` = ?";
				$rs = $this->mDb->getRow($sql, [ $pAssemblyContentId, $pItemContentId ] );
				if ($rs['item_count'] > 0) {
					$ret = true;
				}
			}
		}
		return $ret;
	}

}