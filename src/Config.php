<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2023 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * App Config.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
trait Config
{
	protected $cfg;

	/**
	 * Set/Get config value.
	 */
	public function cfg( string $key = '', $val = null )
	{
		$last = $this->cfg[$key] ?? null;

		if ( isset( $val ) ) {
			$this->cfg[$key] = $val;
		}

		if ( '' === $key ) {
			return $this->cfg;
		}

		return $last;
	}

	/**
	 * Get config value or return default.
	 */
	public function get( string $key = '', $default = '' )
	{
		return $this->cfg( $key ) ?? $default;
	}

	/**
	 * Merge config values.
	 *
	 * ---------------------------------------
	 * Multi-dimensional config array example:
	 * $this->cfg = Array ( 'moduleA' => [ 'opt1' => 1, 'opt2' => 2 ], 'moduleB' => [ 'opt1' => 1 ] )
	 * $defaults  = Array ( 'moduleA' => [ 'opt3' => 3 ] )
	 * $result    = Array ( 'moduleA' => [ 'opt1' => 1, 'opt2' => 2, 'opt3' => 3 ], 'moduleB' => [ 'opt1' => 1 ] )
	 *
	 * Note: To clear [moduleA] use [''] instead of [], ie. merge( [ 'moduleA' => [''] ] )
	 * ---------------------------------------
	 *
	 * @param array   $defaults Low priority config - will NOT replace $this->cfg
	 * @param boolean $force    Hight priority config - will replace $this->cfg
	 * @return self
	 */
	public function merge( array $defaults, bool $force = false ): self
	{
		$this->cfg = $force ? array_replace_recursive( $this->cfg, $defaults ) : array_replace_recursive( $defaults, $this->cfg );
		return $this;
	}
}
