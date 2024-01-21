<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Factory;
use Orkan\Logger;

/**
 * Test Logger.
 *
 * @see \Orkan\Logger
 * @author Orkan <orkans+utils@gmail.com>
 */
class LoggerTest extends TestCase
{

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Verbose log message.
	 */
	public function testCanVerbose()
	{
		$this->expectExceptionMessage( __FUNCTION__ );

		/* @formatter:off */
		$Factory = new Factory( [
			'log_file'    => '',
			'log_verbose' => Logger::INFO,
		]);
		/* @formatter:on */

		$Factory->Logger()->info( __FUNCTION__ );
	}

	/**
	 * Skip log message to file.
	 */
	public function testCanFileSkip()
	{
		/* @formatter:off */
		$Factory = new Factory( [
			'log_file'    => '',
			'log_verbose' => Logger::NONE,
		]);
		/* @formatter:on */

		$Logger = $Factory->Logger();
		$Logger->info( __FUNCTION__ );

		$this->assertFileDoesNotExist( $Logger->getFilename() );
	}

	/**
	 * Log message to file.
	 */
	public function testCanFileWrite()
	{
		if ( !class_exists( '\\Monolog\\Logger' ) ) {
			$this->markTestSkipped( 'The Monolog class is not available.' );
		}

		$logFile = sprintf( '%s/%s.log', self::DIR_TEMP, __FUNCTION__ );

		$Factory = new Factory( [ 'log_file' => $logFile ] );
		$Logger = $Factory->Logger();
		$Logger->warning( $message = __FUNCTION__ );

		$filename = $Logger->getFilename();
		$this->assertFileExists( $filename );
		$this->assertStringContainsString( $message, file_get_contents( $filename ) );
	}
}
