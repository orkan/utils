<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Logger;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Mock trait: Orkan\Factory.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
trait FactoryMockTrait
{
	/**
	 * Mocks context.
	 * @var TestCase
	 */
	protected $TestCase;

	/*
	 * Services:
	 * CAUTION: Already defined in Factory. Can be overriten in FactoryMock class only - not Triat!
	 public $Logger;
	 */

	/**
	 * Configure Factory mock.
	 */
	public function __construct( TestCase $TestCase, array $cfg = [] )
	{
		parent::__construct( $cfg );
		$this->TestCase = $TestCase;
	}

	// =================================================================================================================
	// SERVICES - MOCK
	// =================================================================================================================

	/**
	 * @return MockObject
	 */
	public function Logger()
	{
		if ( !$this->Logger ) {
			$this->Logger = $this->TestCase->createMock( Logger::class );

			// Excecute all Logger::DEBUG protected lines!
			$this->Logger->method( 'is' )->willReturn( true );
		}

		return $this->Logger;
	}
}
