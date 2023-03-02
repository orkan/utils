<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020-2023 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan\Tests;

use Orkan\Input;

/**
 * Test Input.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class InputTest extends \PHPUnit\Framework\TestCase
{

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers Helpers
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * @see Input::inputsAll()
	 *
	 * @formatter:off */
	const FIELDS = [
		'type'   => 'group',
		'name'   => 'group_0_0',
		'value'  => '0_0',
		'items'  => [
			'edit_1_1' => [
				'type'   => 'text',
				'value'  => '1_1',
			],
			'group_1_2' => [
				'type'   => 'group',
				'value'  => '1_2',
				'items'  => [
					'edit_2_1' => [
						'type'   => 'text',
						'value'  => '2_1',
					],
					'edit_2_2' => [
						'type'   => 'text',
						'value'  => '2_2',
					],
					'group_2_3' => [
						'type'   => 'group',
						'value'  => '2_3',
						'items'  => [
							'edit_3_1' => [
								'type'   => 'text',
								'value'  => '3_1',
							],
							'edit_3_2' => [
								'type'   => 'text',
								'value'  => '3_2',
							],
						],
					],
				],
			],
			'edit_1_3' => [
				'type'   => 'text',
				'value'  => '1_3',
			],
		],
	];
	/* @formatter:on */

	/**
	 * @see Input::filterText()
	 * @see Input::filterTextarea()
	 *
	 * \PC  : visible characters
	 * \PCc : control characters
	 * \PCn : unassigned characters
	 * \PCs : UTF-8-invalid characters
	 * \PCf : formatting characters
	 *
	 * @link https://en.wikipedia.org/wiki/List_of_Unicode_characters
	 * @link https://www.regular-expressions.info/unicode.html#category
	 */
	public function valueProvider()
	{
		/* @formatter:off */
		return [
			// alpha
			'text:     [abc]' => [ 'text'    , 'abc', 'abc' ],
			'textarea: [abc]' => [ 'textarea', 'abc', 'abc' ],
			// non-alpha
			'text:     [<a>-b|c&nbsp;]' => [ 'text'    , '<a>-b|c&nbsp;]', '<a>-b|c&nbsp;]' ],
			'textarea: [<a>-b|c&nbsp;]' => [ 'textarea', '<a>-b|c&nbsp;]', '<a>-b|c&nbsp;]' ],
			// numbers
			'text:     [123]' => [ 'text'    , '123', '123' ],
			'textarea: [123]' => [ 'textarea', '123', '123' ],
			// alpha+num
			'text:     [a1b2c3]' => [ 'text'    , 'a1b2c3', 'a1b2c3' ],
			'textarea: [a1b2c3]' => [ 'textarea', 'a1b2c3', 'a1b2c3' ],
			// trim
			'text:     [ abc ]' => [ 'text'    , ' a1c ', 'a1c' ],
			'textarea: [ abc ]' => [ 'textarea', ' a1c ', 'a1c' ],
			'text:     [\x0A\x0D]'  => [ 'text'    , "\x0A\x0D", '' ],
			'textarea: [\x0A\x0D]'  => [ 'textarea', "\x0A\x0D", '' ],
			// html
			'text:     [<a>bc]' => [ 'text'    , '<a>bc', '<a>bc' ],
			'textarea: [<a>bc]' => [ 'textarea', '<a>bc', '<a>bc' ],
			// new line
			'text:     [a\nbc]' => [ 'text'    , "a\nbc", 'a bc'   ],
			'textarea: [a\nbc]' => [ 'textarea', "a\nbc", "a\nbc" ],
			// tab
			'text:     [a\tbc]' => [ 'text'    , "a\tbc", 'a bc'  ],
			'textarea: [a\tbc]' => [ 'textarea', "a\tbc", "a\tbc" ],
			// double spaces
			'text:     [a  b]' => [ 'text'    , "a  b", 'a b'  ],
			'textarea: [a  b]' => [ 'textarea', "a  b", 'a  b' ],
			'text:     [a\r\nb]' => [ 'text'    , "a\r\nb", 'a b'    ],
			'textarea: [a\r\nb]' => [ 'textarea', "a\r\nb", "a\r\nb" ],
			/*
			 * Latin-1 Supplement
			 * \u{00D8} - Ã?
			 */
			'text:     [a\u{00D8}b c]' => [ 'text'    , "a\u{00D8}b c", "a\u{00D8}b c" ],
			'textarea: [a\u{00D8}b c]' => [ 'textarea', "a\u{00D8}b c", "a\u{00D8}b c" ],
			'text:     [ a\u{00D8}b\t\t\n c]' => [ 'text'    , " a\u{00D8}b\t\t\n c", "a\u{00D8}b c" ],
			'textarea: [ a\u{00D8}b\t\t\n c]' => [ 'textarea', " a\u{00D8}b\t\t\n c", "a\u{00D8}b\t\t\n c" ],
			/*
			 * Formating characters
			 * \x0A - Line Feed
			 * \x0D - Carriage Return
			 * \x0B - Vertical Tab
			 */
			'text:     [\x0A\x0D]' => [ 'text'    , "[\x0A\x0D]", "[ ]" ],
			'textarea: [\x0A\x0D]' => [ 'textarea', "[\x0A\x0D]", "[\x0A\x0D]" ],
			/*
			 * Control characters
			 * \x00 - Null char
			 * \x10 - Back Space
			 * \u{0019} - Ctrl-Y
			 */
			'text:     [\x00\x10]'    => [ 'text'    , "[\x00\x10]", "[]" ],
			'textarea: [\x00\x10]'    => [ 'textarea', "[\x00\x10]", "[]" ],
			'text:     [a\u{0019}bc]' => [ 'text'    , "a\u{0019}bc", 'abc' ],
			'textarea: [a\u{0019}bc]' => [ 'textarea', "a\u{0019}bc", 'abc' ],
		];
		/* @formatter:on */
	}

	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests: Tests:
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Filter input value.
	 *
	 * @dataProvider valueProvider
	 */
	public function testCanFilterValue( $type, $value, $expect )
	{
		$Input = new Input( [ 'name' => 'testing', 'type' => $type, 'value' => $value ] );
		$actual = $Input->val();

		$this->assertSame( $expect, $actual );
	}

	/**
	 * Recursively find field by name.
	 */
	public function testCanFindFieldByName()
	{
		/* @formatter:off */
		$fields = [
			'name1' => [
				'type'  => 'text',
				'order' => 200,
			],
			'name2' => [
				'type'  => 'group',
				'order' => 100,
				'items' => [
					'name21' => [
						'type'  => 'checkbox',
						'label' => 'Name 21',
					],
					'name22' => [
						'type' => 'checkbox',
						'label' => 'Name 22',
					],
				],
			],
		];
		/* @formatter:on */

		// Field type: text
		$this->assertSame( $fields['name1'], Input::fieldFind( 'name1', $fields ) );

		// Field type: group
		$this->assertSame( $fields['name2'], Input::fieldFind( 'name2', $fields ) );

		// Nested field
		$this->assertSame( $fields['name2']['items']['name22'], Input::fieldFind( 'name22', $fields ) );

		// Not found
		$this->assertSame( [], Input::fieldFind( 'name3', $fields ) );
	}

	/**
	 * Recursively update each field name.
	 */
	public function testCanIterateEachField()
	{
		/* @formatter:off */
		$fieldsActual = [
			'name1' => [
				'type'  => 'text',
				'order' => 200,
			],
			'name2' => [
				'type'  => 'group',
				'order' => 100,
				'items' => [
					'name21' => [
						'type' => 'checkbox',
					],
					'name22' => [
						'type' => 'checkbox',
					],
				],
			],
		];
		$fieldsExpected = [
			'name1' => [
				'type'  => 'text',
				'order' => 200,
				'name'  => 'new[name1]',
			],
			'name2' => [
				'type'  => 'group',
				'order' => 100,
				'items' => [
					'name21' => [
						'type' => 'checkbox',
						'name' => 'new[name21]',
					],
					'name22' => [
						'type' => 'checkbox',
						'name' => 'new[name22]',
					],
				],
			],
		];
		/* @formatter:on */

		foreach ( Input::fieldsEach( $fieldsActual ) as $name => &$field ) {
			$field['name'] = sprintf( 'new[%s]', $name );
		}

		$this->assertSame( $fieldsExpected, $fieldsActual );
	}

	/**
	 * Recursively update each field name.
	 */
	public function testCanFindAllFields()
	{
		/* @formatter:off */
		$fields = [
			'name1' => [ 'type' => 'text' ],
			'name2' => [
				'type'  => 'group',
				'items' => [
					'name21' => [ 'type' => 'checkbox' ],
					'name22' => [ 'type' => 'checkbox' ],
				],
			],
		];
		/* @formatter:on */

		/* @formatter:off */
		$expect = [
			'name1'  => [ 'type' => 'text' ],
			'name2'  => [
				'type'  => 'group',
				'items' => [
					'name21' => [ 'type' => 'checkbox' ],
					'name22' => [ 'type' => 'checkbox' ],
				],
			],
			'name21' => [ 'type' => 'checkbox' ],
			'name22' => [ 'type' => 'checkbox' ],
		];
		/* @formatter:on */
		$actual = Input::fieldsAll( $fields, true );
		$this->assertSame( $expect, $actual, 'Fields with groups' );

		/* @formatter:off */
		$expect = [
			'name1'  => [ 'type' => 'text'     ],
			'name21' => [ 'type' => 'checkbox' ],
			'name22' => [ 'type' => 'checkbox' ],
		];
		/* @formatter:on */
		$actual = Input::fieldsAll( $fields );
		$this->assertSame( $expect, $actual, 'Fields without groups' );
	}

	/**
	 * Find nested Inputs (include Self & groups).
	 */
	public function testCanFindAllInputs()
	{

		/* @formatter:off */
		$expect = [
			0 => 'group_0_0',
			1 => 'edit_1_1',
			2 => 'group_1_2',
			3 => 'edit_2_1',
			4 => 'edit_2_2',
			5 => 'group_2_3',
			6 => 'edit_3_1',
			7 => 'edit_3_2',
			8 => 'edit_1_3',
		];
		/* @formatter:on */

		$Input = new Input( self::FIELDS );
		$inputs = Input::inputsAll( $Input->elements( true ), true );

		$actual = [];
		foreach ( $inputs as $Input ) {
			$actual[] = $Input->name();
		}

		$this->assertSame( $expect, $actual );
	}

	/**
	 * Find nested Inputs (flattened).
	 */
	public function testCanFindAllInputsWithoutGroups()
	{
		/* @formatter:off */
		$expect = [
			0 => 'edit_1_1',
			1 => 'edit_2_1',
			2 => 'edit_2_2',
			3 => 'edit_3_1',
			4 => 'edit_3_2',
			5 => 'edit_1_3',
		];
		/* @formatter:on */

		$Input = new Input( self::FIELDS );
		$inputs = Input::inputsAll( $Input->elements() );

		$actual = [];
		foreach ( $inputs as $Input ) {
			$actual[] = $Input->name();
		}

		$this->assertSame( $expect, $actual );
	}

	/**
	 * Iterate nested Inputs (no Self, include Groups).
	 */
	public function testCanIterateEachInput()
	{
		/* @formatter:off */
		$expect = [
			0 => 'edit_1_1',
			1 => 'group_1_2',
			2 => 'edit_2_1',
			3 => 'edit_2_2',
			4 => 'group_2_3',
			5 => 'edit_3_1',
			6 => 'edit_3_2',
			7 => 'edit_1_3',
		];
		/* @formatter:on */

		$Input = new Input( self::FIELDS );

		$actual = [];
		foreach ( Input::inputsEach( $Input->elements(), true ) as $name => $Input ) {
			$this->assertSame( $name, $Input->name() );
			$actual[] = $name;
		}

		$this->assertSame( $expect, $actual );
	}

	/**
	 * Iterate nested Inputs (flattened).
	 */
	public function testCanIterateEachInputWithoutGroups()
	{
		/* @formatter:off */
		$expect = [
			0 => 'edit_1_1',
			1 => 'edit_2_1',
			2 => 'edit_2_2',
			3 => 'edit_3_1',
			4 => 'edit_3_2',
			5 => 'edit_1_3',
		];
		/* @formatter:on */

		$Input = new Input( self::FIELDS );

		$actual = [];
		foreach ( Input::inputsEach( $Input->elements() ) as $name => $Input ) {
			$this->assertSame( $name, $Input->name() );
			$actual[] = $name;
		}

		$this->assertSame( $expect, $actual );
	}

	/**
	 * Iterate nested Inputs (no Self, include Groups).
	 */
	public function testCanIterateEachElement()
	{
		/* @formatter:off */
		$fields = [
			'field' => [
				'type'   => 'group',
				'name'   => 'group_0_0',
				'items'  => [
					'group_1_1' => [
						'type'  => 'group',
						'items' => [
							'edit_2_1'  => [ /* TEXT */ ],
							'group_2_3' => [
								'type'  => 'group',
								'items' => [
									'edit_3_1' => [ /* TEXT */ ],
								],
							],
						],
					],
					'edit_1_3' => [ /* TEXT */ ],
				],
			],
		];
		$expect = [
			0 => 'group_1_1',
			1 => 'edit_2_1',
			2 => 'group_2_3',
			3 => 'edit_3_1',
			4 => 'edit_1_3',
		];
		/* @formatter:on */

		// Add name, type...
		Input::fieldsPrepare( $fields );

		$Input = new Input( $fields['field'] );

		$actual = [];
		foreach ( $Input->each( true ) as $name => $Input ) {
			$this->assertSame( $name, $Input->name() );
			$actual[] = $name;
		}

		$this->assertSame( $expect, $actual );
	}

	/**
	 * Iterate nested Inputs (flattened).
	 */
	public function testCanIterateEachElementWithoutGroups()
	{
		/* @formatter:off */
		$fields = [
			'field' => [
				'type'   => 'group',
				'name'   => 'group_0_0',
				'items'  => [
					'group_1_1' => [
						'type'  => 'group',
						'items' => [
							'edit_2_1'  => [ /* TEXT */ ],
							'group_2_3' => [
								'type'  => 'group',
								'items' => [
									'edit_3_1' => [ /* TEXT */ ],
								],
							],
						],
					],
					'edit_1_3' => [ /* TEXT */ ],
				],
			],
		];
		$expect = [
			0 => 'edit_2_1',
			1 => 'edit_3_1',
			2 => 'edit_1_3',
		];
		/* @formatter:on */

		// Add name, type...
		Input::fieldsPrepare( $fields );

		$Input = new Input( $fields['field'] );

		$actual = [];
		foreach ( $Input->each() as $name => $Input ) {
			$this->assertSame( $name, $Input->name() );
			$actual[] = $name;
		}

		$this->assertSame( $expect, $actual );
	}

	/**
	 * Iterate self Input.
	 */
	public function testCanIterateSelf()
	{
		/* @formatter:off */
		$fields = [
			'select_1' => [
				'type' => 'select',
			],
		];
		$expect = [
			0 => 'select_1',
		];
		/* @formatter:on */

		// Add name, type...
		Input::fieldsPrepare( $fields );

		$Input = new Input( $fields['select_1'] );

		$actual = [];
		foreach ( $Input->each() as $name => $Input ) {
			$this->assertSame( $name, $Input->name() );
			$actual[] = $name;
		}

		$this->assertSame( $expect, $actual );
	}

	/**
	 * Collect attribute values from all fields.
	 */
	public function testCanPluckAttr()
	{
		/* @formatter:off */
		$fields = [
			'field_1' => [
				'type' => 'group',
				'find' => 'A',
				'items'  => [
					'group_1_1' => [
						'type'  => 'group',
						'items' => [
							'edit_2_1' => [
								'find' => 'B',
							],
							'group_2_2' => [
								'type'  => 'group',
								'items' => [
									'edit_3_1' => [
										'find' => 'C',
									],
									'edit_3_2' => [
										'find' => 'D',
									],
								],
							],
						],
					],
					'edit_1_2' => [
						'find' => [ 'E1', 'E1', 'E2' ], // doubles!
					],
				],
			],
			'field_2' => [
				'find' => [ 'F1', 'F2' ],
			],
		];
		$expect = [
			0 => 'A',
			1 => 'B',
			2 => 'C',
			3 => 'D',
			4 => 'E1',
			// 5 => 'E1', // remove doubles!
			6 => 'E2',
			7 => 'F1',
			8 => 'F2',
		];
		/* @formatter:on */

		// Add name, type...
		Input::fieldsPrepare( $fields );
		$actual = Input::fieldPluck( 'find', $fields );

		$this->assertSame( $expect, $actual );
	}
}
