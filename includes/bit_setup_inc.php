<?php
use \Bitweaver\Stock\StockAssembly;
global $gBitSystem, $gBitUser, $gBitSmarty, $gBitThemes;

$pRegisterHash = [
	'package_name' => 'stock',
	'package_path' => dirname( dirname( __FILE__ ) ).'/',
	'homeable' => true,
];
// fix to quieten down VS Code which can't see the dynamic creation of these ...
define( 'STOCK_PKG_NAME', $pRegisterHash['package_name'] );
define( 'STOCK_PKG_URL', BIT_ROOT_URL . basename( $pRegisterHash['package_path'] ) . '/' );
define( 'STOCK_PKG_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/' );
define( 'STOCK_PKG_INCLUDE_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/includes/');
define( 'STOCK_PKG_CLASS_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/includes/classes/');
define( 'STOCK_PKG_ADMIN_PATH', BIT_ROOT_PATH . basename( $pRegisterHash['package_path'] ) . '/admin/');

$gBitSystem->registerPackage( $pRegisterHash );

if( $gBitSystem->isPackageActive( 'stock' ) ) { // && $gBitUser->hasPermission( 'p_stock_view' )) {

	// Default Preferences Defines
	define ( 'STOCK_DEFAULT_ROWS_PER_PAGE', 5 );
	define ( 'STOCK_DEFAULT_COLS_PER_PAGE', 2 );
	define ( 'STOCK_DEFAULT_THUMBNAIL_SIZE', 'large' );

	$menuHash = [
		'package_name'  => STOCK_PKG_NAME,
		'index_url'     => STOCK_PKG_URL.'index.php',
		'menu_template' => 'bitpackage:stock/menu_stock.tpl',
	];
	$gBitSystem->registerAppMenu( $menuHash );

	define( 'LIBERTY_SERVICE_PHOTOSHARING', 'photosharing');

	$gLibertySystem->registerService( LIBERTY_SERVICE_PHOTOSHARING, STOCK_PKG_NAME, [
		'users_expunge_function' => 'stock_expunge_user',
	] );

	function stock_expunge_user( $pObject ) {
		global $gBitDb;
		if( !empty( $pObject->mUserId ) ) {
			$query = "SELECT lc.`content_id` FROM `".BIT_DB_PREFIX."liberty_content` lc WHERE lc.`content_type_guid`='".STOCKASSEMBLY_CONTENT_TYPE_GUID."' AND lc.`user_id`=?";
			if( $galleries = $gBitDb->getCol( $query, [ $pObject->mUserId ] ) ) {
				foreach( $galleries as $contentId ) {
					$delGallery = new StockAssembly( $contentId );
					if( $delGallery->load() ) {
						$delGallery->expunge();
					}
				}
			}
		}
	}
}