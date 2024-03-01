<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests\App2;

/**
 * Fixture: Orkan\Factory2.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Factory extends \Orkan\Factory
{
	public function get2( string $key = '' )
	{
		return $this->get( $key );
	}
}
