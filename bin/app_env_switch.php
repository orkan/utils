<?php
use Orkan\Application;
use Orkan\Factory;

require $_composer_autoload_path ?? dirname( __DIR__, 3 ) . '/autoload.php';

/* @formatter:off */
$Factory = new Factory([
	'cli_title'   => 'Environment Switch',
	'app_usage'   => sprintf( '%s --env <env name> --loc [home dir] [options]', basename( __FILE__ ) ),
	'app_args'    => [
		'env'    => [ 'short' => 'e:', 'long' => 'env:'   , 'desc' => 'Environment name' ],
		'loc'    => [ 'short' => 'l:', 'long' => 'loc:'   , 'desc' => 'Home dir. Default: current dir' ],
		'config' => [ 'short' => 'c:', 'long' => 'config:', 'desc' => 'Load additional config file' ],
	],
	// Symlink files. (Tip: empty value to remove mapping)
	'map' => [
		'composer.[%s].json' => 'composer.json',
		'composer.[%s].lock' => 'composer.lock',
	],
]);
/* @formatter:on */

$App = new Application( $Factory );

/*
 * ---------------------------------------------------------------------------------------------------------------------
 * User config?
 */
if ( is_file( $cfgFile = $App->getArg( 'config' ) ) ) {
	$Factory->merge( require $cfgFile, true );
}

$App->run();
$Utils = $Factory->Utils();

if ( !is_dir( $usrLoc = $Utils->pathFix( $App->getArg( 'loc' ) ?: getcwd() ) ) ) {
	throw new InvalidArgumentException( sprintf( 'Home dir "%s" not found!', $usrLoc ) );
}

if ( !$usrEnv = $App->getArg( 'env' ) ) {
	throw new InvalidArgumentException( 'Empty environment name!' );
}

/*
 * ---------------------------------------------------------------------------------------------------------------------
 * Run
 */
$Utils->writeln( "SWITCH env to [$usrEnv]", 2 );
$usrLoc .= DIRECTORY_SEPARATOR;
$map = array_filter( $Factory->cfg( 'map' ) ); // remove empty values

foreach ( $map as $target => $link ) {

	$target = $Utils->pathFix( $usrLoc . sprintf( $target, $usrEnv ) );
	$link = $Utils->pathFix( $usrLoc . sprintf( $link, $usrEnv ) );

	$Utils->writeln( sprintf( "Create symlink:\n%s =>\n%s", $target, $link ) );

	if ( is_file( $target ) ) {
		try {
			/**
			 * symlink():
			 * Needs Administrative rights to run on windows!
			 * @param $target Must be absolute path on windows
			 * @param $link   Default location is c:\windows\system32 !!!
			 */
			@unlink( $link );
			symlink( realpath( $target ), $link ); // issues E_WARNING!
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
