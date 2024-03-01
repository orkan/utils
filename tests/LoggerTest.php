<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Factory;
use Orkan\Logger;

/**
 * Test: Orkan\Logger.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class LoggerTest extends TestCase
{
	const USE_SANDBOX = true;

	/**
	 * Skip all tests if Monolog is not installed.
	 * @var bool
	 */
	private static $isMonolog;

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * {@inheritDoc}
	 */
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		$Logger = new Logger( new Factory() );
		self::$isMonolog = null !== $Logger->Monolog();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void
	{
		parent::setUp();

		if ( !self::$isMonolog ) {
			$this->markTestSkipped( 'The Monolog class is not available.' );
		}
	}

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Verbose log message.
	 */
	public function testCanVerbose()
	{
		$message = uniqid();
		$this->expectExceptionMessage( $message );

		/* @formatter:off */
		$Factory = new Factory([
			'log_verbose' => 'INFO',
		]);
		/* @formatter:on */

		$Factory->Logger()->info( $message );
	}

	/**
	 * Skip log message to file.
	 */
	public function testCanFileSkip()
	{
		$Logger = new Logger( new Factory() );
		$Logger->info( __FUNCTION__ );

		$this->assertFileDoesNotExist( $Logger->getFilename() );
	}

	/**
	 * Log message to file.
	 */
	public function testCanFileWrite()
	{
		/* @formatter:off */
		$cfg = [
			'log_file' => self::sandboxPath( '%s.log', __FUNCTION__ ),
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg );
		$Logger = new Logger( $Factory );

		$message = uniqid();
		$Logger->warning( $message );

		$file = $Logger->getFilename();
		$this->assertStringContainsString( $message, file_get_contents( $file ) );
	}
}
