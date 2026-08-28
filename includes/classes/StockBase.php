<?php
/**
 * Abstract base for StockAssembly and StockComponent.
 *
 * Provides supplier xref enrichment with contact titles.
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
	/** @return string  Service key used by LibertyContent service hooks (always 'stock'). */
	abstract public static function getServiceKey();

	/**
	 * Load xref groups then enrich the 'supplier' group by resolving each contact
	 * content_id (xref column) to the contact's lc.title.
	 */
	public function loadXrefInfo(): void {
		$this->mXrefInfo = $this->xrefType()->loadContent( $this->mContentId );
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

	/** @return bool TRUE when the 'is_public' preference is set to 'y'. */
	public function isPublic() {
		if( $this->isValid() ) {
			return $this->getPreference( 'is_public' ) == 'y';
		}
		return false;
	}

}