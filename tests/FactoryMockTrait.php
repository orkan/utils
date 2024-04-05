<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Logger;
use Orkan\ProgressBar;
use Orkan\Prompt;
use PHPUnit\Framework\TestCase;
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
	 * public $Logger; // CAUTION: Already defined in Factory. Can be overriten in FactoryMock class only - not Triat!
	 */

	/**
	 * Configure Factory mock.
	 */
	public function __construct( TestCase $TestCase, array $cfg = [] )
	{
		parent::__construct( $cfg );
		$this->TestCase = $TestCase;

		// No user prompt
		$this->Utils()->setup( [ 'silent' => true ] );
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

	/**
	 * @return MockObject
	 */
	public function ProgressBar( int $steps = 10, string $format = '' )
	{
		return $this->ProgressBar ?? $this->ProgressBar = $this->TestCase->createMock( ProgressBar::class );
	}

	/**
	 * @return MockObject
	 */
	public function Prompt()
	{
		return $this->Prompt ?? $this->Prompt = $this->TestCase->createMock( Prompt::class );
	}
}
