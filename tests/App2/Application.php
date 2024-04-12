<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests\App2;

/**
 * Fixture: Orkan\Application 2.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Application extends \Orkan\Application
{
	const APP_NAME = 'CLI App2';
	const APP_VERSION = '2.12.7';
	const APP_DATE = '2022-12-07';

	/**
	 * Create derived App.
	 */
	public function __construct( Factory $Factory )
	{
		$this->Factory = $Factory->merge( self::defaults() );
		parent::__construct( $Factory );
	}

	/**
	 * {@inheritdoc}
	 */
	private function defaults()
	{
		/* @formatter:off */
		return [
			'app_title'     => 'CLI Application 2',
			'app2_prop'     => 'App 2 custom prop',
			'app_php_ext'    => [
				'app2_ext1' => false,
				'app2_ext2' => false,
			],
		];
		/* @formatter:on */
	}
}
