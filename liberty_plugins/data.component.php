<?php
/**
 * @version  $Revision$
 * $Header$
 * @package  liberty
 * @subpackage plugins_storage
 */

namespace Bitweaver\Liberty;

use Bitweaver\Stock\StockComponent;
use Bitweaver\BitBase;
use Bitweaver\KernelTools;

define( 'PLUGIN_GUID_DATACOMPONENT', 'datacomponent' );

global $gLibertySystem;

$pluginParams = [
	'tag'           => 'component',
	'title'         => 'Stock Component',
	'description'   => KernelTools::tra( "Display a link to a component in other content." ),
	'help_page'     => 'DataPluginComponent',

	'auto_activate' => false,
	'requires_pair' => false,
	'syntax'        => '{component id= }',
	'plugin_type'   => DATA_PLUGIN,

	'booticon'       => '{biticon ipackage="icons" iname="emblem-favorite" iexplain="Component"}',
	'taginsert'     => '{component id= nolink=}',

	'help_function' => 'data_component_help',
	'load_function' => 'data_component',
];
$gLibertySystem->registerPlugin( PLUGIN_GUID_DATACOMPONENT, $pluginParams );
$gLibertySystem->registerDataTag( $pluginParams['tag'], PLUGIN_GUID_DATACOMPONENT );

function data_component( $pData, $pParams ) {
	global $gBitSystem;
	$ret = ' ';

	if( BitBase::verifyId( $pParams['id'] ) && $gBitSystem->isPackageActive( 'stock' ) ) {
		$item = new StockComponent( $pParams['id'], null );
		if( $item->load() ) {
			$title = htmlspecialchars( $item->getField( 'title', KernelTools::tra( 'Component' ) ) );
			if( !empty( $pParams['nolink'] ) ) {
				$ret = $title;
			} else {
				$url = htmlspecialchars( $item->getDisplayUrl() );
				$ret = '<a href="'.$url.'">'.$title.'</a>';
			}
		} else {
			$ret = KernelTools::tra( "Unknown Component" );
		}
	} else {
		$ret = KernelTools::tra( "Unknown Component" );
	}

	return $ret;
}

function data_component_help() {
	$help =
		'<table class="data help">'
			.'<tr>'
				.'<th>' . KernelTools::tra( "Key" ) . '</th>'
				.'<th>' . KernelTools::tra( "Type" ) . '</th>'
				.'<th>' . KernelTools::tra( "Comments" ) . '</th>'
			.'</tr>'
			.'<tr class="odd">'
				.'<td>id</td>'
				.'<td>' . KernelTools::tra( "numeric") . '<br />' . KernelTools::tra("(required)") . '</td>'
				.'<td>' . KernelTools::tra( "Component id number of the component to link to." ) . '</td>'
			.'</tr>'
			.'<tr class="even">'
				.'<td>nolink</td>'
				.'<td>' . KernelTools::tra( "key-words") . '<br />' . KernelTools::tra("(optional)") . '</td>'
				.'<td>' . KernelTools::tra( "Display the component title without a hyperlink." ) . '</td>'
			.'</tr>'
		.'</table>'
		. KernelTools::tra( "Example: ") . "{component id='13'}";
	return $help;
}
