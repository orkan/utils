<?php
$batFile = basename( $argv[0], 'php' ) . 'bat';
$srcDir = $argv[1] ?? '';
$srcDir = trim( $srcDir, '"' );

// =====================================================================================================================
// Verify
if ( !is_dir( $srcDir ) ) {
	/* @formatter:off */
	echo strtr(
		<<<'EOT'
		Tool:  FFmpeg show cut positions
		Desc:  Compute cut positions in joined video from video parts filenames
		Usage: {bat} <Videos dir>
		EOT
		, [
		'{bat}' => $batFile,
	]);
	/* @formatter:on */

	error( sprintf( 'Videos dir not found: "%s"', $srcDir ) );
}

echo "Looking for videos in:\n\t{$srcDir}/*.*\n";

// =====================================================================================================================
// Find
$files = [];
foreach ( new DirectoryIterator( $srcDir ) as $Item ) {
	if ( $Item->isFile() ) {
		$files[] = $Item->getFilename();
	}
}

if ( !$files ) {
	error( 'Videos not found!' );
}

sort( $files );

// =====================================================================================================================
// Calc
$dur = [];
$pos = [];
$tot = 0;

foreach ( $files as $file ) {
	$m = [];
	if ( !preg_match( '~\[-ss (\d+)\.(\d+)\.(\d+)\]\[-to (\d+)\.(\d+)\.(\d+)\]~', $file, $m ) ) {
		continue;
	}

	$ss = 0;
	$ss += $m[1] * 3600;
	$ss += $m[2] * 60;
	$ss += $m[3];

	$to = 0;
	$to += $m[4] * 3600;
	$to += $m[5] * 60;
	$to += $m[6];

	$dur[$file] = $to - $ss;
	$pos[$file] = $tot + $dur[$file];

	$tot += $dur[$file];
}

// =====================================================================================================================
// Show
echo "Parts found:\n";
foreach ( $dur as $file => $sec ) {
	$time = sec2Time( $sec );
	echo "\t{$file} ({$time})\n";
}

echo "Playback time:\n";
$time = sec2Time( array_pop( $pos ) ); // remove end time
echo "\t{$time}\n";

echo "Cut positions:\n";
foreach ( $pos as $sec ) {
	$time = sec2Time( $sec );
	echo "\t{$time}\n";
}

// =====================================================================================================================
// Functions
function sec2Time( $s )
{
	$t = round( $s );
	return sprintf( '%02d:%02d:%02d', $t / 3600, floor( $t / 60 ) % 60, $t % 60 );
}

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

