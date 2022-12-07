<?php
/*
 * This file is part of the orkan/tlc package.
 * Copyright (c) 2022-2022 Orkan <orkans+tlc@gmail.com>
 */
namespace Orkan;

/**
 * Services definition.
 *
 * @author Orkan <orkans+tlc@gmail.com>
 */
interface FactoryInterface
{

	public function cfg( string $key = '', $val = null );

	public function get( string $key = '', $default = '' );

	public function merge( array $defaults, bool $force = false ): self;

	public function Utils();

	public function Logger();
}
