<?php
$baseName = basename( $argv[0] );
$dvrDir = $argv[1] ?? '';
$paths = [];

if ( !is_dir( $dvrDir ) ) {
	die( "
Rename CryptoBox auto-created DVR subdirs to be sortable by date.
The new name will be: [date] Channel - Program name

Usage:        {$baseName} <ALIDVRS2 dir>
Example:      {$baseName} X:\\ALIDVRS2

" );
}

echo "Looking for DVR dirs in \"$dvrDir\"\n";

// =====================================================================================================================
// Create new path names
$Dir = new DirectoryIterator( $dvrDir );
foreach ( $Dir as $Item ) {
	if ( !$Item->isDir() || $Item->isDot() ) {
		continue;
	}

	$path = $Item->getPathname();
	$name = $Item->getFilename();

	if ( preg_match( '~^\[TS\]~', $name ) ) {
		$newName = substr( $name, 4 ); // remove [TS]
	}
	else {
		continue; // no DVR dir
	}

	$parts = [];
	preg_match( '~(.+)\[(.+)\]+~', $newName, $parts );

	$tmp = $parts[2];
	$parts[2] = substr( $tmp, 0, -20 ); // program name
	$parts[3] = substr( $tmp, -19 ); // time

	// Rearange time, eg. "10-11-2021.09.15.00" => "2021-11-10 09.15"
	$tmp = preg_split( '~[\.-]+~', $parts[3] );
	$parts[3] = "{$tmp[2]}-{$tmp[1]}-{$tmp[0]} {$tmp[3]}.{$tmp[4]}";

	$newName = "[{$parts[3]}] $parts[1] - $parts[2]";

	/* @formatter:off */
	$paths[] = [
		'old' => $path,
		'new' => $dvrDir . '/' . $newName,
		'oldName' => $name,
		'newName' => $newName,
	];
	/* @formatter:on */
}

if ( empty( $paths ) ) {
	die( "No DVR dirs found!\n" );
}
else {
	printf( "Found %d dirs.\n\n", count( $paths ) );
}

// =====================================================================================================================
// Rename subdirs

echo "Rename:\n";
foreach ( $paths as $path ) {
	echo "
old => {$path['oldName']}
new => {$path['newName']}
";

	if ( is_dir( $path['new'] ) ) {
		echo "ERROR: Path already exists. Skipping...\n";
		continue;
	}

	rename( $path['old'], $path['new'] );
}
