<?php
/**
 * @package stock
 */

global $gBitInstaller;

$X = BIT_DB_PREFIX;

$gBitInstaller->registerPackageUpgrade(
	[
		'package'     => STOCK_PKG_NAME,
		'version'     => '5.0.2',
		'description' => 'Drop stock_assembly_map - a leftover fisheye-gallery-hierarchy table '
			.'from stock\'s original port that was never actually populated (confirmed 0 rows '
			.'against 122 real assemblies / months of live merg.rdm1.uk data). The real BOM/'
			.'component-quantity relationship has always lived in liberty_xref (x_group=\'quantity\', '
			.'items SGL/PRT/PCK/SHT/VOL on the assembly, xref=component content_id) - that\'s the '
			.'only place assembly/component membership is stored now. All PHP code that referenced '
			.'this table (StockBase/StockAssembly/StockComponent\'s gallery-hierarchy, breadcrumb, '
			.'thumbnail-picking, and flat-BOM-checkbox apparatus) was dead-in-practice - built but '
			.'never rendered by any template - and was removed in the same pass. See stock.md\'s '
			.'2026-08-28 entry for the full investigation.',
	],
	[
		[ 'QUERY' => [
			'SQL92' => [
				"DROP TABLE `{$X}stock_assembly_map`",
			],
		]],
	]
);
