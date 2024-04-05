<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Application;

/**
 * Test: Orkan\Application.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class ApplicationTest extends \Orkan\Tests\TestCase
{

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Get cmd line arg.
	 *
	 * @todo No solution to pass cmd line args to PHPUnit so far
	 * @link https://stackoverflow.com/questions/39659602/send-parameter-to-php-unit-on-the-command-line
	 */
	public function _testCanGetCommandLineArg()
	{
		$nameS = 't';
		$nameL = 'exclude-group';
		$value = 'dummy_arg';

		/* @formatter:off */
		$cfg = [
			'app_opts' => [
				'arg1' => [ 'short' => "{$nameS}:", 'long' => "{$nameL}", 'desc' => "Testing --$nameL=$value" ],
			],
		];
		/* @formatter:on */

		$App = new Application( new FactoryMock( $this, $cfg ) );

		/**
		 * SOLUTION 1:
		 * Modifying $argv now won't change PHP::getopt() results, since the cmd line args are read on PHP init anyway.
		 * @link https://stackoverflow.com/questions/32897726/how-to-modify-the-command-line-arguments-before-getopt
		 $GLOBALS['argv'][] = "-$nameS";
		 $GLOBALS['argv'][] = $value;
		 $GLOBALS['argc'] += 2;
		 $GLOBALS['argv'][] = "--$nameL";
		 $GLOBALS['argv'][] = $value;
		 $GLOBALS['argc'] += 2;
		 $GLOBALS['argv'][] = "--$nameL=\"$value\"";
		 $GLOBALS['argc'] += 1;
		 $actual = $App->getArg( $nameS );
		 $actual = $App->getArg( $nameL );
		 $this->assertSame( $value, $actual );
		 */

		// Dummy test for now...
		$this->assertSame( null, $App->getArg( 'testing' ) );
	}

	/**
	 * Set PHP ini.
	 */
	public function testCanPhpIniSet()
	{
		/* @formatter:off */
		$expect = [
			[ 'log_errors_max_len' => 1111, 'html_errors' => 'On' , 'precision' => '1' ],
			[ 'log_errors_max_len' => 2222, 'html_errors' => 'Off', 'precision' => '2' ],
		];
		/* @formatter:on */

		foreach ( $expect as $php ) {
			/**
			 * ini_set()
			 * @see Application::__construct()
			 */
			new Application( new FactoryMock( $this, [ 'php' => $php ] ) );

			foreach ( $php as $k => $v ) {
				$this->assertEquals( $v, ini_get( $k ) ); // dont check types!
			}
		}
	}

	/**
	 * Skip setting PHP ini.
	 */
	public function testCanPhpIniSkip()
	{
		$ini = [ 'log_errors_max_len', 'log_errors' ];
		$expect = $actual = [];

		/**
		 * All NULL php settings must be left untouched!
		 * @see Application::__construct()
		 */
		foreach ( $ini as $k ) {
			$expect[$k] = ini_get( $k );
			$actual[$k] = null;
		}

		new Application( new FactoryMock( $this, [ 'php' => $actual ] ) );

		foreach ( $expect as $k => $v ) {
			$this->assertEquals( $v, ini_get( $k ) ); // dont check types!
		}
	}
}
