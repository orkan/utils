<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2023 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

/**
 * App Factory.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Factory extends \Orkan\Factory
{
	/*
	 * Mocks context.
	 */
	private $TestCase;

	/**
	 * Configure new Factory (tests mocks)
	 */
	public function __construct( array $cfg = [], TestCase $TestCase )
	{
		parent::__construct( $cfg );
		$this->TestCase = $TestCase;
	}

	/*
	 * =================================================================================================================
	 * MOCKS
	 * =================================================================================================================
	 */

	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	public function Logger()
	{
		if ( !$this->Logger ) {
			$this->Logger = $this->TestCase->createMock( \Orkan\Logger::class );

			// Excecute all Logger::DEBUG protected lines!
			$this->Logger->method( 'is' )->willReturn( true );
		}

		return $this->Logger;
	}
}
