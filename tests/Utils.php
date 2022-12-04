<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2022 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use ReflectionClass;

/**
 * Test case helper functions.
 *
 * @author Orkan <orkans@gmail.com>
 */
class Utils
{

	/**
	 * Set Object's private property
	 *
	 * @param object $obj
	 * @param string $property Property name
	 * @param mixed $value New value
	 */
	public static function setPrivateProperty( object $obj, string $property, $value ): void
	{
		$class = new ReflectionClass( $obj );
		$item = $class->getProperty( $property );
		$item->setAccessible( true );
		$item->setValue( $obj, $value );
	}

	/**
	 * Get Object's private property
	 *
	 * @param object $obj
	 * @param string $property Property name
	 * @return mixed
	 */
	public static function getPrivateProperty( object $obj, string $property )
	{
		$class = new ReflectionClass( $obj );
		$item = $class->getProperty( $property );
		$item->setAccessible( true );
		return $item->getValue( $obj );
	}

	/**
	 * Call Object's private method
	 *
	 * @param object | string $obj For static method use string then invokeArgs(null, ...)
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public static function callPrivateMethod( $obj, string $method, $args = [] )
	{
		$class = new ReflectionClass( $obj );
		$name = $class->getMethod( $method );
		$name->setAccessible( true ); // Set method to public
		return $name->invokeArgs( is_string( $obj ) ? null : $obj, $args ); // Call $this->name( $args );
	}
}
