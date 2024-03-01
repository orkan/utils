<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
use Orkan\Application;
use Orkan\Factory;

require $_composer_autoload_path ?? dirname( __DIR__, 3 ) . '/autoload.php';

/* @formatter:off */
$Factory = new Factory([
	'cli_title'   => 'Copy random files [src_dir] => [out_dir] with quantity limit and priority',
	'app_usage'   => sprintf( '%s --config <user-config.php> [options]', basename( __FILE__ ) ),
	'app_args'    => [
		'config' => [ 'short' => 'c:', 'long' => 'config:', 'desc' => 'Load additional config from file' ],
	],
	'log_file'    => sprintf( '%s/%s.log', __DIR__, basename( __FILE__ , '.php' ) ),
	'log_level'   => \Orkan\Logger::DEBUG,
	// Module
	'file_types'         => [ 'jpg', 'gif' ],
	'file_priority_mask' => '^_.*',
	'file_quantity'      => 0,
	'limit_delta'        => '5M',
]);
/* @formatter:on */

$App = new Application( $Factory );

/*
 * ---------------------------------------------------------------------------------------------------------------------
 * Start App with user config
 */
if ( null === $cfgFile = $App->getArg( 'config' ) ) {
	echo $App->getHelp();
	throw new InvalidArgumentException( 'Use --config <config.php> to load user settings.' );
}
elseif ( !is_file( $cfgFile ) ) {
	throw new InvalidArgumentException( 'Config file not found: ' . $cfgFile );
}

$Factory->merge( require $cfgFile, true );
$App->run();
$Utils = $Factory->Utils();
$Logger = $Factory->Logger();

// Clear log
getenv( 'APP_RESET' ) && @file_put_contents( $Logger->getFilename(), '' );
$Logger->info( sprintf( "================[%s]================", $Factory->get( 'app_title' ) ) );

/*
 * ---------------------------------------------------------------------------------------------------------------------
 * Verify user config
 */
if ( !$dirSrc = $Factory->get( 'dir_src' ) ) {
	throw new InvalidArgumentException( 'Empty cfg[dir_src]' );
}
elseif ( !is_dir( $dirSrc ) ) {
	throw new InvalidArgumentException( sprintf( 'Source dir not found! cfg[dir_src] = "%s"', $dirSrc ) );
}

if ( !$dirOut = $Utils->pathFix( $Factory->get( 'dir_out' ) ) ) {
	throw new InvalidArgumentException( 'Empty cfg[dir_out]' );
}

/*
 * ---------------------------------------------------------------------------------------------------------------------
 * Find all files in SRC sir
 */
$filesAll = array_diff( scandir( $dirSrc ), [ '..', '.' ] );
$countFilesAll = count( $filesAll );
$Logger->notice( 'Files found: ' . $countFilesAll );

$filesType = array_filter( $filesAll, function ( $file ) {
	global $Utils, $Factory;
	return in_array( $Utils->fileExt( $file ), $Factory->get( 'file_types' ) );
} );
$countFilesType = count( $filesType );
$Logger->notice( sprintf( 'Filter types [%s]: %d', implode( ', ', $Factory->get( 'file_types' ) ), $countFilesType ) );

DEBUG && print ( '$filesAll: ' . print_r( $filesAll, true ) ) ;
DEBUG && print ( '$filesType: ' . print_r( $filesType, true ) ) ;

// Shuffle now, so when usort() returns 0 for equal priority, then values will stay unsorted within each group
// shuffle( $filesType );
$Utils->arrayShuffle( $filesType );
DEBUG && print ( 'shuffle($filesType): ' . print_r( $filesType, true ) ) ;

/*
 * ---------------------------------------------------------------------------------------------------------------------
 * Build list of files to given quote.
 * Randomize results.
 * Make sure priority files are included first.
 */
$priorityMask = sprintf( '~%s~', $Factory->get( 'file_priority_mask' ) );
usort( $filesType, function ( $fileA, $fileB ) use ($priorityMask ) {
	$matchA = preg_match( $priorityMask, $fileA );
	$matchB = preg_match( $priorityMask, $fileB );
	// PHP bug: results are in reversed order. Should be A - B > 0 if A is grather than B
	return $matchB - $matchA;
} );
DEBUG && print ( 'usort($filesType): ' . print_r( $filesType, true ) ) ;

$sizeMax = $Utils->byteNumber( $Factory->get( 'file_quantity' ) );
$quoteTotal = $sizeMax ? $Utils->byteString( $sizeMax ) : 'none';
$Logger->notice( sprintf( 'Quantity limit: %s', $quoteTotal ) );

$sizeOut = $countFilesOut = $sizeSkip = $countFilesSkip = 0;
$filesOut = [];
$filesSkip = [];
$sizeDelta = $Utils->byteNumber( $Factory->get( 'limit_delta' ) );

foreach ( $filesType as $file ) {
	$size = filesize( $dirSrc . '/' . $file );
	$sizeNext = $sizeOut + $size;

	if ( $sizeMax && $sizeNext > $sizeMax ) {
		$sizeSkip += $size;
		$countFilesSkip++;
		$filesSkip[] = $file;

		/* @formatter:off */
		$Logger->debug( sprintf( 'File oversized: %1$s (%2$s --> %3$s)',
			/*1*/ $file,
			/*1*/ $Utils->byteString( $size ),
			/*1*/ $Utils->byteString( $sizeNext ),
		));
		/* @formatter:on */

		// Continue to find smaller file if $limitDelta hasn't been reached yet
		if ( $sizeDelta < $sizeLeft = $sizeMax - $sizeOut ) {
			/* @formatter:off */
			$Logger->debug( sprintf( 'Quantity limit: %1$s. Current size: %2$s. Left: %3$s. Delta: %4$s. Continue...',
				/*1*/ $quoteTotal,
				/*2*/ $Utils->byteString( $sizeOut ),
				/*3*/ $Utils->byteString( $sizeLeft ),
				/*4*/ $Utils->byteString( $sizeDelta ),
			));
			/* @formatter:on */
			continue;
		}

		/* @formatter:off */
		$Logger->debug( sprintf( 'Size left: %1$s < Delta: %2$s. Aborting...',
			$Utils->byteString( $sizeLeft ),
			$Utils->byteString( $sizeDelta ),
		));
		/* @formatter:on */
		break;
	}
	$countFilesOut++;
	$sizeOut += $size;
	$filesOut[] = $file;

	/* @formatter:off */
	$Logger->notice( sprintf( '%1$d. %2$s (%3$s --> %4$s)',
		/*1*/ $countFilesOut,
		/*2*/ $file,
		/*3*/ $Utils->byteString( $size ),
		/*4*/ $Utils->byteString( $sizeOut ),
		/*5*/ $quoteTotal,
	));
	/* @formatter:on */
}

if ( $countFilesSkip ) {
	/* @formatter:off */
	$Logger->notice( sprintf( 'Quantity limit reached (with delta: %1$s). Files skiped: %2$d',
		/*1*/ $Utils->byteString( $sizeDelta ),
		/*2*/ $countFilesType - $countFilesOut,
	));
	$Logger->debug( sprintf( 'Rejected files: %1$d (%2$s)',
		/*1*/ $countFilesSkip,
		/*2*/ $Utils->byteString( $sizeSkip ),
	));
	/* @formatter:on */
	foreach ( $filesSkip as $k => $file ) {
		$size = filesize( $dirSrc . '/' . $file );
		$Logger->debug( sprintf( '%1$d. %2$s (%3$s)', $k + 1, $file, $Utils->byteString( $size ) ) );
	}
}

DEBUG && print ( '$filesOut: ' . print_r( $filesOut, true ) ) ;

/*
 * ---------------------------------------------------------------------------------------------------------------------
 * Copy files to OUT dir
 */
$Logger->notice( sprintf( 'Ready to copy files to "%s"', $dirOut ) );
$Utils->prompt( 'Erase destination dir? Press any key to continue...' );

if ( !$isDryRun = null !== $App->getArg( 'dry-run' ) ) {
	$Logger->debug( sprintf( 'Erase cfg[dir_out]: "%s"', $dirOut ) );
	$Utils->dirClear( $dirOut );
	$dirSrc = realpath( $dirSrc );
	$dirOut = realpath( $dirOut );
}

foreach ( $filesOut as $k => $file ) {
	$Logger->debug( sprintf( 'copy: "%s" ==> "%s"', $src = $dirSrc . '/' . $file, $dst = $dirOut . '/' . $file ) );

	/* @formatter:off */
	$Logger->notice( $title = sprintf( '[%1$d/%2$d] %3$s%4$s (%5$s)',
		/*1*/ $k + 1,
		/*2*/ $countFilesOut,
		/*3*/ $file,
		/*4*/ $isDryRun ? '*' : '',
		/*5*/ $Utils->byteString( filesize( $src ) ),
	 ));
	/* @formatter:on */

	$App->setCliTitle( $title );
	!$isDryRun && copy( $src, $dst );
}

$Logger->notice( 'Done.' );
