<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * FORM input generator.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Input
{
	/**
	 * Input attributes.
	 *
	 * @var array
	 */
	protected $attr;

	/**
	 * Input definition status.
	 *
	 * @var boolean
	 */
	protected $dirty = true;

	/**
	 * Reference to Input name.
	 * @see Input::$attr['name']
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Reference to Input type.
	 * @see Input::$attr['type']
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Filtered value.
	 * @see Input::$attr['value']
	 *
	 * @var string
	 */
	protected $value = null;

	/**
	 * Collection of: current Input + group:items.
	 *
	 * @var Input[]
	 */
	protected $elements = [];

	/**
	 * Construct Input from field attributes.
	 *
	 * @param array  $attr   Input definition
	 * @param array  $values $_POST like array
	 */
	public function __construct( array $attr, array $values = [] )
	{
		$this->attr = array_merge( $this->defaults( $attr['type'] ?? null), $attr );

		// Create shortcuts to important fields
		$this->name = &$this->attr['name'];
		$this->type = &$this->attr['type'];

		// Create name variations
		$this->buildName();

		/**
		 * -------------------------------------------------------------------------------------------------------------
		 * Set input value:
		 * 1. Use 'defval' value only when 'name' or 'name_hidden' is missing in $values array
		 * 2. Use $values[name], or empty string if $values[defval] is not set
		 *
		 * Note:
		 * Unchecked checkboxes do not appear in POST data, hence extra "{name}_hidden" element passed.
		 * For list-type <inputs> with no default, use the first element value as default (mimics the browsers behavior)
		 * Do not use constructor to build value - use lazy build
		 * @see Input::buildValue()
		 */
		if ( !isset( $this->attr['value'] ) ) {
			$nameP = $this->attr['post_name'];
			$nameH = $this->attr['name_hidden'];

			// PHP creates sub-arrays for POST names like "aaa[bbb]"
			if ( $sub = $this->attr['post_group'] ) {
				$nameH = $this->attr['post_name_hidden'];
				$value = $values[$sub][$nameP] ?? $values[$sub][$nameH] ?? null;
			}
			else {
				$value = $values[$nameP] ?? $values[$nameH] ?? null;
			}

			// Verify item exists
			if ( isset( $value ) && isset( $this->attr['items'] ) ) {
				$value = isset( $this->attr['items'][$value] ) ? $value : null;
			}

			$defVal = $this->attr['defval'] = (array) ( $attr['defval'] ?? []);
			$defKey = array_key_first( $this->attr['items'] ?? []) ?? '';

			$this->attr['value'] = $value ?? $defVal[0] ?? $defKey;
		}

		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * Inputs collection:
		 * Self + child Instances if type:group
		 */
		$this->elements[$this->name] = $this; // add self

		// Add direct child instances. All nested childs are added within their own parent (recursively)
		if ( $this->isGroup() ) {
			foreach ( $this->attr['items'] as $_name => $_input ) {
				$_input['name'] = $_input['name'] ?? $_name;
				$this->elements[$_input['name']] = new static( $_input, $values );
			}
		}

		/**
		 * -------------------------------------------------------------------------------------------------------------
		 * Set filter callback
		 * @see Input::buildValue()
		 */
		if ( !isset( $this->attr['filter'] ) ) {
			$this->attr['filter'] = [ 'callback' => $this->getFilter() ];
		}
		elseif ( 'raw' === $this->attr['filter'] ) {
			$this->attr['filter'] = [ 'callback' => [ __CLASS__, 'filterNone' ] ];
		}
		// Convert: 'filter' => [ class, 'method' ] To: 'filter' => [ 'callback' => [ class, 'method' ] ]
		elseif ( is_callable( $this->attr['filter'] ) ) {
			$this->attr['filter'] = [ 'callback' => $this->attr['filter'] ];
		}

		if ( !is_callable( $this->attr['filter']['callback'] ) ) {
			/* @formatter:off */
			throw new \InvalidArgumentException( sprintf( 'Invalid filter callback for [%s] => %s',
				$this->attr['name'],
				var_export( $this->attr['filter'], true ),
			));
			/* @formatter:on */
		}
	}

	/**
	 * Get Input defaults.
	 *
	 * Optional:
	 * @type [type]     string       Input type. @see Input::input!Type!()
	 * @type [filter]   string|array Value filter callback. @see Input::__construct(), Input::buildValue()
	 * @type [value]    string       Current value @see Input::__construct()
	 * @type [defval]   string       Default value if none provided/found @see Input::__construct()
	 * @type [nodef]    string       Hide [default] button after the <input>
	 * @type [idfix]    string       String appended to generated <input> id
	 * @type [desc]     string       Main Input description
	 * @type [label]    string       Left label, like: [label] <text>
	 * @type [tag]      string       Right label, like: <radio> [label]
	 * @type [split]    string|array Glue nested elements @see Input::groupBuildJoin(), @see Input::inputRadio()
	 * @type [items]    array        Input elements (group) or sub-inputs: <radio>, <select>
	 * @type [tab]      string       Tab contents for group:toggler
	 * @type [click]    string       Javascript onClick for: group:toggler
	 * @type [disabled] string       Javascript onClick for: group:toggler
	 *
	 * @see Input::__construct()
	 * @see Input::maybeRebuild()
	 * @see Input::buildValue()
	 * @see Input::buildGroupKindJoin()
	 * @see Input::get()
	 * @see Input::attr()
	 */
	protected function defaults( ?string $type ): array
	{
		static $i = 1;

		/* @formatter:off */
		$cfg = [
			'name'     => sprintf( 'name_%d', $i++ ),
			'type'     => 'text',
			'disabled' => false,
		];
		/* @formatter:on */

		if ( in_array( $type, [ 'group', 'radio' ] ) ) {
			$cfg['split'] = ' ';
			$cfg['items'] = [];
		}

		return $cfg;
	}

	/**
	 * Get/Set Input attribute (raw).
	 * The setter returns old attr.
	 */
	public function attr( string $key = '', $val = null, $default = null )
	{
		$last = $this->attr[$key] ?? $default;

		if ( isset( $val ) ) {

			switch ( $key )
			{
				case 'name':
					return $this->name( $val );

				case 'type':
					return $this->type( $val );

				/**
				 * Auto generated attrs
				 * @see Input::maybeRebuild()
				 */
				case 'id':
				case 'for':
					throw new \RuntimeException( sprintf( 'Changing read only attribute "%s" not supported!', $key ) );
			}

			$this->attr[$key] = $val;
			$this->dirty = true;
		}

		if ( '' === $key ) {
			return $this->attr;
		}

		return $last;
	}

	/**
	 * Get Input attribute (build first) or fallback to default.
	 */
	public function get( string $key = '', $default = '' )
	{
		$this->maybeRebuild();
		return $this->attr( $key ) ?? $default;
	}

	/**
	 * Set/Get input value.
	 *
	 * Value is always build before is returned.
	 * The setter returns old value.
	 */
	public function val( $val = null )
	{
		$this->maybeRebuild();

		if ( isset( $val ) ) {
			$this->attr['value'] = $val;
			$this->dirty = true;
		}

		return $this->value;
	}

	/**
	 * Get/Set Input name.
	 */
	public function name( string $name = '' ): string
	{
		if ( $name ) {
			$this->name = $name;
			$this->dirty = true;
		}

		return $this->name;
	}

	/**
	 * Get/Set Input type.
	 */
	public function type( string $type = '' ): string
	{
		if ( $type ) {
			if ( !method_exists( $this, 'input' . ucfirst( $type ) ) ) {
				throw new \RuntimeException( sprintf( 'Unknown input type "%s"', $type ) );
			}

			$this->type = $type;
			$this->dirty = true;
		}

		return $this->type;
	}

	/**
	 * Check whether the checkbox type Input is ON.
	 */
	public function isChecked(): bool
	{
		return 'on' === $this->val();
	}

	/**
	 * Check if current instance is a type:group.
	 */
	public function isGroup(): bool
	{
		return 'group' === $this->type();
	}

	/**
	 * Get collection of instance elements.
	 *
	 * @param  bool    $withContainer Include self if type:group?
	 * @return Input[]                Self or Input elements if type:group
	 */
	public function elements( bool $withContainer = false ): array
	{
		$elements = $this->elements;

		if ( $this->isGroup() && !$withContainer ) {
			array_shift( $elements );
		}

		return $elements ?? [];
	}

	/**
	 * Traverse instance sub-elements (without Self if type:group) and yield each Input found (recursively).
	 *
	 * @param  bool    $withGroups Include elements of type:group
	 * @return Input[]             Input elements
	 */
	public function each( bool $withGroups = false )
	{
		foreach ( self::inputsEach( $this->elements( false ), $withGroups ) as $name => $Element ) {
			yield $name => $Element;
		}
	}

	/**
	 * Compile field definition.
	 *
	 * CAUTION:
	 * Don't use Input::get/attr() here, since they call this method in first place!
	 */
	protected function maybeRebuild(): void
	{
		if ( !$this->dirty ) {
			return;
		}

		$this->buildName();
		$id = self::buildId( $this->name, $this->attr['idfix'] ?? '');
		$this->attr['id'] = $this->attr['for'] = $id;

		// Add default <option>?
		if ( in_array( $this->type, [ 'select', 'year' ] ) ) {
			if ( isset( $this->attr['items'][0] ) && '' === $this->attr['items'][0] ) {
				$this->attr['items'][0] = '-- none --';
			}
		}

		switch ( $this->type )
		{
			/*
			 * Prepare radio items
			 * All <radio>s share the same name because ther's no sorrounding element like <select> for <options>
			 */
			case 'radio':
				$this->attr['items'] = $this->attr['items'] ?? [];
				foreach ( $this->attr['items'] as $value => &$item ) {
					/* @formatter:off */
					$item = [
						'name'  => $this->attr['name'],
						'id'    => self::buildId( $this->name, $value ),
						'value' => $value,
						'tag'   => $item['tag'] ?? $item,
						'item'  => (array) $item,
					];
					/* @formatter:on */
				}
				if ( null !== $key = array_key_first( $this->attr['items'] ) ) {
					$this->attr['for'] = $this->attr['items'][$key]['id'];
				}
				break;

			case 'group':
				// Set [for] attr to the first found Input
				if ( $elements = self::inputsAll( $this->elements, false ) ) {
					$this->attr['for'] = $elements[array_key_first( $elements )]->get( 'id' );
				}
				break;
		}

		$this->buildValue();
		$this->dirty = false;
	}

	/**
	 * Get default callable to filter field value.
	 *
	 * CAUTION:
	 * Don't use Input::get/attr() here, since we call this from constructor!
	 */
	protected function getFilter()
	{
		$type = $this->attr['type'] ?? '';
		$kind = $this->attr['kind'] ?? '';

		switch ( $type )
		{
			case 'checkbox':
				return [ __CLASS__, 'filterCheckbox' ];

			case 'number':
				return [ __CLASS__, 'filterNumber' ];

			case 'radio':
			case 'select':
				return [ __CLASS__, 'filterKey' ];

			case 'text':
			case 'hidden':
			case 'autofill':
			case 'autoterm':
				return [ __CLASS__, 'filterText' ];

			case 'textarea':
				return [ __CLASS__, 'filterTextarea' ];

			case 'year':
			case 'date':
				return [ __CLASS__, 'filterDate' ];

			case 'group':
				if ( 'toggle' === $kind ) {
					return [ __CLASS__, 'filterCheckbox' ];
				}
			//break;

			default:
				return [ __CLASS__, 'filterNone' ];
		}
	}

	/**
	 * Filter input value.
	 *
	 * Note:
	 * The order of [filter => args] is preserved when invoking callback function!
	 * Modify the 'value' key position to define which argument should hold the 'value' from Input
	 *
	 * CAUTION:
	 * Don't use get/attr() here, since they call maybeRebuild() which in turn calls this method!
	 */
	protected function buildValue(): void
	{
		$args = array_merge( $this->attr['filter']['args'] ?? [], [ 'value' => $this->attr['value'] ] );
		$this->value = call_user_func_array( $this->attr['filter']['callback'], $args );
	}

	/**
	 * Create name variations.
	 *
	 * 1. Create hidden element name.
	 *    - Set hidden input name for "aaa":
	 *      attr[name_hidden] = "aaa_hidden"
	 *      @see Input::inputCheckbox()
	 * 2. Create POST sub-array name => value pair:
	 *    - In case of array type names, like: "aaa[bbb]" set:
	 *      attr[name_hidden] = "aaa[bbb_hidden]"
	 *      attr[post_group] = "aaa"
	 *      attr[post_name] = "bbb"
	 */
	protected function buildName(): void
	{
		$m = [];
		preg_match( '~(.+)\[(.+)\]$~', $this->attr['name'], $m );

		$mask = '%2$s_hidden';
		$this->attr['post_group'] = $m[1] ?? '';
		$this->attr['post_name'] = $m[2] ?? $this->attr['name'];
		$this->attr['post_name_hidden'] = sprintf( $mask, $this->attr['post_group'], $this->attr['post_name'] );

		$mask = $this->attr['post_group'] ? "%1\$s[$mask]" : $mask;
		$this->attr['name_hidden'] = sprintf( $mask, $this->attr['post_group'], $this->attr['post_name'] );
	}

	/**
	 * Build unique ID string.
	 * Forbiden chars: !"#$%&\'()*+,./:;<=>?@[\]^``{|}~
	 */
	public static function buildId( string $name, string $sufix = '' ): string
	{
		$text = $name . $sufix;

		// replace non letter or digits by -
		$text = preg_replace( '~[^.\pL\d]+~u', '-', $text );

		// transliterate
		$text = iconv( 'utf-8', 'us-ascii//TRANSLIT', $text );

		// remove unwanted characters
		$text = preg_replace( '~[^-.\w]+~', '', $text );

		// trim
		$text = trim( $text, '-' );

		// remove duplicate -
		$text = preg_replace( '~-+~', '-', $text );

		return sprintf( '%s-%s', substr( md5( $text ), 0, 7 ), $text );
	}

	/**
	 * Get checked="checked" attr.
	 */
	public static function getChecked( bool $checked, string $type = 'checkbox' ): string
	{
		return $checked ? sprintf( ' %1$s="%1$s"', 'select' === $type ? 'selected' : 'checked' ) : '';
	}

	/**
	 * <label for="">Label</label> <input>.
	 * No escaping!
	 */
	protected function getLabel(): string
	{
		$str = $this->get( 'label' );
		return $str ? sprintf( '<label for="%s">%s</label>', $this->get( 'for' ), $str ) : '';
	}

	/**
	 * <input> <p>Description</p>.
	 * No escaping!
	 */
	protected function getDescription(): string
	{
		$str = $this->get( 'desc' );
		return $str ? sprintf( '<p class="description">%s</p>', $str ) : '';
	}

	/**
	 * Generate default value with onClick event.
	 */
	protected function getDefVal(): string
	{
		$defVals = $this->get( 'defval' );

		if ( !$defVals || $this->get( 'nodef' ) || $this->get( 'disabled' ) ) {
			return '';
		}

		$defVals = (array) $defVals;

		$out = [];
		foreach ( $defVals as $defVal ) {

			switch ( $this->type() )
			{
				case 'text':
				case 'textarea':
				case 'number':
				case 'date':
				case 'datetime':
					$val = addslashes( $defVal );
					$js = sprintf( 'jQuery( "#%s" ).val( "%s" )', $this->get( 'id' ), $val );
					break;

				case 'checkbox':
					$checked = 'on' === self::filterCheckbox( $defVal ) ? 'true' : 'false';
					$js = sprintf( 'jQuery( "#%s" ).prop( "checked", %s )', $this->get( 'id' ), $checked );
					break;

				case 'radio':
					$item = $this->get( 'items' )[$defVal] ?? [];
					$js = sprintf( 'jQuery( "#%s" ).prop( "checked", true )', $item['id'] ?? '');
					$defVal = $item['tag'] ?? '';
					break;

				case 'year':
				case 'select':
					$js = sprintf( 'jQuery( "#%s option[value=%s]" ).prop( "selected", true )', $this->get( 'id' ), $defVal ); // '.find("option[selected]")'
					$defVal = $this->get( 'items' )[$defVal] ?? '';
					break;

				default:
					return '';
			}

			if ( !$defVal ) {
				continue;
			}

			// Keep single quotes unescaped!
			$js = self::escAttr( $js, ENT_COMPAT );

			$out[] = sprintf( ' <label onClick="%s"><code>[%s]</code></label>', $js, self::escHtml( $defVal ) );
		}

		return implode( '', $out );
	}

	/**
	 * Get HTML mockup of all instances.
	 */
	public function getContents(): string
	{
		$this->maybeRebuild();

		$html = $this->buildInput();
		$html .= $this->getDescription();

		return $html;
	}

	/**
	 * Build Input HTML.
	 */
	protected function buildInput(): string
	{
		if ( $html = $this->get( 'html' ) ) {
			return $html;
		}

		if ( $this->isGroup() ) {
			$html .= $this->getLabel();
			$html .= $this->buildGroup();
		}
		elseif ( method_exists( $this, $method = 'input' . ucfirst( $this->type() ) ) ) {
			$html .= $this->getLabel();
			$html .= $this->$method();
			$html .= $this->getDefVal();
		}

		return $html;
	}

	/**
	 * Build group HTML.
	 */
	protected function buildGroup(): string
	{
		if ( !$this->isGroup() ) {
			return '';
		}

		// Get contents
		if ( !$html = $this->get( 'html' ) ) {

			// Get group elements (without self)
			$elements = $this->elements( false );

			$contents = [];
			foreach ( $elements as $name => $Element ) {
				$body = '';

				if ( is_callable( $render = $Element->get( 'render' ) ) ) {
					$body .= $render( $Element );
				}
				elseif ( $Element->isGroup() ) {
					$body .= $Element->getLabel();
					$body .= $Element->buildGroup();
				}
				else {
					$body .= $Element->buildInput();
				}

				// Include nested Element description
				$body .= $Element->getDescription();

				$contents[$name]['head'] = $Element->get( 'title', $name );
				$contents[$name]['body'] = $body;
			}

			// Update contents by group kind. Default to self::buildGroupKindJoin()
			$method = 'buildGroupKind' . ucfirst( $this->get( 'kind', 'join' ) );
			$html = $this->$method( $contents );
		}

		return $html;
	}

	/**
	 * Combine html contents.
	 */
	public function buildGroupKindJoin( array $contents ): string
	{
		$split = (array) $this->get( 'split' );

		switch ( count( $split ) )
		{
			/*
			 * Sorround each item with
			 * [0][1:item][1:item][2], like:
			 * [0:<table>][1:<tr><th>%s (title)</th><td>%s (html)</td></tr>][2:</table>]
			 */
			case 3:
				$rows = '';
				foreach ( $contents as $item ) {
					$rows .= sprintf( $split[1], $item['head'], $item['body'] );
				}
				$html = $split[0] . $rows . $split[2];
				break;

			// Sorround each item with [0]item[1], like <p>item</p>
			case 2:
				$html = $split[0] . implode( $split[1] . $split[0], array_column( $contents, 'body' ) ) . $split[1];
				break;

			// Just glue all items
			default:
				$html = implode( $split[0], array_column( $contents, 'body' ) );
				break;
		}

		return $html;
	}

	/**
	 * <[x] Label-1><[x] Label-2> <Tab-1><Tab-2>
	 */
	protected function buildGroupKindChecktab( array $contents ): string
	{
		// Join labels
		$head = $this->buildGroupKindJoin( $contents );

		$next = 0;
		$class = $this->get( 'class' );
		$ids = [];
		$body = '';

		foreach ( array_keys( $contents ) as $name ) {
			$Element = $this->elements[$name];

			$id = $Element->get( 'id' );
			$tab = $Element->get( 'tab' );

			// Skip non-tabs, so more elements can be attached to the group
			if ( 'checkbox' !== $Element->type() || !$tab ) {
				continue;
			}

			$next++;
			$Element->addClass( "toggle $class tab-$next" );
			$Element->addStyle( $Element->isChecked() ? '' : 'display:none' );

			/* @formatter:off */
			$body .= sprintf(
				'<div id="%1$s_tab"%3$s%4$s>%2$s</div>',
				/*1*/ $id,
				/*2*/ $tab,
				/*3*/ $Element->getAttr( 'class' ),
				/*4*/ $Element->getAttr( 'style' ),
			);
			/* @formatter:on */

			$ids[] = '#' . $id;
		}

		/* @formatter:off */
		$js = sprintf(
			'<script>jQuery(function(){' .
				'jQuery( "%1$s" ).on( "click", function(){' .
				'$box = jQuery( this );' .
				'$tab = jQuery( "#" + this.id + "_tab" );' .
				'%2$s' .
			'})' .
			'})</script>',
			/*1*/ implode( ',', $ids ),
			/*2*/ $this->get( 'click', '$box.prop( "checked" ) ? $tab.show() : $tab.hide();' ),
		);
		/* @formatter:on */

		return $head . $body . $js;
	}

	/**
	 * <[x] label/><body/>
	 */
	protected function buildGroupKindToggle( array $contents ): string
	{
		$head = [];
		$label = (array) $this->get( 'labels', 'Toogle' );

		/*
		 * Two labels: (x) Enable (o) Disable
		 * Possiblity to change labels order like this (keep key assignments):
		 * 'label'  => [ 0 => 'Disable', 1 => 'Enable'  ]
		 * 'label'  => [ 1 => 'Enable' , 0 => 'Disable' ]
		 */
		if ( 2 === count( $label ) ) {
			foreach ( $label as $k => $v ) {
				$value = 0 === $k ? 'off' : 'on';
				/* @formatter:off */
				$head[$this->name . $k] = [
					'head' => $v,
					'body' => sprintf(
						'<label><input type="radio" name="%1$s" value="%2$s"%3$s> %4$s</label>',
						/*1*/ $this->name,
						/*2*/ $value,
						/*3*/ $this->getChecked( $value === $this->val() ),
						/*4*/ self::escHtml( $v ),
				)];
				/* @formatter:on */
			}
		}
		/*
		 * One label: [x] Enable
		 */
		else {
			/* @formatter:off */
			$head[$this->name] = [
				'head' => $label[0],
				'body' => sprintf(
					'<label>' .
					'<input type="checkbox" name="%2$s" value="on"%3$s> %1$s' .
					'<input type="hidden" name="%2$s_hidden" value="off">' .
					'</label>',
					/*1*/ self::escHtml( $label[0] ),
					/*2*/ $this->name,
					/*3*/ $this->getChecked( 'on' === $this->val() ),
			)];
			/* @formatter:on */
		}

		// Glue labels with [split]
		$head = $this->buildGroupKindJoin( $head );

		// Glue [items] with inner group [split]
		$body = $this->buildGroupKindJoin( $contents );

		$this->addClass( 'toggle' );
		$this->addStyle( $this->isChecked() ? '' : 'display:none' );

		/* @formatter:off */
		return sprintf(
			'%5$s' .
			'<div id="%2$s"%3$s%4$s>' .
				'%6$s' .
			'</div>' .
			'<script>jQuery(function(){' .
				'jQuery( `input[name="%1$s"]` ).on( "click", function(){' .
				'jQuery( "#%2$s" ).toggle( jQuery(this).prop( "checked" ) && jQuery(this).val() == "on" )' .
			'})' .
			'})</script>',
			/*1*/ $this->name(),
			/*2*/ $this->get( 'id' ),
			/*3*/ $this->getAttr( 'class' ),
			/*4*/ $this->getAttr( 'style' ),
			/*5*/ $head,
			/*6*/ $body,
		);
		/* @formatter:on */
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Utils
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Sort array by 'order' key.
	 */
	public static function sort( array &$fields ): void
	{
		uasort( $fields, function ( array $a, array $b ) {
			$a['order'] = $a['order'] ?? 0;
			$b['order'] = $b['order'] ?? 0;
			return $a['order'] - $b['order'];
		} );
	}

	/**
	 * Traverse fields and yield a reference to each found field: [name] => &[field].
	 * Recursively traverse grouped fields!
	 */
	public static function &fieldsEach( array &$fields, bool $withGroups = false )
	{
		foreach ( $fields as $name => &$field ) {
			if ( isset( $field['type'] ) && 'group' === $field['type'] ) {
				if ( $withGroups ) {
					yield $name => $field;
				}
				foreach ( self::fieldsEach( $field['items'], $withGroups ) as $_name => &$_field ) {
					yield $_name => $_field;
				}
			}
			else {
				yield $name => $field;
			}
		}
	}

	/**
	 * Find all nested fields (recursively).
	 *
	 * @param  array $fields     Fields definition
	 * @param  bool  $withGroups Include type:group
	 * @return array             Nested fields found (flattened)
	 */
	public static function fieldsAll( array $fields, bool $withGroups = false ): array
	{
		$all = [];

		foreach ( self::fieldsEach( $fields, $withGroups ) as $name => $field ) {
			$all[$name] = $field;
		}

		return $all;
	}

	/**
	 * Update fields defaults.
	 *
	 * In static methods we cant use constructor to fill with defaults(), hence this method.
	 */
	public static function fieldsPrepare( array &$fields, bool $sort = true ): void
	{
		foreach ( self::fieldsEach( $fields, true ) as $name => &$field ) {
			$field['name'] = $field['name'] ?? $name;
			$field['type'] = $field['type'] ?? 'text';

			if ( !isset( $field['items'] ) && in_array( $field['type'], [ 'group', 'radio', 'select' ] ) ) {
				$field['items'] = [];
			}
		}

		if ( $sort ) {
			self::sort( $fields );
		}
	}

	/**
	 * Find field by name (recursively).
	 *
	 * @param  array         $fields Fields array
	 * @return array|boolean         Found field or false
	 */
	public static function fieldFind( string $name, array $fields ): array
	{
		$field = $fields[$name] ?? [];

		if ( !$field ) {
			foreach ( $fields as $_field ) {
				if ( 'group' === $_field['type'] ) {
					if ( $field = self::fieldFind( $name, $_field['items'] ) ) {
						break;
					}
				}
			}
		}

		return $field;
	}

	/**
	 * Collect attribute values from all fields.
	 *
	 * @param string $attr Attribute name to extract
	 * @return array       List of all found attributes
	 */
	public static function fieldPluck( string $attr, array $fields ): array
	{
		$result = [];

		foreach ( self::fieldsEach( $fields, true ) as $field ) {
			if ( isset( $field[$attr] ) ) {
				$result = array_merge( $result, (array) $field[$attr] );
			}
		}

		$result = array_unique( $result );

		return $result;
	}

	/**
	 * Traverse Inputs[] and yield each Input found (recursively).
	 * Note that PHP passes objects by reference by default.
	 *
	 * @param  Input[] $elements   Inputs
	 * @param  bool    $withGroups Include Input type:group
	 * @return Input   Input objects
	 */
	public static function inputsEach( array $elements, bool $withGroups = false )
	{
		foreach ( $elements as $name => $Element ) {
			if ( $Element->isGroup() ) {
				if ( $withGroups ) {
					yield $name => $Element;
				}
				foreach ( self::inputsEach( $Element->elements( false ), $withGroups ) as $_name => $_Element ) {
					yield $_name => $_Element;
				}
			}
			else {
				yield $name => $Element;
			}
		}
	}

	/**
	 * Find all nested Inputs (recursively).
	 *
	 * @see Input::elements()
	 *
	 * @param  Input[] $elements   Input::elements() like collection
	 * @param  bool    $withGroups Include type:group
	 * @return Input[]             All none type:group Inputs found in nested elements
	 */
	public static function inputsAll( array $elements, bool $withGroups = false ): array
	{
		if ( !$elements ) {
			return [];
		}

		$inputs = [];

		// Remove self if type:group
		$name = array_key_first( $elements );

		if ( $elements[$name]->isGroup() ) {
			// Include group?
			$withGroups && $inputs[$name] = $elements[$name];

			unset( $elements[$name] );
		}

		// Add all sub-elements
		foreach ( $elements as $Element ) {
			if ( $Element->isGroup() ) {
				// Include group?
				$withGroups && $inputs[$Element->name()] = $Element;

				$childs = self::inputsAll( $Element->elements( false ), $withGroups );
				$inputs = array_merge( $inputs, $childs );
			}
			else {
				$inputs[$Element->name()] = $Element;
			}
		}

		return $inputs;
	}

	/**
	 * Find Input by name (recursively).
	 *
	 * @param  string    $name   Input name
	 * @param  Input[]   $inputs Input collection
	 * @return self|NULL
	 */
	public static function inputsFind( string $name, array $inputs ): ?self
	{
		foreach ( $inputs as $Input ) {
			$elements = self::inputsAll( $Input->elements( true ), true );
			foreach ( $elements as $Element ) {
				if ( $Element->name() === $name ) {
					return $Element;
				}
			}
		}

		return null;
	}

	/**
	 * =================================================================================================================
	 * Filters
	 * =================================================================================================================
	 * PHP > Filter
	 * @link https://www.php.net/manual/en/filter.filters.flags.php
	 *
	 * PHP > Sanitize
	 * @link https://www.php.net/manual/en/filter.filters.sanitize.php
	 *
	 * Unicode regex
	 * @link https://stackoverflow.com/questions/1497885/remove-control-characters-from-php-string
	 */
	public static function filterNumber( string $value ): int
	{
		$value = intval( $value );
		return $value;
	}

	public static function filterFloat( string $value ): float
	{
		$value = floatval( $value );
		return $value;
	}

	public static function filterCheckbox( $value ): string
	{
		$value = in_array( $value, [ 'on', 'yes', true, 1 ], true ) ? 'on' : 'off';
		return $value;
	}

	public static function filterKey( string $value ): string
	{
		$value = preg_replace( '~[^a-zA-Z0-9_\-]~', '', $value );
		return $value;
	}

	/**
	 * Remove all non printable characters. Replace formating characters to spaces.
	 * @link https://stackoverflow.com/questions/1497885/remove-control-characters-from-php-string
	 */
	public static function filterText( string $value ): string
	{
		$value = preg_replace( '~[^\PC\s]~u', '', $value );
		$value = str_replace( [ "\r", "\n", "\t" ], ' ', $value );
		$value = preg_replace( '~ +~', ' ', $value );
		$value = trim( $value );
		return $value;
	}

	/**
	 * Remove all non printable characters. Keep formating characters.
	 */
	public static function filterTextarea( string $value ): string
	{
		$value = preg_replace( '~[^\PC\s]~u', '', $value );
		$value = trim( $value );
		return $value;
	}

	public static function filterDate( string $value ): string
	{
		$value = preg_replace( '~[^0-9/\-]~', '', $value );
		return $value;
	}

	public static function filterArray( $value )
	{
		$value = is_array( $value ) ? $value : [];
		return $value;
	}

	/**
	 * Default filter for unkown type.
	 */
	public static function filterNone( $value )
	{
		return $value;
	}

	/**
	 * =================================================================================================================
	 * Inputs
	 * =================================================================================================================
	 *
	 * Use CSS class names from WP Admin UI
	 * @see do_settings_fields()
	 *
	 * @see esc_attr() - no escaping for: &nbsp;
	 * @see esc_textarea() - seems the same as htmlspecialchars()
	 * @see esc_html()
	 * @see esc_js()
	 * @see sanitize_text_field()
	 * ---
	 * @see htmlspecialchars() - escapes: &'"<>
	 * @see htmlentities() - escapes: htmlspecialchars() + all HTML entities!
	 */

	/**
	 * Escapes: &"<>
	 *
	 * @param int $flags
	 * ENT_COMPAT  - Will convert double-quotes and leave single-quotes alone (Default)
	 * ENT_QUOTES  - Will convert both double and single quotes
	 * ENT_HTML401 - I.e. single quotes to &#039; (Default)
	 * ENT_HTML5   - I.e. single quotes to &apos;
	 */
	public static function escAttr( string $text, int $flags = ENT_QUOTES | ENT_HTML5 ): string
	{
		$text = htmlspecialchars( $text, $flags );
		return $text;
	}

	/**
	 * Escapes: all HTML
	 *
	 * @param int $flags @see Input::escAttr()
	 */
	public static function escHtml( string $text, int $flags = ENT_QUOTES | ENT_HTML5 ): string
	{
		$text = htmlentities( $text, $flags );
		return $text;
	}

	/**
	 * Help render html tag attribute.
	 *
	 * @param array $input Replaces $this->input
	 */
	public function getAttr( string $key, ?string $default = null, ?array $input = null ): string
	{
		$val = $input[$key] ?? $this->attr( $key ) ?? $default ?? '';

		if ( ( is_string( $val ) || is_bool( $val ) ) && !$val ) {
			return '';
		}

		switch ( $key )
		{
			case 'disabled':
				$mask = '%1$s';
				break;

			case 'extra':
				$mask = '%2$s';
				break;

			default:
				$mask = '%1$s="%2$s"';
				break;
		}

		$val = sprintf( " $mask", $key, $val );

		return $val;
	}

	/**
	 * Add css class to Input attributes.
	 */
	public function addClass( string $class ): void
	{
		if ( !$class ) {
			return;
		}
		$classes = explode( ' ', $this->get( 'class' ) );
		if ( !in_array( $class, $classes ) ) {
			$classes[] = $class;
		}
		$this->attr( 'class', trim( implode( ' ', $classes ) ) );
	}

	/**
	 * Add css style to Input attributes.
	 */
	public function addStyle( string $style ): void
	{
		if ( !$style ) {
			return;
		}
		$styles = preg_replace( '~\s~', '', $this->get( 'style' ) );
		$styles = explode( ';', $styles );
		if ( !in_array( $style, $styles ) ) {
			$styles[] = $style;
		}
		$this->attr( 'style', trim( implode( ' ', $styles ) ) );
	}

	/**
	 * <button></button> <spinner/> <span>ajax</span> <div>log</div>
	 */
	protected function inputAjaxdiv()
	{
		/* @formatter:off */
		return sprintf(
			'<button id="%1$s_button" type="button" class="button">%2$s</button> ' .
			'<span id="%1$s_spin" class="spinner inline"></span> ' .
			'<span id="%1$s_ajax"></span>' .
			'<div id="%1$s_div"></div>',
			/*1*/ $this->get( 'id' ),
			/*2*/ self::escHtml( $this->get( 'button', 'Start' ) ), // Button title!
		);
		/* @formatter:on */
	}

	/**
	 * <input type="hidden">
	 */
	protected function inputHidden(): string
	{
		/* @formatter:off */
		return sprintf(
			'<input type="hidden" id="%1$s" name="%2$s" value="%3$s"%4$s>',
			/*1*/ $this->get( 'id' ),
			/*2*/ $this->name(),
			/*3*/ self::escAttr( $this->val() ),
			/*4*/ $this->getAttr( 'disabled' ),
		);
		/* @formatter:on */
	}

	/**
	 * <input type="text">
	 */
	protected function inputText(): string
	{
		/* @formatter:off */
		return sprintf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s"%5$s%6$s%7$s%8$s>%9$s',
			/*1*/ $this->get( 'id' ),
			/*2*/ $this->name(),
			/*3*/ self::escAttr( $this->val() ),
			/*4*/ self::escAttr( $this->get( 'hint' ) ),
			/*5*/ $this->getAttr( 'class', 'regular-text code' ),
			/*6*/ $this->getAttr( 'style' ),
			/*7*/ $this->getAttr( 'extra' ),
			/*8*/ $this->getAttr( 'disabled' ),
			/*9*/ self::escHtml( $this->get( 'tag' ) ),
		);
		/* @formatter:on */
	}

	/**
	 * <textarea>
	 */
	protected function inputTextarea(): string
	{
		/* @formatter:off */
		return sprintf(
			'<textarea id="%1$s" name="%2$s" placeholder="%4$s" rows="%5$s"%6$s%7$s>%3$s</textarea>',
			/*1*/ $this->get( 'id' ),
			/*2*/ $this->name(),
			/*3*/ self::escAttr( $this->val() ),
			/*4*/ self::escAttr( $this->get( 'hint' ) ),
			/*5*/ $this->get( 'rows', 3 ),
			/*6*/ $this->getAttr( 'class', 'large-text code' ),
			/*7*/ $this->getAttr( 'disabled' ),
		);
		/* @formatter:on */
	}

	/**
	 * <input type="checkbox">
	 * Note: unchecked checkbox do not appear in $_POST array, hence extra input:hidden holdnig "off" value
	 */
	protected function inputCheckbox(): string
	{
		// No def by default for checkboxes!
		$this->attr( 'nodef', $this->get( 'nodef', true ) );

		/* @formatter:off */
		return sprintf(
			'<label%6$s>' .
				'<input type="checkbox" id="%1$s" name="%2$s" value="on"%6$s%7$s%8$s%9$s%4$s>%5$s' .
				'<input type="hidden" name="%3$s" value="off"%8$s>' .
			'</label>',
			/*1*/ $this->get( 'id' ),
			/*2*/ $this->name(),
			/*3*/ $this->get( 'name_hidden' ),
			/*4*/ $this->getChecked( 'on' === $this->val() ),
			/*5*/ self::escHtml( $this->get( 'tag' ) ),
			/*6*/ $this->getAttr( 'class' ),
			/*7*/ $this->getAttr( 'style' ),
			/*8*/ $this->getAttr( 'extra' ),
			/*9*/ $this->getAttr( 'disabled' ), // both checkbox!
		);
		/* @formatter:on */
	}

	/**
	 * <input type="radio"> <input type="radio"> ...
	 *
	 * Items build in:
	 * @see Input::maybeRebuild()
	 */
	protected function inputRadio(): string
	{
		$out = [];

		/* @formatter:off */
		foreach( $this->attr( 'items' ) as $item ) {
			$out[] = sprintf(
				'<label%6$s><input type="radio" id="%1$s" name="%2$s" value="%3$s"%7$s%8$s%9$s%4$s>%5$s</label>',
				/*1*/ $item['id'],
				/*2*/ $item['name'],
				/*3*/ $item['value'],
				/*4*/ $this->getChecked( $item['value'] == $this->val() ), // dont check types!
				/*5*/ self::escHtml( $item['tag'] ),
				/*6*/ $this->getAttr( 'class'   , null, $item['item'] ),
				/*7*/ $this->getAttr( 'style'   , null, $item['item'] ),
				/*8*/ $this->getAttr( 'extra'   , null, $item['item'] ),
				/*9*/ $this->getAttr( 'disabled', null, $item['item'] ),
			);
		}
		/* @formatter:on */

		return implode( $this->get( 'split' ), $out );
	}

	/**
	 * <select> <option> <option> ...
	 */
	protected function inputSelect()
	{
		/* @formatter:off */
		$out = sprintf(
			'<select id="%1$s" name="%2$s"%3$s%4$s%5$s%6$s>',
			/*1*/ $this->get( 'id' ),
			/*2*/ $this->name(),
			/*3*/ $this->getAttr( 'class' ),
			/*4*/ $this->getAttr( 'style' ),
			/*5*/ $this->getAttr( 'extra' ),
			/*6*/ $this->getAttr( 'disabled' ),
		);

		foreach ( $this->attr( 'items' ) as $value => $tag ) {
			$out .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				/*1*/ self::escAttr( $value ),
				/*2*/ $this->getChecked( $this->val() == $value, 'select' ), // dont check value types!
				/*3*/ self::escHtml( $tag ),
			);
		}
		/* @formatter:on */

		return $out . '</select>';
	}

	/**
	 * <select> <option> <option> ...
	 */
	protected function inputYear()
	{
		$now = date( 'Y' );

		/* @formatter:off */
		$attr = array_merge([
			'min'  => $now,
			'max'  => $now,
			'step' => 1,
			'dir'  => 'asc',
		], $this->attr );
		/* @formatter:on */

		$items = range( $attr['min'], $attr['max'], $attr['step'] );

		if ( 'desc' === $attr['dir'] ) {
			$items = array_reverse( $items );
		}

		$this->attr( 'items', array_combine( $items, $items ) );

		return $this->inputSelect();
	}

	/**
	 * <input type="date">
	 */
	protected function inputDate()
	{
		/* @formatter:off */
		return sprintf(
			'<input type="date" id="%1$s" name="%2$s" value="%3$s"%4$s%5$s%6$s%7$s%8$s>%9$s',
			/*1*/ $this->get( 'id' ),
			/*2*/ $this->name(),
			/*3*/ self::escAttr( $this->val() ),
			/*4*/ $this->getAttr( 'min' ),
			/*5*/ $this->getAttr( 'max' ),
			/*6*/ $this->getAttr( 'step' ),
			/*7*/ $this->getAttr( 'class' ),
			/*8*/ $this->getAttr( 'disabled' ),
			/*9*/ self::escHtml( $this->get( 'tag' ) ),
		);
		/* @formatter:on */
	}

	/**
	 * <input type="datetime-local">
	 */
	protected function inputDateTime()
	{
		/* @formatter:off */
		return sprintf(
			'<input type="datetime-local" id="%1$s" name="%2$s" value="%3$s"%4$s%5$s%6$s%7$s%8$s>%9$s',
			/*1*/ $this->get( 'id' ),
			/*2*/ $this->name(),
			/*3*/ self::escAttr( $this->val() ),
			/*4*/ $this->getAttr( 'min' ),
			/*5*/ $this->getAttr( 'max' ),
			/*6*/ $this->getAttr( 'step' ),
			/*7*/ $this->getAttr( 'class' ),
			/*8*/ $this->getAttr( 'disabled' ),
			/*9*/ self::escHtml( $this->get( 'tag' ) ),
		);
		/* @formatter:on */
	}

	/**
	 * <input type="number">
	 */
	protected function inputNumber(): string
	{
		/* @formatter:off */
		return sprintf(
			'<input type="number" id="%1$s" name="%2$s" value="%3$s"%4$s%5$s%6$s%7$s%8$s>%9$s',
			/*1*/ $this->get( 'id' ),
			/*2*/ $this->name(),
			/*3*/ self::escAttr( $this->val() ),
			/*4*/ $this->getAttr( 'min' ),
			/*5*/ $this->getAttr( 'max' ),
			/*6*/ $this->getAttr( 'step' ),
			/*7*/ $this->getAttr( 'class', 'small-text' ),
			/*8*/ $this->getAttr( 'disabled' ),
			/*9*/ self::escHtml( $this->get( 'tag' ) ),
		);
		/* @formatter:on */
	}

	/**
	 * <input type="file" ... >
	 */
	protected function inputFile()
	{
		/* @formatter:off */
		return sprintf(
			'<input type="file" id="%1$s" name="%2$s"%3$s%4$s>',
			/*1*/ $this->get( 'id' ),
			/*2*/ $this->name(),
			/*3*/ $this->getAttr( 'extra' ),
			/*4*/ $this->getAttr( 'disabled' ),
		);
		/* @formatter:on */
	}

	/**
	 * <html>
	 */
	protected function inputHtml()
	{
		return $this->val();
	}
}
