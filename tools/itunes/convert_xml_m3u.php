<?php
$basename = basename( $argv[0] );

if ( $argc < 2 ) {
	die( "
Convert iTunes xml playlist to m3u8

Usage:        {$basename} <playlist.xml>

" );
}

$infile = $argv[1];
$lines = file( $infile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

foreach ( $lines as $line ) {

	$matches = [];
	preg_match( '~<key>Location</key><string>file://localhost/(.*)</string>~i', $line, $matches );

	if ( 2 > count( $matches ) ) {
		continue;
	}

	$path = $matches[1];
	$path = html_entity_decode( $path, ENT_XHTML );
	$path = rawurldecode( $path );
	$path = str_replace( '/', '\\', $path );

	echo $path . "\n";
}
