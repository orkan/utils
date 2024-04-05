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
class FactoryTest extends \Orkan\Tests\TestCase
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
			//                | format                | tokens          | out
			'hr - default' => [ ''                    , null            , str_repeat( '-', 80 )   ],
			'hr = x4'      => [ '='                   , [ '{bar}' => 4 ], str_repeat( '=', 4 )    ],
			'no tokens'    => [ 'Line 1 no tokens'    , []              , 'Line 1 no tokens'      ],
			'one token'    => [ 'Line 2 with "%s" arg', 'one'           , 'Line 2 with "one" arg' ],
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

		$method = [ 'debug', 'info', 'notice', 'warning', 'error' ];
		$method = $method[rand( 0, count( $method ) - 1 )];
		$level = $Logger->toLevel( $method );

		$Factory->$method( $format, $tokens );

		$actual = $Logger->getHistory();
		$this->assertSame( $level, $actual[0]['level'] );
		$this->assertSame( $expect, $actual[0]['message'] );
	}

	/**
	 * Log message bar (use max).
	 */
	public function testCanLogBarMax()
	{
		/* @formatter:off */
		$cfg = [
			'log_file'    => self::sandboxPath( '%s.log', __FUNCTION__ ),
			'log_history' => 'INFO',
			'log_barlen'  => 3,
		];
		$tokens = [
			'{A}' => '[token A]',
			'{B}' => '[[token B]]',
		];
		$logs = [
			'Line 1' => 'One token: {A}',
			'Line 2' => 'Two tokens: {A}, {B}',
			'Bar 0'  => '',
			'Bar 1'  => '*',
			'Bar 2'  => '|',
		];
		/* @formatter:on */

		$max = 0;
		$out = [];

		foreach ( $logs as $k => $v ) {
			$log = strtr( $v, $tokens );
			$len = strlen( $log );
			$max = max( $max, $len );
			$out[$k] = $log;
		}

		// Bar length: compute Max
		$out['Bar 0'] = str_repeat( '-', $max );
		$out['Bar 1'] = str_repeat( $logs['Bar 1'], $max );
		$out['Bar 2'] = str_repeat( $logs['Bar 2'], $max );

		$Factory = new Factory( $cfg );
		$Factory->info( $logs, $tokens );

		$history = $Factory->Logger()->getHistory();
		$this->assertSame( count( $logs ), count( $history ), 'Logs count' );

		$expect = array_values( $out );
		foreach ( $history as $k => $actual ) {
			$this->assertSame( $expect[$k], $actual['message'] );
		}
	}

	/**
	 * Log message bar (use default).
	 */
	public function testCanLogBarMaxDefault()
	{
		/* @formatter:off */
		$cfg = [
			'log_file'    => self::sandboxPath( '%s.log', __FUNCTION__ ),
			'log_history' => 'ERROR',
			'log_barlen'  => 11,
		];
		$logs = [
			'Bar 0'  => '',
			'Bar 1'  => '+',
			'Bar 2'  => '%',
		];
		/* @formatter:on */

		// Bar length: use default
		$out['Bar 0'] = str_repeat( '-', $cfg['log_barlen'] );
		$out['Bar 1'] = str_repeat( $logs['Bar 1'], $cfg['log_barlen'] );
		$out['Bar 2'] = str_repeat( $logs['Bar 2'], $cfg['log_barlen'] );

		$Factory = new Factory( $cfg );
		$Factory->error( $logs );

		$history = $Factory->Logger()->getHistory();
		$this->assertSame( count( $logs ), count( $history ), 'Logs count' );

		$expect = array_values( $out );
		foreach ( $history as $k => $actual ) {
			$this->assertSame( $expect[$k], $actual['message'] );
		}
	}

	/**
	 * Log messages array.
	 */
	public function testCanLogArray()
	{

		/* @formatter:off */
		$cfg = [
			'log_history' => 'debug', // <-- test: isHandling( lowercase ) :-)
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
		$expect = [
			'Line 1 no args',
			'Line 2 "f i r s t" arg',
			'Line 3, [f i r s t] and [second] args',
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg );
		$Logger = $Factory->Logger();

		$method = [ 'debug', 'info', 'notice', 'warning', 'error' ];
		$method = $method[rand( 0, count( $method ) - 1 )];
		$level = $Logger->toLevel( $method );

		$Factory->$method( $format, $tokens );
		$actual = $Logger->getHistory();

		$this->assertCount( count( $expect ), $actual );

		foreach ( array_keys( $expect ) as $k ) {
			$this->assertSame( $expect[$k], $actual[$k]['message'], 'history[message]' );
			$this->assertSame( $level, $actual[$k]['level'], 'history[level]' );
		}
	}
}
