<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Tests\App2\Factory as App2Factory;
use Orkan\Tests\App3\Application as App3;

/**
 * Test: Orkan\Application3.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Application3Test extends \Orkan\Tests\TestCase
{

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Create App3.
	 */
	public function testCanCreateApp()
	{
		$Factory = new App2Factory();
		$App3 = new App3( $Factory );
		$this->assertSame( App3::APP_NAME, $App3::APP_NAME );
	}

	/**
	 * Merge defaults: App3 > App2 > App.
	 */
	public function testCanMergeDefaults()
	{
		$Factory = new App2Factory();
		new App3( $Factory );

		$this->assertSame( 'app.php [options]', $Factory->cfg( 'app_usage' ), 'App1: cfg[app_usage]' );
		$this->assertSame( 'App 2 custom prop', $Factory->cfg( 'app2_prop' ), 'App2: cfg[app2_prop]' );
		$this->assertSame( 'CLI Application 3', $Factory->cfg( 'app_title' ), 'App3: cfg[app_title]' );

		/* @formatter:off */
		$expect = [
			'app2_ext1' => false, // App2
			'app2_ext2' => false, // App2
			'app3_ext1' => false, // App3
		];
		/* @formatter:on */

		$this->assertSame( $expect, $Factory->cfg( 'app_php_ext' ) );
	}
}
