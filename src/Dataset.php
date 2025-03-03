<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2025 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Get/Set data model.
 *
 * Simple data model with getters/setters and read-only fields.
 *
 * CAUTION:
 * To check whether data "is set" use: Dataset->val === null, since isset(Dataset->val) will always return false
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Dataset
{
	/**
	 * Instance name.
	 */
	protected $name;

	/**
	 * Required fields.
	 */
	protected $init = [];

	/**
	 * Read-only fields.
	 *
	 * @var array List of read-only fields fields or globally disable setter.
	 */
	protected $read = [];

	/**
	 * Make all fields read-only. New fields possible.
	 *
	 * @var bool
	 */
	protected $disabled = false;

	/**
	 * Make all fields read-only. New fields NOT possible by setter.
	 * Possible direct data[] access, eg. via rebuild()
	 *
	 * @var bool
	 */
	protected $closed = false;

	/**
	 * Count rebuilds.
	 */
	protected $rebuilds = 0;

	/**
	 * Custom fields.
	 */
	protected $data;

	/**
	 * Data status.
	 */
	protected $dirty = false;

	/**
	 * Initialize data.
	 */
	public function __construct( array $data = [] )
	{
		foreach ( $this->init as $key ) {
			if ( !isset( $data[$key] ) ) {
				throw new \InvalidArgumentException( "Missing required data[$key]" );
			}
			elseif ( '' === $data[$key] ) {
				throw new \TypeError( "Empty required data[$key]" );
			}
		}

		$this->data = $data;
		$this->name = $this->data['name'] ?? basename( static::class );
	}

	/**
	 * Dataset to string.
	 */
	public function __toString(): string
	{
		return $this->name;
	}

	/**
	 * Setter.
	 */
	public function __set( string $key, $value ): void
	{
		// -------------------------------------------------------------------------------------------------------------
		// Read-only: block updating entire data[]
		if ( $this->closed ) {
			throw new \RuntimeException( "Unable to update [$key] in closed dataset [{$this}]" );
		}

		// -------------------------------------------------------------------------------------------------------------
		// Read-only: block updating already defined data[]
		if ( isset( $this->data[$key] ) ) {
			if ( $this->disabled ) {
				throw new \RuntimeException( "Unable to update [$key] in disabled dataset [{$this}]" );
			}
			if ( array_search( $key, $this->read ) !== false ) {
				throw new \RuntimeException( "Unable to update read-only [$key] in dataset [{$this}]" );
			}
		}

		// -------------------------------------------------------------------------------------------------------------
		// Set
		$last = $this->data[$key] ?? null;

		if ( $last !== $value ) {
			$this->data[$key] = $value;
			$this->dirty = true;
		}

		// -------------------------------------------------------------------------------------------------------------
		// User callback?
		if ( method_exists( $this, $method = 'set' . ucfirst( $key ) ) ) {
			$this->$method( $value, $last );
		}
	}

	/**
	 * Getter.
	 */
	public function __get( string $key )
	{
		$this->dirty && $this->rebuild();

		if ( method_exists( $this, $method = 'get' . ucfirst( $key ) ) ) {
			return $this->$method();
		}

		return $this->data[$key] ?? null;
	}

	/**
	 * Get/set data status.
	 */
	public function dirty( ?bool $dirty = null ): bool
	{
		return $this->dirty = $dirty ?? $this->dirty;
	}

	/**
	 * One-time switch to disable all defined data[] at runtime.
	 */
	public function disable(): void
	{
		$this->disabled = true;
	}

	/**
	 * One-time switch to disable setter at runtime.
	 */
	public function close(): void
	{
		$this->closed = true;
	}

	/**
	 * Get rebuilds count.
	 */
	public function rebuilds(): int
	{
		return $this->rebuilds;
	}

	/**
	 * Get all data.
	 */
	public function data(): array
	{
		$this->dirty && $this->rebuild();
		return $this->data;
	}

	/**
	 * Rebuild data and clean status.
	 *
	 * IMPORTANT:
	 * Use data[name] to access data instead of getter/setter - to prevent rebuild loop!
	 */
	protected function rebuild(): void
	{
		$this->rebuilds++;
		$this->dirty = false;
	}
}
