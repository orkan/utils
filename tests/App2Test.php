<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Tests\App2\Application;
use Orkan\Tests\App2\Factory;
use PHPUnit\Framework\TestCase;

/**
 * Test App2.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class App2Test extends TestCase
{

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Create App2.
	 */
	public function testCanCreateApp2()
	{
		$Factory = new Factory( $cfg = [ 'expect' => 'testing' ] );
		$App2 = new Application( $Factory );

		$this->assertSame( $cfg['expect'], $Factory->get2( 'expect' ) );
		$this->assertSame( $App2::APP_NAME, Application::APP_NAME );
	}
}
