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
	 * @var array
	 */
	protected $cfg;

	/**
	 * Input definition status.
	 */
	protected $isDirty;

	/**
	 * Reference to Input name.
	 * @see Input::$attr['name']
	 * @var string
	 */
	protected $name;

	/**
	 * Reference to Input type.
	 * @see Input::$attr['type']
	 * @var string
	 */
	protected $type;

	/**
	 * Filtered value.
	 * @see Input::$attr['value']
	 * @var string
	 */
	protected $value;

	/**
	 * Collection of: current Input + group:items.
	 * @var Input[]
	 */
	protected $elements = [];

	/**
	 * Parent input if groupped.
	 * @var Input
	 */
	protected $Parent;

	/**
	 * Construct html Input from php array.
	 *
	 * Config: (all values optional)
	 * [type]     string       Input type. @see Input::input!Type!()
	 * [filter]   string|array Value filter callback. @see Input::__construct(), Input::buildValue()
	 * [value]    string       Current value @see Input::__construct()
	 * [defval]   string       Default value if none provided/found @see Input::__construct()
	 * [nodef]    bool         Hide input [default] string
	 * [idfix]    string       String appended to generated <input> id
	 * [desc]     string       Main Input description
	 * [label]    string       Input label, like: <label><text>
	 * [tag]      string       Input text tag, eg. <select><option>[tag]</option>, <radio> [tag]
	 * [split]    string|array Glue nested elements @see Input::groupBuildJoin(), @see Input::inputRadio()
	 * [items]    array        Groupped inputs definition or input sub elements, see: <radio>, <select>
	 * [tab]      string       Tab contents for group:toggler
	 * [click]    string       Javascript onClick for: group:toggler
	 * [readonly] bool         Attr [readonly]
	 * [disabled] bool         Attr [disabled]
	 * [class]    string       Input CSS class. If type:group - fallback class for nested inputs
	 * [inline]   bool         Groupped inputs display inline
	 *
	 * @param array $cfg    Input definition
	 * @param array $values $_POST like array
	 * @param array $values $_POST like array
	 */
	public function __construct( array $cfg, array $values = [], ?Input $Parent = null )
	{
		$this->Parent = $Parent;
		$this->isDirty = true;

		$this->cfg = $this->defaults( $cfg );

		// Create shortcuts to important fields
		$this->name = &$this->cfg['name'];
		$this->type = &$this->cfg['type'];

		// Create name variations
		$this->buildName();

		/**
		 \* -------------------------------------------------------------------------------------------------------------
		 * Set input value:
		 * 1. Use 'defval' value only when 'name' or 'name_hidden' is missing in $values array
		 * 2. Use $values[name], or empty string if $values[defval] is not set
		 *
		 * NOTE:
		 * Unchecked checkboxes do not appear in POST data, hence extra "{name}_hidden" element passed.
		 *
		 * For list-type <inputs> with no default, use the first element value as default (mimics the browsers behavior)
		 * To keep radios initially unchecked use a foreign 'defval' value
		 *
		 * Do not use constructor to build value - use lazy build
		 * @see Input::buildValue()
		 *
		 * NOTE:
		 * The Input [type:group] normally doesnt have value, except [kind:toggle] which holds the state on/off
		 */
		if ( !isset( $this->cfg['value'] ) ) {
			$nameP = $this->cfg['post_name'];
			$nameH = $this->cfg['name_hidden'];

			// PHP creates sub-arrays for POST names like "aaa[bbb]"
			if ( $sub = $this->cfg['post_group'] ) {
				$nameH = $this->cfg['post_name_hidden'];
				$value = $values[$sub][$nameP] ?? $values[$sub][$nameH] ?? null;
			}
			else {
				$value = $values[$nameP] ?? $values[$nameH] ?? null;
			}

			// Skip array values when group:name == inputs namespace
			if ( is_array( $value ) ) {
				$value = null;
			}

			$defVal = (array) $this->cfg['defval'];
			$defVal = $defVal[0] ?? null;

			// Fallback to first item if value is out of list
			if ( 'select' === $this->type ) {
				$defVal = $defVal ?? array_key_first( $this->cfg['items'] );
			}

			// Verify value for list type inputs
			if ( !$this->isGroup() && isset( $this->cfg['items'] ) ) {
				$value = isset( $this->cfg['items'][$value] ) ? $value : null;
			}

			$this->cfg['value'] = $value ?? $defVal ?? '';
		}

		/*
		 \* -------------------------------------------------------------------------------------------------------------
		 * Inputs collection:
		 * Self + child Instances if type:group
		 */
		$this->elements[$this->name] = $this; // add self

		// Add direct child instances. All nested childs are added within their own parent (recursively)
		if ( $this->isGroup() ) {
			foreach ( $this->cfg['items'] as $_name => $_input ) {
				$_input['name'] = $_input['name'] ?? $_name;
				$this->elements[$_input['name']] = new static( $_input, $values, $this );
			}
		}

		/**
		 \* -------------------------------------------------------------------------------------------------------------
		 * Set filter callback
		 * @see Input::buildValue()
		 */
		if ( !isset( $this->cfg['filter'] ) ) {
			$this->cfg['filter'] = [ 'callback' => $this->getFilter() ];
		}
		elseif ( 'raw' === $this->cfg['filter'] ) {
			$this->cfg['filter'] = [ 'callback' => [ __CLASS__, 'filterNone' ] ];
		}
		// Convert: 'filter' => [ class, 'method' ] To: 'filter' => [ 'callback' => [ class, 'method' ] ]
		elseif ( is_callable( $this->cfg['filter'] ) ) {
			$this->cfg['filter'] = [ 'callback' => $this->cfg['filter'] ];
		}

		if ( !is_callable( $this->cfg['filter']['callback'] ) ) {
			/* @formatter:off */
			throw new \InvalidArgumentException( sprintf( 'Invalid filter callback for [%s] => %s',
				$this->cfg['name'],
				var_export( $this->cfg['filter'], true ),
			));
			/* @formatter:on */
		}
	}

	/**
	 * Get Input defaults.
	 */
	private function defaults( array $cfg ): array
	{
		static $i = 0;

		$i++;
		$disabled = $this->Parent && $this->Parent->cfg( 'disabled' );
		$readonly = $this->Parent && $this->Parent->cfg( 'readonly' );

		/* @formatter:off */
		$defaults = [
			'type'     => 'text',
			'name'     => 'name_' . $i,
			'value'    => null,
			'defval'   => null,
			'disabled' => $disabled ?? false,
			'readonly' => $readonly ?? false,
		];
		/* @formatter:on */

		if ( in_array( $cfg['type'], [ 'group', 'radio', 'select' ] ) ) {
			$defaults['items'] = [];
		}

		return array_merge( $defaults, $cfg );
	}

	/**
	 * Get/Set Input attribute (raw).
	 * The setter returns old attr.
	 */
	public function cfg( string $key = '', $val = null, $default = null )
	{
		$last = $this->cfg[$key] ?? $default;

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

			$this->cfg[$key] = $val;
			$this->isDirty = true;
		}

		if ( '' === $key ) {
			return $this->cfg;
		}

		return $last;
	}

	/**
	 * Get Input attribute (build first) or fallback to default.
	 */
	public function get( string $key = '', $default = '' )
	{
		$this->maybeRebuild();
		return $this->cfg( $key ) ?? $default;
	}

	/**
	 * Set/Get input value.
	 *
	 * Value is always build before is returned.
	 * The setter returns old value.
	 */
	public function val( $val = null )
	{
		if ( isset( $val ) ) {
			$this->cfg['value'] = $val;
			$this->isDirty = true;
		}

		$this->maybeRebuild();

		return $this->value;
	}

	/**
	 * Get/Set Input name.
	 */
	public function name( string $name = '' ): string
	{
		if ( $name ) {
			$this->name = $name;
			$this->isDirty = true;
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
			$this->isDirty = true;
		}

		return $this->type;
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
	 * @param  bool $withContainer Include self if type:group?
	 * @return Input[] Self or Input elements if type:group
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
	 * @param  bool $withGroups Include elements of type:group
	 * @return Input[] Input elements
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
		if ( !$this->isDirty ) {
			return;
		}

		$this->buildName();
		$id = self::buildId( $this->name, $this->cfg['idfix'] ?? '');
		$this->cfg['id'] = $this->cfg['for'] = $id;

		// Add default <option>?
		if ( in_array( $this->type, [ 'select', 'year' ] ) ) {
			if ( isset( $this->cfg['items'][0] ) && '' === $this->cfg['items'][0] ) {
				$this->cfg['items'][0] = '-- none --';
			}
		}

		switch ( $this->type )
		{
			/*
			 * Prepare radio items
			 * All <radio>s share the same name because ther's no sorrounding element like <select> for <options>
			 */
			case 'radio':
				foreach ( $this->cfg['items'] as $value => $item ) {
					// Do not rebuild already created items
					if ( isset( $item['item'] ) ) {
						continue;
					}

					$id = self::buildId( $this->name, $value );
					!isset( $this->cfg['for'] ) && $this->cfg['for'] = $id;
					/* @formatter:off */
					$this->cfg['items'][$value] = [
						'id'    => $id,
						'name'  => $this->cfg['name'],
						'value' => $value,
						'tag'   => $item['tag'] ?? $item,
						'item'  => (array) $item,
					];
					/* @formatter:on */
				}
				break;

			case 'group':
				// Set [for] attr to the first found Input
				if ( $elements = self::inputsAll( $this->elements, false ) ) {
					$this->cfg['for'] = $elements[array_key_first( $elements )]->get( 'id' );
				}
				break;
		}

		$this->buildValue();
		$this->isDirty = false;
	}

	/**
	 * Get default callable to filter field value.
	 *
	 * CAUTION:
	 * Don't use Input::get/attr() here, since we call this from constructor!
	 */
	protected function getFilter()
	{
		$type = $this->cfg['type'] ?? '';
		$kind = $this->cfg['kind'] ?? '';

		switch ( $type )
		{
			case 'checkbox':
			case 'switch':
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
		$args = array_merge( $this->cfg['filter']['args'] ?? [], [ 'value' => $this->cfg['value'] ] );
		$this->value = call_user_func_array( $this->cfg['filter']['callback'], $args );
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
		preg_match( '~(.+)\[(.+)\]$~', $this->cfg['name'], $m );

		$this->cfg['post_group'] = $m[1] ?? '';
		$this->cfg['post_name'] = $m[2] ?? $this->cfg['name'];
		$this->cfg['post_name_hidden'] = $this->cfg['post_name'] . '_hidden';

		/* @formatter:off */
		$this->cfg['name_hidden'] = strtr( $this->cfg['post_group'] ? '{group}[{name}]' : '{name}', [
			'{group}' => $this->cfg['post_group'],
			'{name}'  => $this->cfg['post_name_hidden'],
		]);
		/* @formatter:on */
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
		$attr = 'select' === $type ? 'selected' : 'checked';
		return $checked ? " $attr=\"$attr\"" : '';
	}

	/**
	 * <label for="input">Label</label> <input>.
	 * No escaping!
	 */
	protected function getLabel(): string
	{
		if ( $text = $this->get( 'label' ) ) {
			/* @formatter:off */
			return strtr( '<label for="{for}" class="{class}">{text}</label>', [
				'{for}'   => $this->get( 'for' ),
				'{text}'  => $text,
				'{class}' => $this->get( 'label_class', 'form-label' ),
			]);
			/* @formatter:on */
		}

		return '';
	}

	/**
	 * <input> <div>Description</div>.
	 * No escaping!
	 */
	protected function getDescription(): string
	{
		if ( $text = $this->get( 'desc' ) ) {
			/* @formatter:off */
			return strtr( '<div class="{class}">{text}</div>', [
				'{text}'  => $text,
				'{class}' => $this->get( 'desc_class', 'form-text' ),
			]);
			/* @formatter:on */
		}

		return '';
	}

	/**
	 * Generate default value with onClick event.
	 */
	protected function getDefVal(): string
	{
		$defVals = (array) $this->get( 'defval' );

		if ( $this->get( 'nodef' ) || $this->get( 'disabled' ) || !$defVals ) {
			return '';
		}

		$out = [];
		foreach ( $defVals as $defVal ) {

			if ( !$defVal ) {
				continue;
			}

			switch ( $this->type() )
			{
				case 'text':
				case 'textarea':
				case 'number':
				case 'date':
				case 'datetime':
					$val = preg_replace( "~\R~", '', $defVal );
					$val = addslashes( $val );
					$js = sprintf( 'jQuery( "#%s" ).val( "%s" )', $this->get( 'id' ), $val );
					break;

				case 'checkbox':
				case 'switch':
					$checked = self::filterCheckbox( $defVal ) ? 'true' : 'false';
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
			 * [0:<table>][1:<tr><th>{head}</th><td>{body}</td></tr>][2:</table>]
			 */
			case 3:
				$rows = '';
				foreach ( $contents as $item ) {
					$rows .= strtr( $split[1], [ '{head}' => $item['head'], '{body}' => $item['body'] ] );
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
	 * Form grid.
	 * @link https://getbootstrap.com/docs/5.3/forms/layout/#form-grid
	 */
	protected function buildGroupKindGrid( array $contents ): string
	{
		$row = $this->get( 'row_class', 'row align-items-center' );
		$col = $this->get( 'col_class', 'col' );

		$this->cfg( 'split', [ "<div class=\"$row\">", "<div class=\"$col\">{body}</div>", '</div>' ] );
		return $this->buildGroupKindJoin( $contents );
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

			// Remove original input:checkbox classes for {tab} element!
			$Element->cfg( 'class', "toggle $class tab-$next" );
			$Element->addStyle( $Element->val() ? '' : 'display:none' );

			/* @formatter:off */
			$body .= strtr( '<div id="{id}_tab"{class}{style}>{tab}</div>', [
				'{id}'    => $id,
				'{tab}'   => $tab,
				'{class}' => $Element->getAttr( 'class' ),
				'{style}' => $Element->getAttr( 'style' ),
			]);
			/* @formatter:on */

			$ids[] = '#' . $id;
		}

		/* @formatter:off */
		$js = strtr( <<<'EOT'
			<script>
			jQuery(() => jQuery( "{ids}" ).on( "click", (event) => {
				const $box = jQuery(event.currentTarget);
				const $tab = jQuery( "#" + event.currentTarget.id + "_tab" );
				{code}
			}))
			</script>
			EOT, [
			'{ids}'  => implode( ',', $ids ),
			'{code}' => $this->get( 'click', '$box.prop( "checked" ) ? $tab.show() : $tab.hide();' ),
		]);
		/* @formatter:on */

		return $head . $body . $js;
	}

	/**
	 * <[x] label/><body/>
	 */
	protected function buildGroupKindToggle( array $contents ): string
	{
		$labels = (array) $this->get( 'labels', 'Toogle' );

		/* @formatter:off */
		$head = [
			'name'  => $this->name,
			'value' => $this->val(),
			'split' => $this->get( 'split' ),
		];
		/* @formatter:on */

		/*
		 * One label: [x] Enable
		 */
		if ( 1 === count( $labels ) ) {
			$head['type'] = 'checkbox';
			$head['tag'] = $labels[0];
		}
		/*
		 * Two labels: (x) Enable (o) Disable
		 * Possiblity to change labels order like this (keep key assignments):
		 * cfg[label] => [ 0 => 'Disable', 1 => 'Enable'  ]
		 * cfg[label] => [ 1 => 'Enable' , 0 => 'Disable' ]
		 */
		else {
			$head['type'] = 'radio';
			$head['items'] = $labels;
		}

		// Glue labels with [split]
		$Head = new static( $head );
		$body = $this->buildGroupKindJoin( $contents );

		$this->addClass( 'toggle' );
		$this->addStyle( $this->val() ? '' : 'display:none' );

		/* @formatter:off */
		return strtr( <<<'EOT'
			{head}
			<div id="{id}"{class}{style}>{body}</div>
			<script>
			jQuery(() => jQuery( `input[name="{name}"]` ).on( "click", (event) => {
				const $el = jQuery(event.currentTarget);
				const show = $el.val() == "1" || $el.val() == "on";
				jQuery( "#{id}" ).toggle( $el.prop( "checked" ) && show );
			}))
			</script>
			EOT, [
			'{id}'    => $this->get( 'id' ) . '_body',
			'{name}'  => $this->name(),
			'{class}' => $this->getAttr( 'class' ),
			'{style}' => $this->getAttr( 'style' ),
			'{head}'  => $Head->getContents(),
			'{body}'  => $body,
		]);
		/* @formatter:on */
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Utils
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

	public static function filterCheckbox( $value )
	{
		$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		return $value;
	}

	public static function filterKey( string $value ): string
	{
		$value = preg_replace( '~[^a-zA-Z0-9_\-]~', '', $value );
		return $value;
	}

	/**
	 * Remove all non printable characters. Replace formating characters to spaces.
	 *
	 * \PC  : visible characters
	 * \PCc : control characters
	 * \PCn : unassigned characters
	 * \PCs : UTF-8-invalid characters
	 * \PCf : formatting characters
	 *
	 * @link https://en.wikipedia.org/wiki/List_of_Unicode_characters
	 * @link https://www.regular-expressions.info/unicode.html#category
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
	public static function escAttr( ?string $text = '', int $flags = ENT_QUOTES | ENT_HTML5 ): string
	{
		$text = htmlspecialchars( $text, $flags );
		return $text;
	}

	/**
	 * Escapes: all HTML
	 *
	 * @param int $flags @see Input::escAttr()
	 */
	public static function escHtml( ?string $text = '', int $flags = ENT_QUOTES | ENT_HTML5 ): string
	{
		$text = htmlentities( $text, $flags );
		return $text;
	}

	/**
	 * Help render html tag attribute.
	 *
	 * @param array $input Replaces $this->input
	 */
	public function getAttr( string $name, ?string $default = null, ?array $input = null ): string
	{
		$this->maybeRebuild();
		$value = $input[$name] ?? $this->cfg( $name ) ?? $default ?? '';

		if ( '' === $value || false === $value ) {
			return '';
		}

		switch ( $name )
		{
			case 'disabled':
			case 'readonly':
				$format = '{name}';
				break;

			case 'extra':
				$format = '{value}';
				break;

			default:
				$format = '{name}="{value}"';
		}

		return strtr( " $format", [ '{name}' => $name, '{value}' => $value ] );
	}

	/**
	 * Add css class to Input attributes.
	 */
	public function addClass( $class ): void
	{
		$this->addCss( 'class', $class );
	}

	/**
	 * Add css style to Input attributes.
	 */
	public function addStyle( $style ): void
	{
		$this->addCss( 'style', $style );
	}

	/**
	 * Add unique css attribute: trim, no empties, no double spaces.
	 *
	 * @param string       $key Cfg[key]
	 * @param string|array $add New attribute(s)
	 */
	public function addCss( string $key, $add = null ): void
	{
		$sep = 'style' === $key ? ';' : ' ';
		$end = 'style' === $key ? ';' : '';

		$out = preg_replace( '~[\s]+~', ' ', $this->get( $key ) );
		$out = trim( $out );
		$out = explode( $sep, $out );

		$add = array_map( 'trim', (array) $add );
		$out = array_merge( $out, $add );

		$out = array_unique( $out );
		$out = array_filter( $out );

		$out = implode( $sep, $out ) . ( $out ? $end : '' );

		$this->cfg( $key, $out );
	}

	/**
	 * <button></button> <spinner/> <span>ajax</span> <div>log</div>
	 */
	protected function inputAjaxdiv()
	{
		/* @formatter:off */
		return strtr( <<<EOT
			<button id="{id}_button" type="button" class="button">{button}</button>
			<span id="{id}_spin" class="spinner inline"></span>
			<span id="{id}_ajax"></span>
			<div id="{id}_div"></div>
			EOT, [
			'{id}'     => $this->get( 'id' ),
			'{button}' => self::escHtml( $this->get( 'button', 'Start' ) ), // Button title
		]);
		/* @formatter:on */
	}

	/**
	 * <input type="hidden">
	 */
	protected function inputHidden(): string
	{
		/* @formatter:off */
		return strtr( '<input type="hidden" id="{id}" name="{name}" value="{value}"{disabled}>', [
			'{id}'       => $this->get( 'id' ),
			'{name}'     => $this->name(),
			'{value}'    => self::escAttr( $this->val() ),
			'{disabled}' => $this->getAttr( 'disabled' ),
		]);
		/* @formatter:on */
	}

	/**
	 * <input type="text">
	 */
	protected function inputText(): string
	{
		/* @formatter:off */
		return strtr( <<<EOT
			<input type="text" id="{id}" name="{name}" value="{value}" placeholder="{hint}"
			{class}{style}{extra}{disabled}{readonly}
			>{tag}
			EOT, [
			'{id}'        => $this->get( 'id' ),
			'{name}'      => $this->name(),
			'{value}'     => self::escAttr( $this->val() ),
			'{hint}'      => self::escAttr( $this->get( 'hint' ) ),
			'{class}'     => $this->getAttr( 'class', 'form-control' ),
			'{style}'     => $this->getAttr( 'style' ),
			'{extra}'     => $this->getAttr( 'extra' ),
			'{disabled}'  => $this->getAttr( 'disabled' ),
			'{readonly}'  => $this->getAttr( 'readonly' ),
			'{tag}'       => self::escHtml( $this->get( 'tag' ) ),
		]);
		/* @formatter:on */
	}

	/**
	 * <textarea>
	 */
	protected function inputTextarea(): string
	{
		/* @formatter:off */
		return strtr( <<<EOT
			<textarea id="{id}" name="{name}" placeholder="{hint}" rows="{rows}"
			{class}{disabled}{readonly}>{value}</textarea>
			EOT, [
			'{id}'       => $this->get( 'id' ),
			'{name}'     => $this->name(),
			'{value}'    => self::escAttr( $this->val() ),
			'{hint}'     => self::escAttr( $this->get( 'hint' ) ),
			'{rows}'     => $this->get( 'rows', 3 ),
			'{class}'    => $this->getAttr( 'class', 'form-control' ),
			'{disabled}' => $this->getAttr( 'disabled' ),
			'{readonly}' => $this->getAttr( 'readonly' ),
		]);
		/* @formatter:on */
	}

	/**
	 * <input type="checkbox" class="form-check">
	 * NOTE:
	 * - unchecked checkbox do not appear in $_POST array, hence extra input:hidden holdnig "off" value
	 * - cfg[class] is merged from both: current and parent (group)
	 * - cfg[class] is for <div>
	 * - cfg[style] is for <checkbox>
	 */
	protected function inputCheckbox(): string
	{
		$nodef = $this->get( 'nodef', true );
		$inline = $this->get( 'inline', true );
		$class = explode( ' ', $this->get( 'class' ) );

		// Prioritize parent attrs
		if ( $this->Parent ) {
			$nodef = $this->Parent->get( 'nodef', $nodef );
			$inline = $this->Parent->get( 'inline', $inline );
			$class = array_merge( $class, explode( ' ', $this->Parent->get( 'class' ) ) );
		}

		// Set default attributes
		$this->cfg( 'nodef', $nodef );
		$this->cfg( 'inline', $inline );

		$class[] = 'form-check';
		$inline && $class[] = 'form-check-inline';
		$this->addCss( 'class', $class );

		/* @formatter:off */
		return strtr( <<<EOT
			<div{class}>
				<input type="hidden" name="{name2}" value="off"{disabled}>
				<input class="form-check-input" type="checkbox" id="{id}" name="{name}" value="on"
				{style}{extra}{disabled}{checked}>
				<label class="form-check-label" for="{id}">{tag}</label>
			</div>
			EOT
			, [
			'{id}'       => $this->get( 'id' ),
			'{name}'     => $this->name(),
			'{name2}'    => $this->get( 'name_hidden' ),
			'{checked}'  => $this->getChecked( $this->val() ),
			'{tag}'      => self::escHtml( $this->get( 'tag' ) ),
			'{class}'    => $this->getAttr( 'class' ),
			'{style}'    => $this->getAttr( 'style' ),
			'{extra}'    => $this->getAttr( 'extra' ),
			'{disabled}' => $this->getAttr( 'disabled' ), // both checkbox!
		]);
		/* @formatter:on */
	}

	/**
	 * <input type="checkbox" class="form-check form-switch">
	 */
	protected function inputSwitch(): string
	{
		$this->addClass( 'form-switch' );
		return $this->inputCheckbox();
	}

	/**
	 * <input type="radio"> <input type="radio"> ...
	 *
	 * Items build in:
	 * @see Input::maybeRebuild()
	 */
	protected function inputRadio(): string
	{
		// Set default attributes
		$inline = $this->get( 'inline', true );
		$this->cfg( 'inline', $inline );

		$class = explode( ' ', $this->get( 'class' ) );
		$class[] = 'form-check';
		$inline && $class[] = 'form-check-inline';
		$this->addCss( 'class', $class );

		$out = [];
		$class = $this->get( 'class' );
		/* @formatter:off */
		foreach( $this->cfg( 'items' ) as $key => $item ) {
			$out[$key]['head'] = $tag = self::escHtml( $item['tag'] );
			$out[$key]['body'] = strtr( <<<EOT
				<div{class}>
					<input class="form-check-input" type="radio" id="{id}" name="{name}" value="{value}" 
					{style}{extra}{disabled}{checked}>
					<label class="form-check-label" for="{id}">{tag}</label>
				</div>
				EOT
				, [
				'{id}'       => $item['id'],
				'{name}'     => $item['name'],
				'{value}'    => $item['value'],
				'{tag}'      => $tag,
				'{class}'    => $this->getAttr( 'class'   , $class, $item['item'] ),
				'{style}'    => $this->getAttr( 'style'   ,   null, $item['item'] ),
				'{extra}'    => $this->getAttr( 'extra'   ,   null, $item['item'] ),
				'{disabled}' => $this->getAttr( 'disabled',   null, $item['item'] ),
				'{checked}'  => $this->getChecked( $item['value'] == $this->val() ), // dont check types!
			]);
		}
		/* @formatter:on */

		return $this->buildGroupKindJoin( $out );
	}

	/**
	 * <select> <option> <option> ...
	 */
	protected function inputSelect()
	{
		/* @formatter:off */
		$out = strtr( '<select id="{id}" name="{name}"{class}{style}{extra}{disabled}>', [
			'{id}'       => $this->get( 'id' ),
			'{name}'     => $this->name(),
			'{class}'    => $this->getAttr( 'class', 'form-select' ),
			'{style}'    => $this->getAttr( 'style' ),
			'{extra}'    => $this->getAttr( 'extra' ),
			'{disabled}' => $this->getAttr( 'disabled' ),
			'{readonly}' => $this->getAttr( 'readonly' ),
		]);

		foreach ( $this->cfg( 'items' ) as $value => $tag ) {
			$out .= strtr( '<option value="{value}"{checked}>{tag}</option>', [
				'{value}'   => self::escAttr( $value ),
				'{checked}' => $this->getChecked( $this->val() == $value, 'select' ), // dont compare value types!
				'{tag}'     => self::escHtml( $tag ),
			]);
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
		], $this->cfg );
		/* @formatter:on */

		$items = range( $attr['min'], $attr['max'], $attr['step'] );

		if ( 'desc' === $attr['dir'] ) {
			$items = array_reverse( $items );
		}

		$this->cfg( 'items', array_combine( $items, $items ) );

		return $this->inputSelect();
	}

	/**
	 * <input type="date">
	 */
	protected function inputDate()
	{
		/* @formatter:off */
		return strtr( <<<EOT
			<input type="date" id="{id}" name="{name}" value="{value}"
			{min}{max}{step}{class}{disabled}
			>{tag}
			EOT, [
			'{id}'       => $this->get( 'id' ),
			'{name}'     => $this->name(),
			'{value}'    => self::escAttr( $this->val() ),
			'{min}'      => $this->getAttr( 'min' ),
			'{max}'      => $this->getAttr( 'max' ),
			'{step}'     => $this->getAttr( 'step' ),
			'{class}'    => $this->getAttr( 'class', 'form-control' ),
			'{disabled}' => $this->getAttr( 'disabled' ),
			'{readonly}' => $this->getAttr( 'readonly' ),
			'{tag}'      => self::escHtml( $this->get( 'tag' ) ),
		]);
		/* @formatter:on */
	}

	/**
	 * <input type="datetime-local">
	 */
	protected function inputDateTime()
	{
		/* @formatter:off */
		return strtr( <<<EOT
			<input type="datetime-local" id="{id}" name="{name}" value="{value}"
			{min}{max}{step}{class}{disabled}
			>{tag}
			EOT, [
				'{id}'       => $this->get( 'id' ),
				'{name}'     => $this->name(),
				'{value}'    => self::escAttr( $this->val() ),
				'{min}'      => $this->getAttr( 'min' ),
				'{max}'      => $this->getAttr( 'max' ),
				'{step}'     => $this->getAttr( 'step' ),
				'{class}'    => $this->getAttr( 'class', 'form-control' ),
				'{disabled}' => $this->getAttr( 'disabled' ),
				'{readonly}' => $this->getAttr( 'readonly' ),
				'{tag}'      => self::escHtml( $this->get( 'tag' ) ),
			]);
		/* @formatter:on */
	}

	/**
	 * <input type="number">
	 */
	protected function inputNumber(): string
	{
		/* @formatter:off */
		return strtr( <<<EOT
			<input type="number" id="{id}" name="{name}" value="{value}"
			{min}{max}{step}{class}{disabled}{readonly}
			>{tag}
			EOT, [
			'{id}'       => $this->get( 'id' ),
			'{name}'     => $this->name(),
			'{value}'    => self::escAttr( $this->val() ),
			'{min}'      => $this->getAttr( 'min' ),
			'{max}'      => $this->getAttr( 'max' ),
			'{step}'     => $this->getAttr( 'step' ),
			'{class}'    => $this->getAttr( 'class', 'form-control' ),
			'{disabled}' => $this->getAttr( 'disabled' ),
			'{readonly}' => $this->getAttr( 'readonly' ),
			'{tag}'      => self::escHtml( $this->get( 'tag' ) ),
		]);
		/* @formatter:on */
	}

	/**
	 * <input type="file" ... >
	 */
	protected function inputFile()
	{
		/* @formatter:off */
		return strtr( <<<EOT
			<input type="file" id="{id}" name="{name}"{class}{extra}{disabled}{readonly}>
			EOT, [
			'{id}'       => $this->get( 'id' ),
			'{name}'     => $this->name(),
			'{class}'    => $this->getAttr( 'class', 'form-control' ),
			'{extra}'    => $this->getAttr( 'extra' ),
			'{disabled}' => $this->getAttr( 'disabled' ),
			'{readonly}' => $this->getAttr( 'readonly' ),
		]);
		/* @formatter:on */
	}

	/**
	 * <button ...></button>
	 */
	protected function inputButton()
	{
		/* @formatter:off */
		return strtr( <<<EOT
			<button id="{id}" name="{name}"{type}{class}{extra}{disabled}>{tag}</button>
			EOT, [
			'{id}'       => $this->get( 'id' ),
			'{name}'     => $this->name(),
			'{type}'     => $this->getAttr( 'type', 'submit' ),
			'{class}'    => $this->getAttr( 'class', 'btn btn-primary' ),
			'{extra}'    => $this->getAttr( 'extra' ),
			'{disabled}' => $this->getAttr( 'disabled' ),
			'{tag}'      => self::escHtml( $this->get( 'tag' ) ),
		]);
		/* @formatter:on */
	}

	/**
	 * <html>
	 */
	protected function inputHtml()
	{
		return self::escAttr( $this->val() );
	}
}
