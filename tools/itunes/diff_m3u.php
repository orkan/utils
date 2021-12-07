<?php
$basename = basename( $argv[0] );

if ( 3 > $argc ) {
	die( "
Find difference between two playlists.

Usage:        {$basename} <playlist.m3u8> <playlist.m3u8>

" );
}

$a1 = file( $argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
$a2 = file( $argv[2], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

// Find main playlist (always substract from bigger playlist)
if ( count( $a1 ) >= count( $a2 ) ) {
	$main = &$a1;
	$skip = &$a2;
}
else {
	$main = &$a2;
	$skip = &$a1;
}

$lines = array_diff( noExt( $main ), noExt( $skip ) );

foreach ( $lines as $line ) {
	echo $line . "\n";
}

// Skip #EXTINF lines
function noExt( $a )
{
	$o = [];

	foreach ( $a as $line ) {
		if ( 0 === strpos( $line, '#' ) ) {
			continue;
		}

		$o[] = $line;
	}

	return $o;
}
