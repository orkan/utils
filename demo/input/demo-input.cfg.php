<?php

use Orkan\Input;
use Orkan\Inputs;

return [
	'text' => [
		'type'   => 'text',
		'label'  => 'Label:',
		'tag'    => 'Tag',
		'desc'   => Input::escHtml('Default: "Some <html> text". Filter: raw. Order: 200'),
		'hint'   => 'Type somenthing',
		'defval' => 'Some <html> text',
		'filter' => 'raw',
		'order'  => 200,
		// 'value'  => 'Value from DB',
	],
	'textVal' => [
		'type'   => 'text',
		'desc'   => 'Hardcoded field value: "My value". Order: 100',
		'defval' => 'My defval',
		'value'  => 'My value',
		'order'  => 100,
	],
	'textDefVals' => [
		'type'   => 'text',
		'desc'   => 'Default value with escaping chars: Single\'s <quote> "text".',
		'defval' => [ 'Single\'s <quote> "text".', 'Alternate text' ],
		//'value'  => '',
		'order'  => 101,
	],
	'textDisabled' => [
		'type'     => 'text',
		'order'    => 211,
		'value'    => 'Disabled input',
		'disabled' => true,
	],
	'textReadonly' => [
		'type'     => 'text',
		'order'    => 212,
		'value'    => 'Readonly input',
		'readonly' => true,
	],
	'textFilterAnonymouse' => [
		'type'   => 'text',
		'desc'   => Input::escHtml('Filter: anonymouse'),
		'defval' => 'Text',
		'filter' => [
			'callback' => function( $arg1, $arg2, $arg3 ) {
				return "\$arg1: $arg1, \$arg2: $arg2, \$arg3: $arg3";
			},
			'args' => [
				'Arg 1', // 1:
				'value' => 'Arg 2', // 2: 'value' is passed as 2nd arg!
				'Arg 3', // 3:
			]
		],
	],
	'textarea' => [
		'type'   => 'textarea',
		'hint'   => 'Type some text...',
		'desc'   => Input::escHtml('Default: "Some <html> text" (hidden). Filter: ') . '<a href="#link">Input::filterText()</a>',
		'defval' => 'Some <html> text',
		'nodef'  => true,
		'filter' => [ Input::class, 'filterText' ],
		'nodef'  => true,
	],
	'numberDefVals' => [
		'type'   => 'number',
		'desc'   => 'From 10 to 50 with step: 10',
		'defval' => [ '20', '40', '55', '60' ],
		'min'    => '10',
		'max'    => '50',
		'step'   => '10',
	],
	'radios' => [
		'type'   => 'radio',
		'desc'   => Input::escHtml('Radios with default: C. Also use special chars in Labels!'),
		'defval' => 'radC',
		'order'  => 710,
		'items'  => [
			'radA' => 'Tag & A',
			'radB' => 'Tag <B>',
			'radC' => 'Tag "C"',
		],
	],
	'radiosNodef' => [
		'type'   => 'radio',
		'split'  => ', ',
		'label'  => 'Label: ',
		'order'  => 720,
		'items'  => [
			'radA' => 'Tag A',
			'radB' => 'Tag B',
			'radC' => 'Tag C',
		],
	],
	'radiosExtraDefVals' => [
		'type'   => 'radio',
		'desc'   => 'Items config (1,2) as array, (3) as string',
		'order'  => 730,
		'defval' => [ 'rad-3', 'rad-2' ],
		'items'  => [
			'rad-1' => [
				'tag'   => '(array) Radio 1',
				'class' => 'radios-extra rad-1-class',
				'style' => 'background: yellow;',
				'extra' => 'onClick=\'(function(el){alert("You clicked: " + jQuery(el).parent().text())})(this)\'',
			],
			'rad-2' => [
				'tag'   => '(array) Radio 2',
				'style' => 'background: yellow;',
				'extra' => 'onClick=\'alert("You clicked: " + jQuery(this).parent().text())\'',
			],
			'rad-3' => '(string) Radio 3',
		],
	],
	'checkboxOn' => [
		'type'   => 'checkbox',
		'label'  => 'Label: ',
		'tag'    => 'Tag',
		'defval' => 'on',
		// 'value'  => '',
	],
	'checkboxOff' => [
		'type'   => 'checkbox',
		'tag'    => 'Tag A',
		'defval' => 'off',
	],
	'checkboxNameSpacedSingle' => [
		'type'   => 'checkbox',
		'name'   => 'checkboxNameSpacedSingle[cbox1]',
		'tag'    => 'My name is: checkboxNameSpacedSingle[cbox1]',
		'defval' => 'on',
	],
	'checkboxNameSpacedGroup' => [
		'type'   => 'group',
		'desc'   => 'Use namespaced input[name]. Hidden name: input[name_hidden]. Default: cbox1:[none], cbox2:[ON]',
		'items'  => [
			'checkboxNameSpacedGroup[cbox1]' => [
				'type'   => 'checkbox',
				'name'   => 'checkboxNameSpacedGroup[cbox1]',
				'tag'    => 'Name: checkboxNameSpacedGroup[cbox1]',
			],
			'checkboxNameSpacedGroup[cbox2]' => [
				'type'   => 'checkbox',
				'name'   => 'checkboxNameSpacedGroup[cbox2]',
				'tag'    => 'Name: checkboxNameSpacedGroup[cbox2]',
				'defval' => 'on',
			],
		],
	],
	'textNameSpacedGroup' => [
		'type'   => 'group',
		'items'  => [
			'textNameSpacedGroup_1' => [
				'type'   => 'text',
				'name'   => 'textNameSpacedGroup[text1]',
				'desc'   => 'Name: textNameSpacedGroup[text1]',
			],
			'textNameSpacedGroup_2' => [
				'type'   => 'text',
				'name'   => 'textNameSpacedGroup[text2]',
				'desc'   => 'Name: textNameSpacedGroup[text2]',
			],
		],
	],
	'checkboxSwitch' => [
		'type'   => 'switch',
		'tag'    => 'Tag A',
		'defval' => 'off',
		'order'  => 610,
	],
	'checkboxSwitchGroup' => [
		'type'   => 'group',
		'desc'   => 'Groupped switches: on, off, on.',
		'inline' => false,
		'order'  => 620,
		'items'  => [
			'checkboxSwitchGroup_1' => [
				'type'   => 'switch',
				'tag'    => 'Tag A (on)',
				'defval' => 'on',
			],
			'checkboxSwitchGroup_2' => [
				'type'   => 'switch',
				'tag'    => 'Tag B (off)',
				'defval' => 'off',
			],
			'checkboxSwitchGroup_3' => [
				'type'   => 'switch',
				'tag'    => 'Tag C (on)',
				'defval' => 'on',
			],
		],
	],
	'groupSelectCbox' => [
		'type'   => 'group',
		'desc'   => 'Select with onChange event. Added attributes: class, style, extra.',
		'split'  => " &nbsp; \n",
		'items'  => [
			'groupSelectCbox_select' => [
				'type'   => 'select',
				'label'  => 'Text: ',
				'class'  => 'select group-1 select-2 box-3',
				'style'  => 'background: pink;',
				'extra'  => 'onChange=\'alert("You clicked: " + jQuery( "option:selected", this ).text())\'',
				// 'defval' => 'optC',
				// 'nodef'  => true,
				'items'  => [ '',
					'optA' => 'Opt A',
					'optB' => 'Opt B',
					'optC' => 'Opt C',
				],
			],
			'groupSelectCbox_cbox1' => [
				'type'   => 'checkbox',
				'tag'    => 'Confirm!',
				'defval' => 'off',
				'nodef'  => true,
				'class'  => 'cbox-1',
				'style'  => 'background: aqua;',
			],
			'groupSelectCbox_cbox2' => [
				'type'   => 'checkbox',
				'tag'    => 'You sure?',
				'style'  => 'background: yellow;',
				'extra'  => 'onClick=\'alert("You clicked: " + jQuery(this).parent().text())\'',
				// 'defval' => 'off',
				// 'nodef'  => true,
			],
		],
	],
	'groupGridSelectRadio' => [
		'type'   => 'group',
		'kind'   => 'grid',
		'desc'   => 'Sort by with order.',
		'items'  => [
			'groupSelectRadio_sortby' => [
				'type'   => 'select',
				'items'  => [
					'id'   => 'Image ID',
					'user' => 'User ID',
					'date' => 'Date published',
				],
			],
			'groupSelectRadio_order' => [
				'type'   => 'radio',
				'items'  => [
					'asc'  => 'Asc',
					'desc' => 'Desc',
				],
			],
		],
	],
	'groupGridTextDefVals' => [
		'type'      => 'group',
		'kind'      => 'grid',
		'row_class' => 'row g-3',
		'col_class' => 'col-auto',
		'desc'   => 'Multiple default values. Grid: row g-3, col-auto',
		'items'  => [
			'groupTextDefVals_text' => [
				'type'   => 'text',
				'label'  => 'Text: ',
				'defval' => [ 'A', 'B', 'C' ],
			],
			'groupTextDefVals_cbox' => [
				'type'   => 'number',
				'label'  => 'Number: ',
				'defval' => [ 11, 22, 33 ],
			],
		],
	],
	'groupCboxs' => [
		'type'   => 'group',
		'split'  => ' -|- ',
		'desc'  => 'Defaults: checkbox 1: on, checkbox 2: off. Also use special chars in Labels!',
		'items'  => [
			'groupCboxs_cbox1' => [
				'type'   => 'checkbox',
				'tag'    => '<Tag> & "1"',
				'defval' => 'on',
				'nodef'  => true,
			],
			'groupCboxs_cbox2' => [
				'type'   => 'checkbox',
				'tag'    => '<Tag> & "2"',
				'defval' => 'off',
				'nodef'  => true,
			],
		],
	],
	'groupGroup' => [
		'type'   => 'group',
		'title'  => 'type: group of group',
		'split'  => '{split}',
		'label'  => '{group:0}',
		'desc'  => 'Check <code>[x] Show config</code> to see this field definition',
		'items'  => [
			'g0_edit1' => [
				'type'   => 'text',
				'value'  => '0a',
				'style'  => 'width: 8rem',
			],
			'g1' => [
				'type'   => 'group',
				'label'  => '{group:1}',
				'items'  => [
					'g1_edit1' => [
						'type'   => 'text',
						'value'  => '1a',
						'style'  => 'width: 6rem',
					],
					'g1_edit2' => [
						'type'   => 'text',
						'value'  => '1b',
						'style'  => 'width: 6rem',
					],
					'g2' => [
						'type'   => 'group',
						'label'  => '{group:2}',
						'items'  => [
							'g2_edit1' => [
								'type'   => 'text',
								'value'  => '2a',
								'style'  => 'width: 4rem',
							],
							'g2_edit2' => [
								'type'   => 'text',
								'value'  => '2b',
								'style'  => 'width: 4rem',
							],
						],
					],
				],
			],
			'g0_edit2' => [
				'type'   => 'text',
				'value'  => '0c',
				'style'  => 'width: 6rem',
			],
		],
	],
	'groupTextCboxMb2' => [
		'type'   => 'group',
		'split'  => [ '<div class="mb-2">', '</div>' ],
		'desc'   => 'Text: onKeydown event, filter: strtoupper()',
		'items'  => [
			'groupTextCbox_text' => [
				'type'   => 'text',
				'label'  => 'Label: ',
				'filter' => 'strtoupper',
				'extra'  => 'onKeydown=\'jQuery("input[name=groupTextCbox_cbox]").prop("checked", false)\'',
			],
			'groupTextCbox_cbox' => [
				'type'   => 'checkbox',
				'tag'    => 'Confirm!',
				'defval' => 'off',
				'nodef'  => true,
			],
		],
	],
	'groupToggleCbox' => [
		'type'   => 'group',
		'kind'   => 'toggle',
		'labels' => 'Enable',
		'defval' => 1,
		'desc'   => 'Group toogle. Labels: "Enable". Default: [1]. Text: onKeydown event.',
		'items'  => [
			'groupToggleCbox1' => [
				'type'  => 'group',
				'split' => [ '<div class="mb-2">', '</div>' ],
				'items' => [
					'groupToggleCbox1_text' => [
						'type'   => 'text',
						'extra'  => 'onKeydown=\'jQuery("input[name=groupToggleCbox1_cbox]").prop("checked", false)\'',
					],
					'groupToggleCbox1_cbox' => [
						'type' => 'checkbox',
						'tag'  => 'Tag?',
					],
				],
			],
		],
	],
	'groupToggleRadioParagraphsReversed' => [
		'type'   => 'group',
		'kind'   => 'toggle',
		'labels' => [ 1 => 'Enable', 0 => 'Disable' ],
		'defval' => 0,
		'desc'   => 'Group toogle. Labels: [Enable, Disable]. Default: [0]. Enable first (reversed). Body: no inner group - no split defined.',
		'items'  => [
			'groupToggleRadioRev_text' => [
				'type'   => 'text',
				'label'  => 'Text: ',
				'defval' => [ 'A', 'B', 'C' ],
			],
			'groupToggleRadioRev_cbox' => [
				'type'   => 'number',
				'label'  => 'Number: ',
				'defval' => [ 11, 22, 33 ],
			],
		],
	],
	'groupToggleRadioTable1' => [
		'type'   => 'group',
		'kind'   => 'toggle',
		'labels' => [ 0 => 'Disable <p>', 1 => 'Enable <p>' ],
		'defval' => 1,
		'split'  => [ '<p>', "</p>\n" ],
		'desc'   => 'Group toogle. Labels: [Disable, Enable] Default: [1], Header: split as paragraphs, Body: split as table',
		'items'  => [
			'groupToggleRadioTable1_1' => [
				'type'  => 'group',
				'split' => [ "\n<table class=\"table th-auto\">\n", "<tr><th>{head}</th><td>{body}</td></tr>\n", "</table>\n" ],
				'desc'  => 'Split as table.',
				'items' => [
					'groupToggleRadioTable1_1_text' => [
						'type'   => 'text',
						'title'  => 'Custom title',
						'label'  => 'Text: ',
						'defval' => [ 'A', 'B', 'C' ],
					],
					'groupToggleRadioTable1_1_cbox' => [
						'type'   => 'number',
						'label'  => 'Number: ',
						'defval' => [ 11, 22, 33 ],
					],
				],
			],
		],
	],
	'groupToggleTable' => [
		'type'   => 'group',
		'kind'   => 'toggle',
		'defval' => 'off',
		'desc'   => 'Group toogle. No label. Default: [off]',
		'items'  => [
			'groupToggleTable_1' => [
				'type'    => 'group',
				'kind'    => 'grid',
				'title'   => 'Thumbnail cropping',
				'desc'    => 'No enlarge, no black bars.',
				'section' => 'photo',
				'order'   => 201,
				'split'   => ' &nbsp; ',
				'items'   => [
					'th_crop2' => [
						'type'   => 'number',
						'label'  => 'Position [%]: ',
						'min'    => '0',
						'max'    => '100',
						'defval' => '50',
					],
					'th_ratio2' => [
						'type'   => 'text',
						'label'  => 'Ratio: ',
						'defval' => [ '16:9', '3:4' ],
					],
				],
			],
		],
	],
	'groupToggleTableNested' => [
		'type'   => 'group',
		'kind'   => 'toggle',
		'defval' => 1,
		'class'  => 'stuffbox',
		'style'  => 'border: 1px dashed gray;',
		'desc'   => 'Group toogle. Label: none. Default: [1]. [render] => "Inputs::buildTableNested".',
		'items'  => [
			'groupToggleTableNested_1' => [
				'type'   => 'group',
				'render' => [ Inputs::class, 'buildTableNested' ],
				'desc'   => '[group1:desc]',
				'items'  => [
					'premium' => [
						'type'   => 'checkbox',
						'title'  => 'User capability',
						'tag'    => 'Is Premium',
						'defval' => 'off',
						'order'  => 109,
					],
					'th_crop' => [
						'type'      => 'group',
						'kind'      => 'grid',
						'row_class' => 'row',
						'title'     => 'Thumbnail cropping',
						'desc'      => 'No enlarge, no black bars.',
						'section'   => 'photo',
						'order'     => 210,
						'split'     => '<br>',
						'items'     => [
							'th_crop_pos' => [
								'type'   => 'number',
								'label'  => 'Position [%]: ',
								'min'    => '0',
								'max'    => '100',
								'defval' => '50',
							],
							'th_crop_ratio' => [
								'type'   => 'text',
								'label'  => 'Ratio: ',
								'defval' => '16:9',
							],
							'th_crop_types' => [
								'type'   => 'group',
								'label'  => 'Apply to:',
								'items'  => [
									'break' => [
										'type' => 'html',
										'html' => '<br>',
									],
									'th_crop_types_200' => [
										'type'   => 'checkbox',
										'tag'    => '200',
									],
									'th_crop_types_400' => [
										'type'   => 'checkbox',
										'tag'    => '400',
									],
								],
							],
						],
					],
					'Help [name]' => [
						'type'    => 'html',
						'html'   => 'attr[html] Raw text: <em>Extra [item] included to [group:render_table]</em>',
						//'value'  => 'attr[html] Raw text: <em>Extra [item] included to [group:render_table]</em>',
						//'title'  => 'Help [title]',
						'desc'   => 'Use either: attr[value] for escaped html or attr[html] for raw text.' .
						            '<br>NOTE: [html] has precedance over [value]!',
					],
				],
			],
		],
	],
	'html' => [
		'type'    => 'html',
		'value'   => '<em>Some <span style="font-weight:bold;color:pink;">html</span> in input[value]</em>',
	],
	'toggleCboxTabs' => [
		'type'   => 'group',
		'kind'   => 'checktab',
		'class'  => 'checktab',
		'click'  => '
$box.prop( "checked" ) ?
$tab.show( "highlight", { color: "white" } ):
$tab.hide( "highlight", { color: "white" } );
',
		'desc'   => 'Tabbed checkboxes => [ Tab 1 (off), Tab 2 (on), Tab 3 (off) ]',
		'items'  => [
			'notab1' => [
				'type'    => 'checkbox',
				'tag'     => '[No tab]',
				'defval'  => 'on',
				//'tab'     => 'Tab 1 contents...', // <-- disable tab!
			],
			'tab1' => [
				'type'    => 'checkbox',
				'tag'     => 'Tab 1',
				'defval'  => 'off',
				'tab'     => 'Tab 1 contents...',
			],
			'notab2' => [
				'type'    => 'text', // <-- disable tab!
				'style'   => 'width: 10rem; display: inline-block; margin-right: 1rem;',
				'hint'    => 'tab',
			],
			'tab2' => [
				'type'    => 'checkbox',
				'tag'     => 'Tab 2',
				'defval'  => 'on',
				'tab'     => 'Tab 2 contents...',
			],
			'tab3' => [
				'type'    => 'checkbox',
				'tag'     => 'Tab 3',
				'defval'  => 'off',
				'tab'     => 'Tab 3 contents...',
			],
		],
	],
	'groupToggle2' => [
		'type'   => 'group',
		'split'  => ' &nbsp;',
		'desc'   => 'Group > [ group:toogle1, group:toogle2]. No default.',
		'items'  => [
			'groupToggle2_1' => [
				'type'   => 'group',
				'kind'   => 'toggle',
				'labels' => 'Toggle 1',
				'items'  => [
					'groupToggle2_1_html' => [
						'type'    => 'html',
						'value'   => 'Html 1',
					],
				],
			],
			'groupToggle2_2' => [
				'type'   => 'group',
				'kind'   => 'toggle',
				'labels' => 'Toggle 2',
				'items'  => [
					'groupToggle2_2_html' => [
						'type'    => 'html',
						'value'   => 'Html 2',
					],
				],
			],
		],
	],
	'selectNoDef' => [
		'type'    => 'select',
		'desc'    => 'No default, no none. Also use special chars in Labels!',
		'label'   => 'Label: ',
		'items'   => [
			'optA' => '<Opt> & "A"',
			'optB' => '<Opt> & "B"',
			'optC' => '<Opt> & "C"',
		],
	],
	'selectDefVals' => [
		'type'    => 'select',
		'desc'    => 'Use multiple default values',
		'label'   => 'Choose: ',
		'defval'  => [ 'optB', 'optC' ],
		'items'   => [
			'optA' => '<Opt> & "A"',
			'optB' => '<Opt> & "B"',
			'optC' => '<Opt> & "C"',
		],
	],
	'selectYear' => [
		'type'   => 'year',
		'desc'   => 'From 1920-1960 every 2 years, desc',
		'dir'    => 'desc',
		'min'    => '1920',
		'max'    => '1960',
		'step'   => '2',
		'defval' => '1952',
	],
	'date' => [
		'type'   => 'date',
		'desc'   => 'Min 2017-04-01, default: 2022-04-01',
		'min'    => '2017-04-01',
		// 'max'    => '2022-04-01',
		// 'step'   => '2',
		'defval' => '2022-04-01',
	],
	'groupDisabled' => [
		'type'   => 'group',
		'split'  => [ '<p>', '</p>'],
		'desc'   => Input::escHtml( 'Disabled inputs. No defval! Radio 1 - disable group. Radio 2 - disable item "B".' ),
		'items'  => [
			'groupDisabled_text' => [
				'type'     => 'text',
				'value'    => 'No defval on disabled inputs!',
				'disabled' => true,
				'defval'   => 'No defval',
			],
			'groupDisabled_select' => [
				'type'     => 'select',
				'items'    => [ '' ],
				'disabled' => true,
			],
			'groupDisabled_checkbox' => [
				'type'     => 'checkbox',
				'tag'      => 'Confirm!',
				'disabled' => true,
				'defval'   => 'on',
			],
			'groupDisabled_radio1' => [
				'type'     => 'radio',
				'disabled' => true,
				'items'    => [
					'rad1a' => 'Radio 1a',
					'rad1b' => 'Radio 1b',
					'rad1c' => 'Radio 1c',
				],
			],
			'groupDisabled_radio2' => [
				'type'     => 'radio',
				'items'    => [
					'rad2a' => 'Radio 2A',
					'rad2b' => [ 'tag' => 'Radio 2b', 'disabled' => true ],
					'rad2c' => 'Radio 2C',
				],
			],
			'groupDisabled_year' => [
				'type'     => 'year',
				'defval'   => '1952',
				'disabled' => true,
			],
			'groupDisabled_date' => [
				'type'     => 'date',
				'defval'   => date( 'Y-m-d' ),
				'disabled' => true,
			],
			'groupDisabled_number' => [
				'type'     => 'number',
				'defval'   => '1234',
				'disabled' => true,
			],
			'groupDisabled_file' => [
				'type'     => 'file',
				'disabled' => true,
			],
			'groupDisabled_textarea' => [
				'type'     => 'textarea',
				'value'    => 'I am disabled!',
				'disabled' => true,
			],
		],
	],
// 	'editAutoFill' => [
// 		'type'    => 'autofill',
// 		'hint'    => 'Try to push the <button>',
// 		// 'value'   => 'Find me!',
// 		// 'action'  => 'Action',
// 	],
// 	'editAutoTerm' => [
// 		'type'   => 'autoterm',
// 		'button' => 'Click <me>!',
// 		'span'   => '<a href="#">Link</a>', // disables edit mode!
// 	],
// 	'editAutoTermEdit' => [
// 		'type'   => 'autoterm',
// 		'value'  => 'slug-1',
// 	],
];
