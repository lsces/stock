<?php
/**
 * @package stock
 */

/**
 * required setup
 */
namespace Bitweaver\Stock;

use Bitweaver\Liberty\LibertyContent;

/**
 * @package stock
 */
#[\AllowDynamicProperties]
abstract class StockBase extends LibertyContent
{
	// Path of gallery images to get breadcrumbs
	public $mAssemblyPath;

	abstract public static function getServiceKey();

	public function __sleep() {
		return array_merge( parent::__sleep(), [ 'mAssemblyPath' ] );
	}

	public function __construct() {
		$this->mAssemblyPath = '';
		parent::__construct();
	}

	public function loadXrefList(): void {
		parent::loadXrefList();
		if( !empty( $this->mInfo['supplier'] ) ) {
			$contactIds = array_values( array_unique( array_filter( array_column( $this->mInfo['supplier'], 'xref' ) ) ) );
			if( $contactIds ) {
				$placeholders = implode( ',', array_fill( 0, count( $contactIds ), '?' ) );
				$contacts = $this->mDb->getAssoc(
					"SELECT lc.`content_id`, lc.`title`
					 FROM `".BIT_DB_PREFIX."liberty_content` lc
					 WHERE lc.`content_id` IN ($placeholders)",
					$contactIds
				);
				foreach( $this->mInfo['supplier'] as &$row ) {
					if( !empty( $row['xref'] ) && isset( $contacts[$row['xref']] ) ) {
						$row['xref_title'] = $contacts[$row['xref']];
					}
				}
				unset( $row );
			}
		}
	}

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

	// Gets a list of galleries which this item is attached to
	public function getParentAssemblies( $pContentId=null ) {
		if( !$this->verifyId( $pContentId ) ) {
			$pContentId = $this->mContentId;
		}
		$ret = null;

		if( is_numeric( $pContentId ) ) {
			$sql = "SELECT lc.`content_id` AS `hash_key`, lc.*
					FROM `".BIT_DB_PREFIX."liberty_content` lc
					INNER JOIN `".BIT_DB_PREFIX."stock_assembly_component_map` fgim ON (fgim.`assembly_content_id`=lc.`content_id`)
					WHERE fgim.`item_content_id` = ? AND lc.`content_type_guid` = '".STOCKASSEMBLY_CONTENT_TYPE_GUID."'";
			$ret = $this->mDb->getAssoc( $sql, [ $pContentId ] );
		}
		if ( $ret ) {
			$parents = current( $ret );
			$sql = "WITH TREE AS
				( SELECT fgim.`item_content_id` AS assembly_content_id,
				LAG( fgim.`item_content_id`) OVER (ORDER BY fgim.`item_position`) AS PREVIOUS,
				LEAD( fgim.`item_content_id` ) OVER (ORDER BY fgim.`item_position`) AS NEXT
				FROM `".BIT_DB_PREFIX."stock_assembly_component_map` fgim
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

	public function loadParentAssemblies() {
		if( $this->isValid() ) {
			$this->mInfo['parent_galleries'] = $this->getParentAssemblies();
		}
	}

	public function setGalleryPath( $pPath ) {
		$this->setField( 'gallery_path', rtrim( $pPath, '/' ) );
	}

	public function getThumbnailContentId() {
		// PURE VIRTUAL
	}

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
						  FROM connectby('`".BIT_DB_PREFIX."stock_assembly_component_map`', '`assembly_content_id`', '`item_content_id`', ?, 0, '/') AS t(cb_item_content_id int,cb_assembly_content_id int, level int, branch text)
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
						 INNER JOIN `".BIT_DB_PREFIX."stock_assembly_component_map` fgim ON (fgim.`assembly_content_id`=lc.`content_id`)
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

	public function addToAssemblies( $pAssemblyArray ) {
		global $gBitSystem;
		if( $this->isValid() ) {
			$inGalleries = $this->mDb->getAssoc(
				"SELECT `assembly_content_id`, `assembly_content_id`
				 FROM `".BIT_DB_PREFIX."stock_assembly_component_map`
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
										"SELECT MAX(`item_position`) FROM `".BIT_DB_PREFIX."stock_assembly_component_map` WHERE `assembly_content_id`=?",
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
					"DELETE FROM `".BIT_DB_PREFIX."stock_assembly_component_map` WHERE `assembly_content_id` = ? AND `item_content_id` = ?",
					[ $contentId, $this->mContentId ]
				);
			}
		}
	}

	public function isPublic() {
		if( $this->isValid() ) {
			return $this->getPreference( 'is_public' ) == 'y';
		}
		return false;
	}

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
						  FROM connectby('`".BIT_DB_PREFIX."stock_assembly_component_map`', '`assembly_content_id`', '`item_content_id`', ?, 0, '/') AS t(`cb_assembly_content_id` int,`cb_item_content_id` int, `level` int, `branch` text)
						  WHERE `cb_assembly_content_id`=?
						  ORDER BY branch
						";
				if ( $this->mDb->getOne($query, [  $pItemContentId, $pAssemblyContentId ] ) ) {
					$ret = true;
				}
			} else {
				$sql = "SELECT count(`item_content_id`) as `item_count`
						FROM `".BIT_DB_PREFIX."stock_assembly_component_map`
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