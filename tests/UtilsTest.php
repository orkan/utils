<?php
/*
 * This file is part of the orkan/tvguide package.
 *
 * Copyright (c) 2020 Orkan <orkans@gmail.com>
 */
use Orkan\Utils;
use PHPUnit\Framework\TestCase;

/**
 * Importers test suite
 *
 * @author Orkan <orkans@gmail.com>
 */
class UtilsTest extends TestCase
{

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	protected function setUp(): void
	{
	}

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	public function sizeStringProvider()
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
	 * Convert size string to bytes
	 * @dataProvider sizeStringProvider
	 * @group single
	 */
	public function testConvertToBytes( $str, $expected )
	{
		$actual = Utils::toBytes( $str );
		$this->assertSame( $expected, $actual );
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
				'key2.2' => new Prop(),
				'key2.3' => 'ccc',
			],
			'key3' => new Prop( $needle ),
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
}
class Prop
{
	public $prop;

	public function __construct( $set = 'property' )
	{
		$this->prop = $set;
	}
}

