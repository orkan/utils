<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests\App2;

/**
 * Test derived Factory.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Factory extends \Orkan\Factory
{
	public function get2( string $key = '' )
	{
		return $this->cfg( $key );
	}
}
