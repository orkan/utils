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
	'app_desc'       => 'Symlink configuration files throughout the project',
	'app_usage_show' => true,
	'app_usage'      => <<<EOT
	$basename [OPTIONS] --env <env name> [--loc <home dir>] [--config <file.php>] [--copy]
	
	===============
	Default config: 
	===============
	
	'app_map' => [
		// %s == <env name>
		// ------------------------------------
		// Find file:        => Create symlink:
		// ------------------------------------
		'composer.[%s].json' => 'composer.json',
		'composer.[%s].lock' => 'composer.lock',
	]
	
	======================================
	Example of custom --config <file.php>:
	======================================
	
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
	EOT,
	'app_opts' => [
		'env'    => [ 'short' => 'e:', 'long' => 'env:'   , 'desc' => 'Environment name used in target files' ],
		'loc'    => [ 'short' => 'l:', 'long' => 'loc:'   , 'desc' => 'Working dir (default: current dir)' ],
		'config' => [ 'short' => 'c:', 'long' => 'config:', 'desc' => 'Custom config file' ],
		'copy'   => [                  'long' => 'copy'   , 'desc' => 'Copy files instead of creating symlinks' ],
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

$mode = $App->getArg( 'copy' ) ? 'copy' : 'symlink';

// =====================================================================================================================
// Run
$Utils->writeln( "SWITCH env to [$env]" );
foreach ( $map as $src => $dst ) {

	$src = $Utils->pathFix( $loc . '/' . sprintf( $src, $env ) );
	$dst = $Utils->pathFix( $loc . '/' . sprintf( $dst, $env ) );

	$Utils->writeln( <<<EOT
		
		Create {$mode}:
		$src =>
		$dst
		EOT );

	if ( is_file( $src ) ) {
		try {
			if ( $mode === 'copy' ) {
				@unlink( $dst );
				$result = copy( $src, $dst );
				$Utils->errorCheck( $result );
			}
			else {
				/**
				 * symlink():
				 * Needs Administrative rights to run on windows!
				 * @param $target Must be absolute path on windows
				 * @param $symlnk Default location is c:\windows\system32 !!!
				 */
				@unlink( $dst );
				$result = symlink( realpath( $src ), $dst ); // issues E_WARNING!
				$Utils->errorCheck( $result );
			}
		}
		catch ( \Throwable $E ) {
			$Utils->writeln( trim( $E->getMessage() ) );
		}
	}
	else {
		$Utils->writeln( 'Not found!' );
	}
}
