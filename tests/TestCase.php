<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Utils;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * TestCase: Orkan.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
	/**
	 * Root tests dir location.
	 * Each derived class should declare this constant by its own.
	 * @var string
	 */
	const DIR_SELF = __DIR__;

	/**
	 * Force derived class to explicitly declare sandbox and fixtures usage.
	 * Each child class gets its own (auto-cleared) sandbox dir.
	 */
	const USE_SANDBOX = false;
	const USE_FIXTURE = false;

	/**
	 * Working dirs.
	 */
	protected static $dir = [];

	/**
	 * Is monolog installed?
	 * @var bool
	 */
	protected static $isMonolog;

	/**
	 * {@inheritDoc}
	 */
	public static function setUpBeforeClass(): void
	{
		// Tip: first TestCase defines it!
		!defined( 'DEBUG' ) && define( 'DEBUG', true );
		!defined( 'TESTING' ) && define( 'TESTING', true );

		static::USE_SANDBOX && static::$dir['sandbox'] = static::DIR_SELF . '/_sandbox/' . basename( static::class );
		static::USE_FIXTURE && static::$dir['fixture'] = static::DIR_SELF . '/_fixtures';

		// Prepare current sandbox sub-dir - only once per TestCase
		static::USE_SANDBOX && self::sandboxClear();

		// Check Monolog class
		self::$isMonolog = class_exists( '\\Monolog\\Logger' );
	}

	/**
	 * Skip test despite of Monolog existence.
	 * @param bool $require Skip if true and "NO Monolog" or skip if false and "IS Monolog"
	 */
	public function needMonolog( bool $require = true ): void
	{
		$require && !self::$isMonolog && $this->markTestSkipped( 'Skipped! Please install Monolog to run this test.' );
		!$require && self::$isMonolog && $this->markTestSkipped( 'Skipped! Please uninstall Monolog to run this test.' );
	}

	/**
	 * Made public for FactoryMock.
	 *
	 * {@inheritDoc}
	 */
	public function createMock( string $originalClassName ): MockObject
	{
		return parent::createMock( $originalClassName );
	}

	/**
	 * Prepare current sandbox sub-dir.
	 */
	public static function sandboxClear(): void
	{
		Utils::dirClear( static::$dir['sandbox'] );
	}

	/**
	 * Build path to artifact.
	 */
	public static function dirPath( string $key, string $path = '', ...$args ): string
	{
		return static::$dir[$key] . '/' . sprintf( $path, ...$args );
	}

	/**
	 * Build path to sandbox artifact.
	 * NOTE: Each TestCase uses its own sub-dir.
	 */
	public static function sandboxPath( string $path, ...$args ): string
	{
		if ( !static::USE_SANDBOX ) {
			throw new \Exception( 'Sandbox disabled! Use const USE_SANDBOX = true; in ' . static::class );
		}

		return self::dirPath( 'sandbox', $path, ...$args );
	}

	/**
	 * Build path to fixture artifact.
	 */
	public static function fixturePath( string $path, ...$args ): string
	{
		if ( !static::USE_FIXTURE ) {
			throw new \Exception( 'Fixtures disabled! Use const USE_FIXTURE = true; in ' . static::class );
		}

		return self::dirPath( 'fixture', $path, ...$args );
	}

	/**
	 * Get contents from file.
	 */
	public function fixtureData( string $path, ...$args )
	{
		$path = self::fixturePath( $path, ...$args );
		return file_get_contents( $path );
	}

	/**
	 * Convert JSON fixture to PHP variable.
	 */
	public function fixtureJson( string $path, ...$args )
	{
		$path = self::fixturePath( $path, ...$args );
		$data = json_decode( file_get_contents( $path ), true );

		if ( null === $data ) {
			/* @formatter:off */
			throw new \Exception( var_export([
				'path'  => $path,
				'data'  => $data,
				'error' => Utils::errorJson(),
			], true ));
			/* @formatter:on */
		}

		return $data;
	}
}
