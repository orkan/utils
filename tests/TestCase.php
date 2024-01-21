<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use PHPUnit\Framework\MockObject\MockObject;

/**
 * Shared TestCase class.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
	const DIR_TEMP = __DIR__ . '/_tmp';

	/**
	 * @var Factory
	 */
	protected $Factory;

	/**
	 * NOTE:
	 * If defaults() is defined in derived class, you must merge this method manually.
	 */
	public function defaults(): array
	{
		/* @formatter:off */
		return [
		];
		/* @formatter:on */
	}

	/**
	 * {@inheritDoc}
	 * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
	 */
	public static function setUpBeforeClass(): void
	{
		!defined( 'DEBUG' ) && define( 'DEBUG', true );
		!defined( 'TESTING' ) && define( 'TESTING', true );
	}

	/**
	 * {@inheritDoc}
	 * @see \PHPUnit\Framework\TestCase::setUp()
	 */
	protected function setUp(): void
	{
		\Orkan\Utils::dirClear( self::DIR_TEMP );
		$this->Factory = new Factory( $this->defaults(), $this );
	}

	/**
	 * Made public for FactoryMock.
	 *
	 * {@inheritDoc}
	 * @see \PHPUnit\Framework\TestCase::createMock()
	 */
	public function createMock( string $originalClassName ): MockObject
	{
		return parent::createMock( $originalClassName );
	}
}
