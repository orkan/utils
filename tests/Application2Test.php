<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Tests\App2\Factory as App2Factory;
use Orkan\Tests\App2\Application as App2;

/**
 * Test: Orkan\Application2.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Application2Test extends \Orkan\Tests\TestCase
{

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Create App2.
	 */
	public function testCanCreateApp()
	{
		$Factory = new App2Factory( $cfg = [ 'expect' => 'testing' ], $this );
		$App2 = new App2( $Factory );

		$this->assertSame( $cfg['expect'], $Factory->get2( 'expect' ) );
		$this->assertSame( App2::APP_NAME, $App2::APP_NAME );
	}
}
