<?php
/**
 * @package stock
 */

/**
 * required setup
 */
namespace Bitweaver\Stock;

use Bitweaver\Liberty\LibertyMime;		// StockAssembly base class
use Bitweaver\Liberty\LibertyContent;

define('STOCKASSEMBLY_CONTENT_TYPE_GUID', 'stockassembly' );

/**
 * @package stock
 */
#[\AllowDynamicProperties]
abstract class StockBase extends LibertyMime
{
	// Path of gallery images to get breadcrumbs
	public $mAssemblyPath;
	public $mAssemblyId;

	abstract public static function getServiceKey();

	public function __sleep() {
		return array_merge( parent::__sleep(), [ 'mAssemblyPath' ] );
	}

	public function __construct() {
		$this->mAssemblyPath = '';
		parent::__construct();
	}

	// regular expression to determine if the title was computer generated
	public function isMachineName( $pString ) {
		if ( !empty($pString) ) {
			return preg_match( '/(^[0-9][-0-9 ]*$)|(^[-0-9 ]*(img|dsc|dscn|pict|htg|dscf|p)[-0-9 ][-0-9 ]*.*$)/i', trim( $pString ) );
		}
			return '';

	}

	// Gets a list of galleries which this item is attached to
	public function getParentAssemblies( $pContentId=null ) {
		if( !$this->verifyId( $pContentId ) ) {
			$pContentId = $this->mContentId;
		}
		$ret = null;

		if( is_numeric( $pContentId ) ) {
			$sql = "SELECT fg.`assembly_id` AS `hash_key`, fg.*, lc.`title`
					FROM `".BIT_DB_PREFIX."stock_assembly` fg, `".BIT_DB_PREFIX."liberty_content` lc, `".BIT_DB_PREFIX."stock_assembly_component_map` fgim
					WHERE fgim.`item_content_id` = ? AND fgim.`assembly_content_id`=fg.`content_id` AND fg.`content_id`=lc.`content_id`";
			$ret = $this->mDb->getAssoc( $sql, [ $pContentId  ] );
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
				if ( $parents['pre_t'] == STOCKASSEMBLY_CONTENT_TYPE_GUID ) {
					$ret['previous_gallery_id'] = $parents['previous'];
				} else {
					$ret['previous_component_id'] = $parents['previous'];
				}
				if ( $parents['next_t'] == STOCKASSEMBLY_CONTENT_TYPE_GUID ) {
					$ret['next_gallery_id'] = $parents['next'];
				}else {
					$ret['next_component_id'] = $parents['previous'];
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

	public function updatePosition($pAssemblyContentId, $newPosition = null) {
		if( $pAssemblyContentId && $newPosition && $this->verifyId($this->mContentId) ) {
			// SQL optimization to prevent stupid updates of identical data
			if( $radixPosition = strpos( $newPosition, '.' ) ) {
				// clean out newPosition to be a valid float, and nuke all extra . or extra crap
				$significand = substr( $newPosition, 0, $radixPosition );
				$mantissa = preg_replace( '/[^0-9]/', '', substr( $newPosition, $radixPosition + 1 ) );
				$newPosition = $significand.'.'.$mantissa;
			}
			$cleanPosition = preg_replace( '/\./', '', $newPosition );
			$sql = "UPDATE `".BIT_DB_PREFIX."stock_assembly_component_map` SET `item_position` = ?
					WHERE `item_content_id` = ? AND `assembly_content_id` = ? AND (`item_position` IS null OR `item_position`!=?)";
			$rs = $this->mDb->getOne($sql, [ $newPosition, $this->mContentId, $pAssemblyContentId, $newPosition ] );
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

	// Possible derived read-only object such as Facebook, Instagram, etc.. default is true
	public function isEditable() {
		return true;
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
		global $gBitSystem;
		//$ret['stock'] = $gBitSystem->getConfig('site_title');
		$ret = [];
		if( !$this->getField( 'gallery_path' ) ) {
			if( $this->isValid() ) {
				$ancestors = [];
				$currentContentId = $this->mContentId;
				for( $depth = 0; $depth < 10; $depth++ ) {
					$parent = $this->mDb->getRow(
						"SELECT fg.assembly_id AS gid, fg.content_id AS cid, lc.title AS gtitle
						 FROM ".BIT_DB_PREFIX."stock_assembly fg
						 INNER JOIN ".BIT_DB_PREFIX."liberty_content lc ON (lc.content_id=fg.content_id)
						 INNER JOIN ".BIT_DB_PREFIX."stock_assembly_component_map fgim ON (fgim.assembly_content_id=fg.content_id)
						 WHERE fgim.item_content_id=?",
						[$currentContentId]
					);
					if( !$parent ) break;
					array_unshift( $ancestors, $parent );
					$currentContentId = $parent['cid'];
				}
				if( $ancestors ) {
					$pathIds = array_column( $ancestors, 'gid' );
					$this->setGalleryPath( '/'.implode( '/', $pathIds ) );
					foreach( $ancestors as $ancestor ) {
						$ret[$ancestor['gid']] = $ancestor['gtitle'];
					}
				}
			}
		}
		if( !$ret && $this->getField( 'gallery_path' ) ) {
			$path = explode( '/', ltrim( $this->getField( 'gallery_path' ), '/' ) );
			$p = 0;
			$c = 1;
			$joinSql = '';
			$selectSql = '';//AS title$g, fg$g.assembly_id AS assembly_id$g";
			$whereSql = '';
			$bindVars = [];
			// We need to get min_content_status_id
			$pListHash = [];
			LibertyContent::prepGetList($pListHash);
			foreach( $path as $assemblyId ) {
				if( $assemblyId ) {
					$p++; $c++;
					$selectSql .= " lc$p.`title` AS `title$p`, fg$p.`assembly_id` AS `assembly_id$p`,";
					$joinSql .= " `".BIT_DB_PREFIX."stock_assembly_component_map` fgim$p
						INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc$p ON(fgim$p.`assembly_content_id`=lc$p.`content_id`)
						INNER JOIN `".BIT_DB_PREFIX."stock_assembly` fg$p ON(fg$p.`content_id`=lc$p.`content_id`),";
					$whereSql .= " fg$p.`assembly_id`=? AND fgim$p.`item_content_id`=lc$c.`content_id` AND lc$p.`content_status_id` > ? AND";
					array_push( $bindVars, $assemblyId );
					array_push( $bindVars, $pListHash['min_content_status_id']);
				}
			}
//			$selectSql .= " lc$c.title AS title$c ";//AS title$g, fg$g.assembly_id AS assembly_id$g";
			$joinSql .= " `".BIT_DB_PREFIX."stock_assembly_component_map` fgim$c
				INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc$c ON(fgim$c.`item_content_id`=lc$c.`content_id`) ";
			$whereSql .= " lc$c.`content_id`=?  AND fgim$c.`assembly_content_id`=lc$p.`content_id` ";
			array_push( $bindVars, $this->mContentId );
			$rs = $this->mDb->getRow( "SELECT ".rtrim( $selectSql, ',')." FROM ".rtrim( $joinSql, ',')." WHERE $whereSql", $bindVars );
			if( !empty( $rs ) ) {
				for( $i = 1; $i <= (count( $rs ) / 2); $i++ ) {
					$ret[$rs['assembly_id'.$i]] = $rs['title'.$i];
				}
			}
		}

		if( $this->isValid() && $pIncludeSelf && is_a( $this, '\Bitweaver\Stock\StockAssembly' ) ) {
			$ret[$this->mAssemblyId] = $this->getTitle();
		}

		return $ret;
	}

	public function addToAssemblies( $pAssemblyArray ) {
		global $gBitSystem;
		if( $this->isValid() ) {
			$inGalleries = $this->mDb->getAssoc( "SELECT `assembly_id`,`assembly_content_id` FROM `".BIT_DB_PREFIX."stock_assembly_component_map` fgim INNER JOIN `".BIT_DB_PREFIX."stock_assembly` fg ON (fgim.`assembly_content_id`=fg.`content_id`) WHERE `item_content_id` = ?", [ $this->mContentId  ] );
			$galleries = [];
			if( is_array( $pAssemblyArray ) && count( $pAssemblyArray ) ) {
				foreach( $pAssemblyArray as $assemblyId ) {
					// image has been requested to be put in a new gallery
					if( !is_numeric( $assemblyId ) ) {
						switch( $assemblyId ) {
							case 'newest':
								$assemblyId = $this->mDb->getAssoc( "SELECT `assembly_id` FROM `".BIT_DB_PREFIX."stock_assembly` fg INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON (fg.`content_id`=lg.`content_id`) WHERE `user_id` = ? ORDER BY assembly_id DESC", [ $this->getField( 'user_id' ) ] );
								break;

						}
					}
					if( empty( $inGalleries[$assemblyId] ) ) {
						if( empty( $galleries[$assemblyId] ) ) {
							if( $galleries[$assemblyId] = StockAssembly::lookup( [ 'assembly_id' => $assemblyId ] ) ) {
								$galleries[$assemblyId]->load();
							}
						}
						if( $galleries[$assemblyId] && $galleries[$assemblyId]->isValid() ) {
							if( $galleries[$assemblyId]->hasUserPermission( 'p_stock_upload', true, false ) || $galleries[$assemblyId]->isPublic() ) {
								if( $gBitSystem->isFeatureActive( 'stock_gallery_default_sort_mode' ) ) {
									$pos = null;
								} else {
									$query = "SELECT MAX(`item_position`)
											  FROM `".BIT_DB_PREFIX."stock_assembly_component_map` fgim
												INNER JOIN `".BIT_DB_PREFIX."stock_assembly` fg ON(fgim.`assembly_content_id`=fg.`content_id`)
											  WHERE fg.`assembly_id`=?";
									$pos = $this->mDb->getOne( $query, [ $assemblyId ] ) + 10;
								}

								$galleries[$assemblyId]->addItem( $this->mContentId, $pos );
							} else {
								$this->mErrors[] = "You do not have permission to attach ".$this->getTitle()." to ".$galleries[$assemblyId]->getTitle();
							}
						}
					} else {
						// image already in an existing gallery.
						unset( $inGalleries[$assemblyId] );
					}
				}
			}
			if( count( $inGalleries ) ) {
				// if we have any left over in the inGalleries array, we should delete them. these were the "unchecked" boxes
				foreach( $inGalleries as $assemblyId ) {
					$sql = "DELETE FROM `".BIT_DB_PREFIX."stock_assembly_component_map` WHERE `assembly_content_id` = ? AND `item_content_id` = ?";
					$rs = $this->mDb->getOne($sql, [ $assemblyId, $this->mContentId ] );
				}
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
		if ( is_numeric( $this->mAssemblyId ) && is_numeric( $pAssemblyContentId ) ) {

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