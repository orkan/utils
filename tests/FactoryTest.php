<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Factory;

/**
 * Test Factory.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class FactoryTest extends TestCase
{

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Merge defaults.
	 */
	public function testCanMergeConfig()
	{
		/* @formatter:off */
		$cfg = [
			'factory_test_cfg_1' => 'Cfg 1', // initial value
			'factory_test_cfg_2' => 'Cfg 2',
		];
		$opt = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // do NOT replace if already set
			'factory_test_opt_1' => 'Opt 1',
		];
		$expect = [
			'factory_test_cfg_1' => 'Cfg 1', // not replaced
			'factory_test_cfg_2' => 'Cfg 2',
			'factory_test_opt_1' => 'Opt 1',
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg ); // merge Factory self config with $cfg
		$Factory->merge( $opt ); // merge $opt

		foreach ( $expect as $name => $value ) {
			$this->assertSame( $value, $Factory->get( $name ) );
		}
	}

	/**
	 * Replace defaults.
	 */
	public function testCanMergeConfigForce()
	{
		/* @formatter:off */
		$cfg = [
			'factory_test_cfg_1' => 'Cfg 1', // initial value
			'factory_test_cfg_2' => 'Cfg 2',
		];
		$opt = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // replace if force == true!
			'factory_test_opt_1' => 'Opt 1',
		];
		$expect = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // replaced!
			'factory_test_cfg_2' => 'Cfg 2',
			'factory_test_opt_1' => 'Opt 1',
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg ); // merge Factory self config with $cfg
		$Factory->merge( $opt, true ); // replace with $opt

		foreach ( $expect as $name => $value ) {
			$this->assertSame( $value, $Factory->get( $name ) );
		}
	}

	/**
	 * Merge defaults (multi-dimensional).
	 */
	public function testCanMergeMultiConfig()
	{
		/* @formatter:off */
		$cfg = [
			'factory_test_cfg_1' => 'Cfg 1', // initial value
			'factory_test_cfg_2' => [
				'cfg_2_1' => 'Cfg 2.1',
				'cfg_2_2' => 'Cfg 2.2', // initial value
			],
		];
		$opt = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // do NOT replace if already set
			'factory_test_cfg_2' => [
				'cfg_2_2' => 'Cfg 2.2 (opt)', // do NOT replace if already set
				'cfg_3_1' => 'Cfg 3.1',
			],
		];
		$expect = [
			'factory_test_cfg_1' => 'Cfg 1', // not replaced
			'factory_test_cfg_2' => [
				'cfg_2_2' => 'Cfg 2.2', // not replaced
				'cfg_3_1' => 'Cfg 3.1',
				'cfg_2_1' => 'Cfg 2.1',
			],
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg ); // merge Factory self config with $cfg
		$Factory->merge( $opt ); // merge $opt

		foreach ( $expect as $name => $value ) {
			$this->assertSame( $value, $Factory->get( $name ) );
		}
	}

	/**
	 * Replace defaults (multi-dimensional).
	 */
	public function testCanMergeMultiConfigForce()
	{
		/* @formatter:off */
		$cfg = [
			'factory_test_cfg_1' => 'Cfg 1', // initial value
			'factory_test_cfg_2' => [
				'cfg_2_1' => 'Cfg 2.1',
				'cfg_2_2' => 'Cfg 2.2', // initial value
			],
		];
		$opt = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // replaced
			'factory_test_cfg_2' => [
				'cfg_2_2' => 'Cfg 2.2 (opt)', // replaced
				'cfg_3_1' => 'Cfg 3.1',
			],
		];
		$expect = [
			'factory_test_cfg_1' => 'Cfg 1 (opt)', // replaced
			'factory_test_cfg_2' => [
				'cfg_2_1' => 'Cfg 2.1',
				'cfg_2_2' => 'Cfg 2.2 (opt)', // replaced
				'cfg_3_1' => 'Cfg 3.1',
			],
		];
		/* @formatter:on */

		$Factory = new Factory( $cfg ); // merge Factory self config with $cfg
		$Factory->merge( $opt, true ); // replace with $opt

		foreach ( $expect as $name => $value ) {
			$this->assertSame( $value, $Factory->get( $name ) );
		}
	}
}
