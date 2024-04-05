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
 * NOTICE:
 * Tets this class in both environments: with and without Monolog installed!
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class LoggerTest extends \Orkan\Tests\TestCase
{
	const USE_SANDBOX = true;

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Verbose log message.
	 */
	private function helpTestCanVerbose()
	{
		$msg = __METHOD__;
		$this->expectExceptionMessage( $msg );

		/* @formatter:off */
		$Factory = new Factory([
			'log_verbose' => 'INFO',
		]);
		/* @formatter:on */

		$Factory->Logger()->info( $msg );
	}

	/**
	 * cfg[log_verbose]: Monolog installed.
	 */
	public function testCanVerbose()
	{
		$this->needMonolog();
		$this->helpTestCanVerbose();
	}

	/**
	 * cfg[log_verbose]: Monolog NOT installed.
	 */
	public function testCanVerboseWithoutMonolog()
	{
		$this->needMonolog( false );
		$this->helpTestCanVerbose();
	}

	/**
	 * is(): check if current cfg[log_level] is handled.
	 */
	public function testCanHandleLogLevel()
	{
		$this->needMonolog();

		/* @formatter:off */
		$Logger = new Logger( new Factory([
			'log_level' => 'WARNING',
		]));
		/* @formatter:on */

		// Yes
		$this->assertTrue( $Logger->is( 'ERROR' ) );
		$this->assertTrue( $Logger->is( Logger::ERROR ) );
		$this->assertTrue( $Logger->is( 'WARNING' ) );
		$this->assertTrue( $Logger->is( Logger::WARNING ) );
		// No
		$this->assertFalse( $Logger->is( 'NOTICE' ) );
		$this->assertFalse( $Logger->is( Logger::NOTICE ) );
		$this->assertFalse( $Logger->is( 'INFO' ) );
		$this->assertFalse( $Logger->is( Logger::INFO ) );
		$this->assertFalse( $Logger->is( 'DEBUG' ) );
		$this->assertFalse( $Logger->is( Logger::DEBUG ) );
	}

	/**
	 * is(): check if cfg[log_history] is handled.
	 */
	public function testCanSaveHistory()
	{
		$this->needMonolog();

		/* @formatter:off */
		$Logger = new Logger( new Factory( $cfg = [
			'log_history' => Logger::NOTICE,
		]));
		/* @formatter:on */

		$msg = __METHOD__;

		// No
		$Logger->debug( $msg . Logger::DEBUG );
		$Logger->info( $msg . Logger::INFO );
		// Yes
		$Logger->notice( $msg . Logger::NOTICE );
		$Logger->warning( $msg . Logger::WARNING );
		$Logger->error( $msg . Logger::ERROR );

		$this->assertCount( 3, $history = $Logger->getHistory() );

		foreach ( $history as $v ) {
			$this->assertTrue( $v['level'] >= $cfg['log_history'] );
			$this->assertTrue( $v['message'] === $msg . $v['level'] );
		}
	}

	/**
	 * Log message to file.
	 */
	public function testCanLogToFile()
	{
		$this->needMonolog();

		/* @formatter:off */
		$cfg = [
			'log_file' => self::sandboxPath( '%s.log', __FUNCTION__ ),
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg );
		$Logger = new Logger( $Factory );

		$msg = __METHOD__;
		$Logger->warning( $msg );

		$file = $Logger->getFilename();
		$this->assertStringContainsString( $msg, file_get_contents( $file ) );
	}

	/**
	 * Skip log message to file.
	 */
	public function testCanLogToFileSkip()
	{
		$this->needMonolog();

		/* @formatter:off */
		$Logger = new Logger( new Factory([
			'log_file' => '',
		]));
		/* @formatter:on */

		$Logger->info( __METHOD__ );
		$this->assertFileDoesNotExist( $Logger->getFilename() );
	}
}
