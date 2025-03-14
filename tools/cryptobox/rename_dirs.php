<?php
$batFile = basename( $argv[0], 'php' ) . 'bat';
$dvrDir = $argv[1] ?? '';
$dvrDir = trim( $dvrDir, '"' );

if ( !is_dir( $dvrDir ) ) {
	echo "Rename CryptoBox auto-created DVR subdirs to be sortable by date.
New name format: [date] Channel - Program name

Usage:   {$batFile} <ALIDVRS2 dir>
Example: {$batFile} X:\\ALIDVRS2
";
	error( sprintf( 'DVR dir not found "%s"', $dvrDir ) );
}

printf( 'Looking for DVR subdirs in "%s"%s', $dvrDir, "\n" );

// =====================================================================================================================
// Create new path names
$paths = [];
$Dir = new DirectoryIterator( $dvrDir );

foreach ( $Dir as $Item ) {
	if ( !$Item->isDir() || $Item->isDot() ) {
		continue;
	}

	$varName = $Item->getFilename();

	if ( !preg_match( '~^\[TS\]~', $varName ) ) {
		continue; // not a DVR dir!
	}

	$varName = substr( $varName, 4 ); // remove "[TS]..." prefix

	// Extract channel, programme, time
	$parts = [];
	preg_match( '~(.+)\[(.+)\]+~', $varName, $parts );
	$tmp = $parts[2];
	$parts[2] = substr( $tmp, 0, -20 ); // Program name
	$parts[3] = substr( $tmp, -19 ); // Time

	// Fix time string, eg. "10-11-2021.09.15.00" => "2021-11-10 09.15"
	$tmp = preg_split( '~[\.-]+~', $parts[3] );
	$parts[3] = "{$tmp[2]}-{$tmp[1]}-{$tmp[0]} {$tmp[3]}.{$tmp[4]}";

	/* @formatter:off */
	$paths[] = [
		'old' => $Item->getFilename(),
		'new' => "[{$parts[3]}] $parts[1] - $parts[2]",
	];
	/* @formatter:on */
}

if ( empty( $paths ) ) {
	error( 'No DVR subdirs found!' );
}

printf( "Found %d dirs.\n\n", count( $paths ) );

// =====================================================================================================================
// Rename subdirs
echo "Rename:\n";
foreach ( $paths as $path ) {
	$pathOld = $dvrDir . '/' . $path['old'];
	$pathNew = $dvrDir . '/' . $path['new'];

	echo "
old => {$path['old']}
new => {$path['new']}
";

	if ( is_dir( $pathNew ) ) {
		error( 'Path already exists. Skipping...', 0 );
		continue;
	}

	rename( $pathOld, $pathNew );
}

// =====================================================================================================================
// Functions
function error( $str, $code = 123 )
{
	if ( $code ) {
		echo "\n" . str_repeat( '-', 74 ) . "\n";
	}

	echo "ERROR: {$str}\n";

	if ( $code ) {
		exit( $code );
	}
}

