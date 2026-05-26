<?php
/**
 * @version  $Revision$
 * $Header$
 * @package  liberty
 * @subpackage plugins_storage
 */

namespace Bitweaver\Liberty;

use Bitweaver\Stock\StockAssembly;
use Bitweaver\BitBase;
use Bitweaver\KernelTools;

define( 'PLUGIN_GUID_DATAASSEMBLY', 'dataassembly' );

global $gLibertySystem;

$pluginParams = [
	'tag'           => 'assembly',
	'title'         => 'Stock Assembly',
	'description'   => KernelTools::tra( "Display a link to a stock assembly in other content." ),
	'help_page'     => 'DataPluginAssembly',

	'auto_activate' => false,
	'requires_pair' => false,
	'syntax'        => '{assembly id= }',
	'plugin_type'   => DATA_PLUGIN,

	'booticon'       => '{booticon iname="icon-list" iexplain="Assembly"}',
	'taginsert'     => '{assembly id= nolink=}',

	'help_function' => 'data_assembly_help',
	'load_function' => 'data_assembly',
];
$gLibertySystem->registerPlugin( PLUGIN_GUID_DATAASSEMBLY, $pluginParams );
$gLibertySystem->registerDataTag( $pluginParams['tag'], PLUGIN_GUID_DATAASSEMBLY );

function data_assembly( $pData, $pParams ) {
	global $gBitSystem;
	$ret = ' ';

	if( BitBase::verifyId( $pParams['id'] ) && $gBitSystem->isPackageActive( 'stock' ) ) {
		$assembly = new StockAssembly( null, $pParams['id'] );
		if( $assembly->load() ) {
			$title = htmlspecialchars( $assembly->getTitle() );
			if( !empty( $pParams['nolink'] ) ) {
				$ret = $title;
			} else {
				$url = htmlspecialchars( $assembly->getDisplayUrl() );
				$ret = '<a href="'.$url.'">'.$title.'</a>';
			}
		} else {
			$ret = KernelTools::tra( "Unknown Assembly" );
		}
	} else {
		$ret = KernelTools::tra( "Unknown Assembly" );
	}

	return $ret;
}

function data_assembly_help() {
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
				.'<td>' . KernelTools::tra( "Assembly id number of the assembly to link to." ) . '</td>'
			.'</tr>'
			.'<tr class="even">'
				.'<td>nolink</td>'
				.'<td>' . KernelTools::tra( "key-words") . '<br />' . KernelTools::tra("(optional)") . '</td>'
				.'<td>' . KernelTools::tra( "Display the assembly title without a hyperlink." ) . '</td>'
			.'</tr>'
		.'</table>'
		. KernelTools::tra( "Example: ") . "{assembly id='5'}";
	return $help;
}
