<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests\App3;

/**
 * Fixture: Orkan\Application 3.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Application extends \Orkan\Tests\App2\Application
{
	const APP_NAME = 'CLI App3';
	const APP_VERSION = '4.1.31';
	const APP_DATE = '2024-01-31';

	/**
	 * Create derived App.
	 */
	public function __construct( \Orkan\Tests\App2\Factory $Factory )
	{
		$this->Factory = $Factory->merge( self::defaults() );
		parent::__construct( $Factory );
	}

	/**
	 * {@inheritdoc}
	 */
	public function defaults()
	{
		/* @formatter:off */
		return [
			'cli_title'     => 'CLI Application 3',
			'extensions'    => [
				'app3_ext1' => false,
			],
		];
		/* @formatter:on */
	}
}
