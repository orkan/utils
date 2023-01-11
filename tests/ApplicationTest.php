<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2023 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

use PHPUnit\Framework\TestCase;

/**
 * Test Utils\Factory.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class ApplicationTest extends TestCase
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
	public function testCanGetCommandLineArg()
	{
		$nameS = 't';
		$nameL = 'exclude-group';
		$value = 'dummy_arg';


		/* @formatter:off */
		$App = new Application( new Factory([
			'app_args' => [
				'arg1' => [ 'short' => "{$nameS}:", 'long' => "{$nameL}", 'desc' => "Testing --$nameL=$value" ],
			],
		]));
		/* @formatter:on */

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
}
