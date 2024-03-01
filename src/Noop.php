<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Noop class.
 *
 * This class can Mock (overwrite) any other class and it's method calls without raising PHP errors.
 * Especially useful in unit testing when a mocking class is declared final or have static methods.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Noop
{

	public function __call( $name, $arguments )
	{
		return null;
	}

	public static function __callStatic( $name, $arguments )
	{
		return null;
	}
}
