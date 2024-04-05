<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Factory;

/**
 * Test: Orkan\ProgressBar.
 * @see \Orkan\ProgressBar
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class ProgressBarTest extends \Orkan\Tests\TestCase
{

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * ProgressBar::draw() - verbose.
	 */
	public function testCanBarDraw()
	{
		$this->expectExceptionMessage( "[|.........] |  10% | ...anBarDraw" );

		/* @formatter:off */
		$Factory = new Factory([
			'log_verbose'   => 'NOTICE',
			'bar_verbose'   => 'NOTICE',
			'bar_char_done' => '|',
			'bar_char_fill' => '.',
			'bar_format_1'  => '[{bar}] | {cent}% | {text}',
			'bar_size'      => '10',
			'bar_width'     => '34',
		]);
		/* @formatter:on */

		$Bar = $Factory->ProgressBar( 10, 'bar_format_1' );
		$this->assertTrue( $Bar->inc( __FUNCTION__ ) );
	}

	/**
	 * ProgressBar::draw() - no verbose.
	 */
	public function testCanBarDrawNoVerbose()
	{
		/* @formatter:off */
		$Factory = new Factory([
			'log_verbose'   => 'NOTICE',
			'bar_verbose'   => 'INFO', // <-- no verbose! INFO < NOTICE
		]);
		/* @formatter:on */

		$Bar = $Factory->ProgressBar();
		$this->assertFalse( $Bar->inc( __FUNCTION__ ) );
	}
}
