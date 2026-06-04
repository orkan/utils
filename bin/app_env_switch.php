<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
use Orkan\Application;
use Orkan\Factory;

require $GLOBALS['_composer_autoload_path'] ?? $_ENV['COMPOSER_AUTOLOAD'];
$basename = basename( __FILE__ );

/* @formatter:off */
$Factory = new Factory([
	'app_title'      => 'Environment Switch',
	'app_usage_show' => true,
	'app_usage' => <<<USAGE
	$basename [OPTIONS] --env <env name> [--loc <home dir>] [--config <file.php>]
	
	Default config contains:
	'app_map' => [
		// ------------------------------------
		// target file       || symlink
		// ------------------------------------
		'composer.[%s].json' => 'composer.json',
		'composer.[%s].lock' => 'composer.lock',
	]
	
	To add/remove/modify mappings create custom --config <file.php> returning an array:
	<?php
	return [
		'app_map' => [
			// ----------------------------
			// Remove default mappings:
			// ----------------------------
			'composer.[%s].json'     => '',
			'composer.[%s].lock'     => '',
			// -------------------------------------------------
			// Add custom mappings:
			// -------------------------------------------------
			'package.[%s].json'      => 'src/package.json',
			'package-lock.[%s].json' => 'src/package-lock.json',
		],
	];
	
	NOTE: The "%s" in target filename will be replaced by --env "name".
	USAGE,
	'app_opts' => [
		'env'    => [ 'short' => 'e:', 'long' => 'env:'   , 'desc' => 'Environment name used in target files' ],
		'loc'    => [ 'short' => 'l:', 'long' => 'loc:'   , 'desc' => 'Working dir (default: current dir)' ],
		'config' => [ 'short' => 'c:', 'long' => 'config:', 'desc' => 'Custom config file' ],
	],
	// Symlink files. (Tip: empty value to remove mapping)
	'app_map' => [
		'composer.[%s].json' => 'composer.json',
		'composer.[%s].lock' => 'composer.lock',
	],
]);
/* @formatter:on */

$App = new Application( $Factory );
$App->cfgLoad( 'config' );
$App->run();
$Utils = $Factory->Utils();

// =====================================================================================================================
// Validate
if ( !is_dir( $loc = $Utils->pathFix( $App->getArg( 'loc' ) ?: getcwd() ) ) ) {
	throw new InvalidArgumentException( sprintf( 'Home dir "%s" not found! See --loc', $loc ) );
}

if ( !$env = $App->getArg( 'env' ) ) {
	throw new InvalidArgumentException( 'Empty environment name! See --env' );
}

if ( !$map = array_filter( $Factory->cfg( 'app_map' ) ) ) {
	throw new InvalidArgumentException( 'Nothing to map. See cfg[app_map]' );
}

// =====================================================================================================================
// Run
$Utils->writeln( "SWITCH env to [$env]", 2 );
foreach ( $map as $target => $symlnk ) {

	$target = $Utils->pathFix( $loc . '/' . sprintf( $target, $env ) );
	$symlnk = $Utils->pathFix( $loc . '/' . sprintf( $symlnk, $env ) );

	$Utils->writeln( sprintf( "Create symlink:\n%s =>\n%s", $target, $symlnk ) );

	if ( is_file( $target ) ) {
		try {
			/**
			 * symlink():
			 * Needs Administrative rights to run on windows!
			 * @param $target Must be absolute path on windows
			 * @param $symlnk Default location is c:\windows\system32 !!!
			 */
			@unlink( $symlnk );
			symlink( realpath( $target ), $symlnk ); // issues E_WARNING!
		}
		catch ( \Throwable $E ) {
			$Utils->writeln( trim( $E->getMessage() ) );
		}
	}
	else {
		$Utils->writeln( 'Not found!' );
	}

	echo "\n";
}
