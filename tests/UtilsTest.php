<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2022 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

use PHPUnit\Framework\TestCase;

/**
 * Test Utils.
 *
 * @author Orkan <orkans@gmail.com>
 */
class UtilsTest extends TestCase
{

	protected function setUp(): void
	{
	}

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Utils::formatTime( $seconds, $precision )
	 */
	public function formatTimeProvider()
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

	public function toBytesProvider()
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

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Convert size string to bytes.
	 *
	 * @dataProvider formatTimeProvider
	 */
	public function testFormatTime( $seconds, $precision, $expect )
	{
		if ( null === $precision ) {
			$actual = Utils::formatTime( $seconds );
		}
		else {
			$actual = Utils::formatTime( $seconds, $precision );
		}

		$this->assertSame( $expect, $actual );
	}

	/**
	 * Convert size string to bytes.
	 *
	 * @dataProvider toBytesProvider
	 */
	public function testToBytes( $str, $expect )
	{
		$actual = Utils::toBytes( $str );
		$this->assertSame( $expect, $actual );
	}

	/**
	 * Print message to log file if defined( 'TESTING' )
	 * See: ./_cmd/phpunit.xml for defined constants
	 * See: ../src/TESTING-Orkan-Utils-print.log for output
	 */
	public function testCanPrint()
	{
		Utils::print( sprintf( "Hello from %s() in %s, line: %d\n", __METHOD__, __FILE__, __LINE__ ) );

		$this->assertTrue( true );
	}

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
	 * Randomize multi-dimensional array and maintain key assigment
	 */
	public function testCanSortMultiArray()
	{
		/* @formatter:off */
		$playlistA = $playlistB = [
			0 => ['line' => 'line0', 'path' => 'path0', 'name' => 'name_3_0' ],
			1 => ['line' => 'line1', 'path' => 'path1', 'name' => 'name_2_1' ],
			3 => ['line' => 'line3', 'path' => 'path3', 'name' => 'name_1_3' ],
		];
		/* @formatter:on */

		// Reverse: by name, asc
		Utils::sortMultiArray( $playlistB, 'name', 'asc' );
		$this->assertEquals( $playlistA, $playlistB, 'Content differs!' );
		$this->assertNotSame( $playlistA, $playlistB, 'Same order!' );
		$this->assertEquals( $playlistA[1], $playlistB[1], 'Lost key assigment!' );

		// Re-create order: by name, desc
		Utils::sortMultiArray( $playlistB, 'name', 'desc' );
		$this->assertEquals( $playlistA, $playlistB, 'Content differs!' );
		$this->assertSame( $playlistA, $playlistB, 'Not same order!' );
		$this->assertEquals( $playlistA[1], $playlistB[1], 'Lost key assigment!' );
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

		// Two chances to randomize array!
		for ( $i = 0; $i < 2; $i++ ) {
			Utils::shuffleArray( $playlistB );
			if ( array_keys( $playlistA ) != array_keys( $playlistB ) ) {
				break;
			}
		}

		$this->assertEquals( $playlistA, $playlistB, 'Content differs!' );
		$this->assertNotSame( $playlistA, $playlistB, 'Same order! Note for false positive results. Try again!' );
		$this->assertEquals( $playlistA[1], $playlistB[1], 'Lost key assigment!' );
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

