<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2023 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * App Factory.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Factory
{
	use Config;

	/*
	 * Services:
	 */
	protected $Utils;
	protected $Logger;

	/**
	 * Configure Factory.
	 */
	public function __construct( array $cfg = [] )
	{
		$this->cfg = $cfg;
	}

	/**
	 * @return Utils
	 */
	public function Utils()
	{
		return $this->Utils ?? $this->Utils = new Utils();
	}

	/**
	 * @return Logger
	 */
	public function Logger()
	{
		return $this->Logger ?? $this->Logger = new Logger( $this );
	}
}
