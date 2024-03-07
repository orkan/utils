<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Factory;

/**
 * Test: Orkan\Factory.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class FactoryTest extends TestCase
{
	const USE_SANDBOX = true;

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Merge defaults.
	 */
	public function testCanMergeConfig()
	{
		/* @formatter:off */
		$cfg = [
			'factory_test_cfg_1' => 'Cfg 1', // initial value
			'factory_test_cfg_2' => 'Cfg 2',
		];
		$opt = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // do NOT replace if already set
			'factory_test_opt_1' => 'Opt 1',
		];
		$expect = [
			'factory_test_cfg_1' => 'Cfg 1', // not replaced
			'factory_test_cfg_2' => 'Cfg 2',
			'factory_test_opt_1' => 'Opt 1',
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg ); // merge Factory self config with $cfg
		$Factory->merge( $opt ); // merge $opt

		foreach ( $expect as $name => $value ) {
			$this->assertSame( $value, $Factory->get( $name ) );
		}
	}

	/**
	 * Replace defaults.
	 */
	public function testCanMergeConfigForce()
	{
		/* @formatter:off */
		$cfg = [
			'factory_test_cfg_1' => 'Cfg 1', // initial value
			'factory_test_cfg_2' => 'Cfg 2',
		];
		$opt = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // replace if force == true!
			'factory_test_opt_1' => 'Opt 1',
		];
		$expect = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // replaced!
			'factory_test_cfg_2' => 'Cfg 2',
			'factory_test_opt_1' => 'Opt 1',
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg ); // merge Factory self config with $cfg
		$Factory->merge( $opt, true ); // replace with $opt

		foreach ( $expect as $name => $value ) {
			$this->assertSame( $value, $Factory->get( $name ) );
		}
	}

	/**
	 * Merge defaults (multi-dimensional).
	 */
	public function testCanMergeMultiConfig()
	{
		/* @formatter:off */
		$cfg = [
			'factory_test_cfg_1' => 'Cfg 1', // initial value
			'factory_test_cfg_2' => [
				'cfg_2_1' => 'Cfg 2.1',
				'cfg_2_2' => 'Cfg 2.2', // initial value
			],
		];
		$opt = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // do NOT replace if already set
			'factory_test_cfg_2' => [
				'cfg_2_2' => 'Cfg 2.2 (opt)', // do NOT replace if already set
				'cfg_3_1' => 'Cfg 3.1',
			],
		];
		$expect = [
			'factory_test_cfg_1' => 'Cfg 1', // not replaced
			'factory_test_cfg_2' => [
				'cfg_2_2' => 'Cfg 2.2', // not replaced
				'cfg_3_1' => 'Cfg 3.1',
				'cfg_2_1' => 'Cfg 2.1',
			],
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg ); // merge Factory self config with $cfg
		$Factory->merge( $opt ); // merge $opt

		foreach ( $expect as $name => $value ) {
			$this->assertSame( $value, $Factory->get( $name ) );
		}
	}

	/**
	 * Replace defaults (multi-dimensional).
	 */
	public function testCanMergeMultiConfigForce()
	{
		/* @formatter:off */
		$cfg = [
			'factory_test_cfg_1' => 'Cfg 1', // initial value
			'factory_test_cfg_2' => [
				'cfg_2_1' => 'Cfg 2.1',
				'cfg_2_2' => 'Cfg 2.2', // initial value
			],
		];
		$opt = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // replaced
			'factory_test_cfg_2' => [
				'cfg_2_2' => 'Cfg 2.2 (opt)', // replaced
				'cfg_3_1' => 'Cfg 3.1',
			],
		];
		$expect = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // replaced
			'factory_test_cfg_2' => [
				'cfg_2_1' => 'Cfg 2.1',
				'cfg_2_2' => 'Cfg 2.2 (opt)', // replaced
				'cfg_3_1' => 'Cfg 3.1',
			],
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg ); // merge Factory self config with $cfg
		$Factory->merge( $opt, true ); // replace with $opt

		foreach ( $expect as $name => $value ) {
			$this->assertSame( $value, $Factory->get( $name ) );
		}
	}

	/**
	 * @see FactoryTest::testCanLog()
	 */
	public function provideLogs()
	{
		/* @formatter:off */
		return [
			//                | format                | tokens | out
			'hr - default' => [ '-'                   , null   , str_repeat( '-', 80 )   ],
			'hr = x4'      => [ '='                   , 4      , str_repeat( '=', 4 )    ],
			'no tokens'    => [ 'Line 1 no tokens'    , []     , 'Line 1 no tokens'      ],
			'one token'    => [ 'Line 2 with "%s" arg', 'one'  , 'Line 2 with "one" arg' ],
		];
		/* @formatter:on */
	}

	/**
	 * Log message with tokens.
	 * @dataProvider provideLogs
	 */
	public function testCanLog( $format, $tokens, string $expect )
	{
		/* @formatter:off */
		$cfg = [
			'log_file'    => self::sandboxPath( '%s.log', __FUNCTION__ ),
			'log_history' => 'DEBUG',
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg );
		$Logger = $Factory->Logger();
		$Monolog = $Logger->Monolog();

		$method = [ 'debug', 'info', 'notice', 'warning', 'error' ];
		$method = $method[rand( 0, count( $method ) - 1 )];
		$level = $Monolog->toMonologLevel( $method );

		$Factory->$method( $format, $tokens );

		$actual = $Logger->getHistory();
		$this->assertSame( $level, $actual[0]['level'] );
		$this->assertSame( $expect, $actual[0]['message'] );
	}

	/**
	 * Log messages array.
	 */
	public function testCanLogArray()
	{
		/* @formatter:off */
		$cfg = [
			'log_level' => 'DEBUG',
			'log_file'  => self::sandboxPath( '%s.log', __FUNCTION__ ),
		];
		$tokens = [
			'%one%' => 'f i r s t',
			'%two%' => 'second',
		];
		$format = [
			'Line 1 no args',
			'Line 2 "%one%" arg',
			'Line 3, [%one%] and [%two%] args',
		];
		$expects = [
			'Line 1 no args',
			'Line 2 "f i r s t" arg',
			'Line 3, [f i r s t] and [second] args',
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg );
		$Logger = $Factory->Logger();
		$Monolog = $Logger->Monolog();

		$method = [ 'debug', 'info', 'notice', 'warning', 'error' ];
		$method = $method[rand( 0, count( $method ) - 1 )];
		$level = $Monolog->getLevelName( $Monolog->toMonologLevel( $method ) );

		$Factory->$method( $format, $tokens );

		// Check messages
		$actual = file_get_contents( $Logger->getFilename() );
		foreach ( $expects as $k => $expect ) {
			$this->assertStringContainsString( $expect, $actual, $format[$k] );
		}

		// Check level name in log: info(message) --> INFO: message
		$this->assertStringContainsString( $level, $actual );
	}
}
