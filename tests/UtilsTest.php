<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Utils;

/**
 * Test Utils.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class UtilsTest extends TestCase
{

	protected function setUp(): void
	{
	}

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * @see Utils::timeString( $seconds, $precision )
	 */
	public function provideTimes()
	{
		/* @formatter:off */
		return [
			// second
			'(  0     ,    0 )'      => [  0     ,    0,  '0s'     ],
			'(  1     ,    0 )'      => [  1     ,    0,  '1s'     ],
			'(  1     ,    1 )'      => [  1     ,    1,  '1.0s'   ],
			'(  1.2   ,    1 )'      => [  1.2   ,    1,  '1.2s'   ],
			'(  1.25  ,    1 )'      => [  1.25  ,    1,  '1.2s'   ], // round down
			'(  1.26  ,    1 )'      => [  1.26  ,    1,  '1.3s'   ], // round up
			'( -1.25  ,    1 )'      => [ -1.25  ,    1, '-1.2s'   ], // round down
			'( -1.26  ,    1 )'      => [ -1.26  ,    1, '-1.3s'   ], // round up
			'(  1.2345,    0 )'      => [  1.2345,    0,  '1s'     ],
			'(  1.2345, null )'      => [  1.2345, null,  '1.234s' ], // def. 3
			'( -3.453 ,    2 )'      => [ -3.453 ,    2, '-3.45s'  ], // round down
			'( -3.457 ,    2 )'      => [ -3.457 ,    2, '-3.46s'  ], // round up
			// minute
			'(  123.45,    0 )'      => [  123.45,    0,  '2m 3s'     ],
			'(  123.45,    2 )'      => [  123.45,    2,  '2m 3.45s'  ],
			'( -123.45,    2 )'      => [ -123.45,    2, '-2m 3.45s'  ],
			'( -123.45,    3 )'      => [ -123.45,    3, '-2m 3.450s' ],
			'( -123.45, null )'      => [ -123.45, null, '-2m 3.450s' ], // def. 3
			// hour
			'( 3600   , 0 )'         => [ 3600   ,    0,  '1h'       ],
			'( 3601   , 1 )'         => [ 3601   ,    1,  '1h 1.0s'  ],
			'( 3601.51, 2 )'         => [ 3601.51,    2,  '1h 1.51s' ],
			// day
			'( 86400   ,    0 )'     => [ 86400   ,    0,  '1d'              ],
			'( 86401   ,    1 )'     => [ 86401   ,    1,  '1d 1.0s'         ],
			'( 86401   ,    2 )'     => [ 86401   ,    2,  '1d 1.00s'        ],
			'( 88560   ,    2 )'     => [ 88560   ,    2,  '1d 36m'          ],
			'( 88564   ,    2 )'     => [ 88564   ,    2,  '1d 36m 4.00s'    ],
			'( 93784   ,    0 )'     => [ 93784   ,    0,  '1d 2h 3m 4s'     ],
			'( 93784   , null )'     => [ 93784   , null,  '1d 2h 3m 4.000s' ],  // def. 3
			'( 86402.34, null )'     => [ 86402.34, null,  '1d 2.340s'       ],  // def. 3
		];
		/* @formatter:on */
	}

	/**
	 * Convert size string to bytes.
	 *
	 * @dataProvider provideTimes
	 */
	public function testFormatTime( $seconds, $precision, $expect )
	{
		if ( null === $precision ) {
			$actual = Utils::timeString( $seconds );
		}
		else {
			$actual = Utils::timeString( $seconds, $precision );
		}

		$this->assertSame( $expect, $actual );
	}

	public function provideBytes()
	{
		/* @formatter:off */
		return [
			'bytes: 12'               => [ 'in' => '12'      , 'to' => 12            ],
			'bytes 1025b'             => [ 'in' => '1025b'   , 'to' => 1025          ],
			'kibibytes fract (0.5kB)' => [ 'in' => '0.5kB'   , 'to' => 512           ],
			'kibibytes (1KB)'         => [ 'in' => '1KB'     , 'to' => 1024          ],
			'kibibytes (10 K)'        => [ 'in' => '10 K'    , 'to' => 10240         ],
			'mebibytes (1000K)'       => [ 'in' => '1000K'   , 'to' => 1024000       ],
			'mebibytes (1M)'          => [ 'in' => '1M'      , 'to' => 1048576       ],
			'mebibytes (1.2 MB)'      => [ 'in' => '1.2 MB'  , 'to' => 1258291       ], // 1258291.2
			'mebibytes (1025 MB)'     => [ 'in' => '1025 MB' , 'to' => 1074790400    ],
			'mebibytes (1024MB)'      => [ 'in' => '1024MB'  , 'to' => 1073741824    ],
			'gibibytes (1GB)'         => [ 'in' => '1GB'     , 'to' => 1073741824    ],
			'tebibytes (1T)'          => [ 'in' => '1T'      , 'to' => 1099511627776 ],
		];
		/* @formatter:on */
	}

	/**
	 * Convert size string to bytes.
	 *
	 * @dataProvider provideBytes
	 */
	public function testToBytes( $str, $expect )
	{
		$this->assertSame( $expect, Utils::byteNumber( $str ) );
	}

	/**
	 * Fancy print_r().
	 */
	public function testCanPrintR()
	{
		$needle = 'Hello World!';

		/* @formatter:off */
		$a = [
			'key1' => 'aaa',
			'key2' => [
				'key2.1' => 'bbb',
				'key2.2' => new PrintR(),
				'key2.3' => 'ccc',
			],
			'key3' => new PrintR( $needle ),
			'key4' => 'ddd',
		];
		/* @formatter:on */

		// Don't exclude Objects so PHP::print_r() will parse each property in output
		$result = Utils::print_r( $a, false );
		$this->assertStringContainsString( $needle, $result, 'Missing Object property in output' );

		// Replace each Object in array with class name string
		$result = Utils::print_r( $a );
		$this->assertStringNotContainsString( $needle, $result, 'Missing Object property in output' );
	}

	/**
	 * Sort multi-dimensional array by string value and maintain key assigment.
	 */
	public function testCanSortMultiArrayByStringValue()
	{
		/* @formatter:off */
		$playlistA = $playlistB = [
			0 => ['line' => 'line0', 'path' => 'path0', 'name' => 'name_3_0' ],
			1 => ['line' => 'line1', 'path' => 'path1', 'name' => 'name_2_1' ],
			3 => ['line' => 'line3', 'path' => 'path3', 'name' => 'name_1_3' ],
		];
		/* @formatter:on */

		// Reverse: by name, asc
		Utils::arraySortMulti( $playlistB, 'name' );
		$this->assertEquals( $playlistA, $playlistB, 'Content differs!' );
		$this->assertNotSame( $playlistA, $playlistB, 'Same order!' );
		$this->assertEquals( $playlistA[1], $playlistB[1], 'Lost key assigment!' );

		// Re-create order: by name, desc
		Utils::arraySortMulti( $playlistB, 'name', false );
		$this->assertEquals( $playlistA, $playlistB, 'Content differs!' );
		$this->assertSame( $playlistA, $playlistB, 'Not same order!' );
		$this->assertEquals( $playlistA[1], $playlistB[1], 'Lost key assigment!' );
	}

	/**
	 * Sort multi-dimensional array by bolean value and maintain key assigment.
	 */
	public function testCanSortMultiArrayBoleanValue()
	{
		/* @formatter:off */
		$data = [
			0 => [ 'ready' => true  ],
			1 => [ 'ready' => false ],
			2 => [ 'ready' => true  ],
		];
		$expectAsc = [
			1 => [ 'ready' => false ],
			0 => [ 'ready' => true  ],
			2 => [ 'ready' => true  ],
		];
		$expectDesc = [
			0 => [ 'ready' => true  ],
			2 => [ 'ready' => true  ],
			1 => [ 'ready' => false ],
		];
		/* @formatter:on */

		$arr = $data; // copy
		Utils::arraySortMulti( $arr, 'ready' );
		$this->assertSame( $expectAsc, $arr, 'Sort ASC' );

		$arr = $data; // copy
		Utils::arraySortMulti( $arr, 'ready', false );
		$this->assertSame( $expectDesc, $arr, 'Sort DESC' );
	}

	/**
	 * Randomize multi-dimensional array and maintain key assigment
	 * Note for false positive shuffle results b/c it may return exactly the same order too!
	 */
	public function testCanShuffleArray()
	{
		/* @formatter:off */
		$playlistA = $playlistB = [
			0 => ['line' => 'line0', 'path' => 'path0', 'name' => 'name0' ],
			1 => ['line' => 'line1', 'path' => 'path1', 'name' => 'name1' ],
			3 => ['line' => 'line3', 'path' => 'path3', 'name' => 'name3' ],
		];
		/* @formatter:on */

		// More chances to randomize array!
		for ( $i = 0; $i < 10; $i++ ) {
			Utils::arrayShuffle( $playlistB );
			if ( array_keys( $playlistA ) != array_keys( $playlistB ) ) {
				break;
			}
		}

		$this->assertEquals( $playlistA, $playlistB, 'Content differs!' );
		$this->assertNotSame( $playlistA, $playlistB, 'Same order! Note for false positive results. Try again!' );
		$this->assertEquals( $playlistA[1], $playlistB[1], 'Lost key assigment!' );
	}

	/**
	 *
	 */
	public function provideHttpHeaders()
	{
		/* @formatter:off */
		return [
			[ [ 'a: aaa', 'b: bbb' ],           [ 'a: xxx' ],   ': ',        [ 'a: xxx', 'b: bbb' ] ],
			[   [ 'a=aaa', 'b=bbb' ],   [ 'b=xxx', 'c=ccc' ],    '=', [ 'a=aaa', 'b=xxx', 'c=ccc' ] ],
			[ [ 'a -> 1', 'b -> 2' ],                     [], ' -> ',        [ 'a -> 1', 'b -> 2' ] ],
			[                     [], [ 'a -> 1', 'b -> 2' ], ' -> ',        [ 'a -> 1', 'b -> 2' ] ],
		];
		/* @formatter:on */
	}

	/**
	 * Merge exploded values.
	 *
	 * @dataProvider provideHttpHeaders
	 */
	public function testArrayMergeValues( $a1, $a2, $delimiter, $expect )
	{
		$this->assertSame( $expect, Utils::arrayMergeValues( $a1, $a2, $delimiter ) );
	}

	/**
	 * exif[GPS]
	 * @link https://www.gps-coordinates.net/gps-coordinates-converter
	 */
	public function provideExifGps()
	{
		/* @formatter:off */
		return [
			'N 54, E 18' => [
				[
					'GPSLatitudeRef'  => 'N', 'GPSLatitude'  => [ '54/1', '24/1', '29/1' ],
					'GPSLongitudeRef' => 'E', 'GPSLongitude' => [ '18/1', '37/1', '12/1' ],
				],
				[ 'lat' => 54.408056, 'lon' => 18.62 ],
			],
			'N 56, W 158' => [
				[
					'GPSLatitudeRef'  => 'N', 'GPSLatitude'  => [  '56/1', '59/1', '45865/1000' ],
					'GPSLongitudeRef' => 'W', 'GPSLongitude' => [ '158/1', '44/1', '1607/1000' ],
				],
				[ 'lat' => 56.996074, 'lon' => -158.733780 ],
			],
			'S 0, e 2' => [
				[
					'GPSLatitudeRef'  => 'S', 'GPSLatitude'  => [ '0/1', '28/1', '761/100'    ],
					'GPSLongitudeRef' => 'e', 'GPSLongitude' => [ '2/1', '38/1', '267/1000' ],
				],
				[ 'lat' => -0.468781, 'lon' => 2.633408 ],
			],
			's 35, E 146' => [
				[
					'GPSLatitudeRef'  => 's', 'GPSLatitude'  => [  '35/1', '19/1', '10726/1000' ],
					'GPSLongitudeRef' => 'E', 'GPSLongitude' => [ '146/1',  '4/1', '15267/1000' ],
				],
				[ 'lat' => -35.319646, 'lon' => 146.070908 ],
			],
			'S 51, W 71' => [
				[
					'GPSLatitudeRef'  => 'S', 'GPSLatitude'  => [ '51/1', '53/1', '17387/1000' ],
					'GPSLongitudeRef' => 'W', 'GPSLongitude' => [ '71/1', '43/1', '19419/1000' ],
				],
				[ 'lat' => -51.888163, 'lon' => -71.722061 ],
			],
		];
		/* @formatter:on */
	}

	/**
	 * Convert EXIF gps data to decimal location.
	 *
	 * @dataProvider provideExifGps
	 */
	public function testExifGpsToLoc( $gps, $expect )
	{
		$this->assertSame( $expect, Utils::exifGpsToLoc( $gps ) );
	}

	/**
	 * Convert EXIF with invalid gps data.
	 */
	public function testExifGpsInvalid()
	{
		$exif = [ 'TestCase' => __METHOD__ ];
		$this->assertSame( [], Utils::exifGpsToLoc( $exif ) );

		$exif['GPSLatitude'] = [ '54/1', '24/1', '29/1' ];
		$this->assertSame( [], Utils::exifGpsToLoc( $exif ) );

		$exif['GPSLongitude'] = [ '5/4/1', '24/1', '29/1' ];
		$this->assertSame( [], Utils::exifGpsToLoc( $exif ) );

		$exif['GPSLongitude'] = [ '54/1' ];
		$this->assertSame( [], Utils::exifGpsToLoc( $exif ) );

		$exif['GPSLatitudeRef'] = 'U';
		$this->assertSame( [], Utils::exifGpsToLoc( $exif ) );

		$exif = $this->provideExifGps()['S 51, W 71'][0];
		unset( $exif['GPSLongitude'][1] );
		$this->assertSame( [], Utils::exifGpsToLoc( $exif ) );

		$exif = $this->provideExifGps()['N 54, E 18'][0];
		$exif['GPSLatitudeRef'] = 'X';
		$this->assertSame( [], Utils::exifGpsToLoc( $exif ) );
	}
}

/**
 * Dummy class to show printing abilities
 */
class PrintR
{
	public $prop;

	public function __construct( $set = 'property' )
	{
		$this->prop = $set;
	}
}

