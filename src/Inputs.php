<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Render FORM Inputs.
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class Inputs
{
	/**
	 * @var Input[]
	 */
	protected $Inputs = [];

	/**
	 * Internal config.
	 * @var array
	 */
	protected $cfg;

	/**
	 * Html body.
	 */
	protected $html = '';

	/**
	 * Collect Inputs for presentation.
	 *
	 * @param Input[] $inputs Inputs collection
	 */
	public function __construct( array $inputs = [], array $cfg = [] )
	{
		$this->cfg = array_merge( self::defaults(), $cfg );

		foreach ( $inputs as $Input ) {
			$this->add( $Input );
		}
	}

	protected function defaults(): array
	{
		/* @formatter:off */
		return [
			'echo' => true,
		];
		/* @formatter:on */
	}

	/**
	 * Set/Get config value
	 */
	public function cfg( string $key = '', $val = null )
	{
		$last = $this->cfg[$key] ?? null;

		if ( isset( $val ) ) {
			$this->cfg[$key] = $val;
		}

		if ( '' === $key ) {
			return $this->cfg;
		}

		return $last;
	}

	/**
	 * Get attribute or fallback to default.
	 */
	public function get( string $key = '', $default = '' )
	{
		return $this->cfg( $key ) ?? $default;
	}

	/**
	 * Add Input to collection.
	 */
	public function add( Input $Input )
	{
		$this->Inputs[$Input->name()] = $Input;
	}

	/**
	 * Find Input in collection.
	 */
	public function find( string $name ): ?Input
	{
		return Input::inputsFind( $name, $this->Inputs );
	}

	/**
	 * Get Inputs collection.
	 */
	public function inputs(): array
	{
		return $this->Inputs;
	}

	/**
	 * Get all Inputs::elements().
	 */
	public function elements( bool $withSelf = false ): array
	{
		$elements = [];
		foreach ( $this->Inputs as $Input ) {
			$elements = array_merge( $elements, $Input->elements( $withSelf ) );
		}

		return $elements;
	}

	/**
	 * Check whether the Input type:checkbox is ON.
	 */
	public function isChecked( string $name ): bool
	{
		return $this->find( $name )->isChecked();
	}

	/**
	 * Render all Inputs.
	 */
	public function render( string $format = 'table', ?array $inputs = null ): void
	{
		if ( method_exists( $this, $method = 'render' . ucfirst( $format ) ) ) {
			$this->$method( $inputs );
		}
	}

	/**
	 * Manage STDOUT.
	 */
	protected function maybeEcho(): string
	{
		if ( $this->get( 'echo' ) ) {
			echo $this->html;
			return true;
		}

		return false;
	}

	/**
	 * Render Inputs inside FORM table.
	 *
	 * @param Input $Parent Table attributes
	 */
	public function renderTable( ?Input $Parent = null ): string
	{
		if ( !$Parent ) {
			$parent = $this->get( 'table', [] );
			$parent['type'] = 'group';
			$Parent = new Input( $parent );
		}

		$fields = $this->get( 'fields', [] );
		$rows = [];

		foreach ( $this->Inputs as $Input ) {
			// Debug
			if ( $config = $fields[$Input->name()] ?? '') {
				$config = var_export( $config, true );
				$config = nl2br( Input::escHtml( $config ) );
				$config = sprintf( '<code class="config">%s</code>', $config );
			}

			/* @formatter:off */
			$rows[] = strtr( '<tr{class}><th scope="row"><label{for}>{title}</label></th><td>{body}{config}</td></tr>', [
				'{for}'    => $Input->getAttr( 'for' ),
				'{class}'  => $Input->getAttr( 'class', null, [ 'class' => $Input->get( 'class_tr' ) ] ),
				'{title}'  => $Input->get( 'title', $Input->name() ),
				'{body}'   => $Input->getContents(),
				'{config}' => $config,
			]);
			/* @formatter:on */
		}

		$thead = <<<EOT
		<thead>
			<tr>
				<th scope="col">{th1_title}</th>
				<th scope="col">{th2_title}</th>
			</tr>
		</thead>
		EOT;
		$thead = $Parent->get( 'th1_title' ) || $Parent->get( 'th2_title' ) ? $thead : '';

		/* @formatter:off */
		$this->html = strtr( <<<EOT
			<table{id}{class}{style}>
				$thead
				<tbody class="{tbody}">
					{rows}
				</tbody>
			</table>
			EOT, [
			'{id}'        => $Parent->getAttr( 'id' ),
			'{class}'     => $Parent->getAttr( 'class', 'table' ),
			'{style}'     => $Parent->getAttr( 'style' ),
			'{tbody}'     => $Parent->get( 'tbody', 'table-group-divider' ),
			'{th1_title}' => $Parent->get( 'th1_title' ),
			'{th2_title}' => $Parent->get( 'th2_title' ),
			'{rows}'      => implode( "\n\t", $rows ),
		]);
		/* @formatter:on */

		return $this->maybeEcho() ? '' : $this->html;
	}

	/**
	 * Help render nested Input elements inside FORM table.
	 */
	public static function buildTableNested( Input $Input ): string
	{
		$Table = new static( $Input->elements( false ), [ 'echo' => false ] );
		return $Table->renderTable( $Input );
	}

	/**
	 * Render nested Inputs inside FORM table.
	 *
	 * All nested groups are rendered separatelly and saved in Group->cfg( html, ... )
	 * Otherwise Input::getContents() will flatten all sub-group elements and render them in one level.
	 *
	 * @see Inputs::renderTableNested()
	 * @see Input::getContents()
	 */
	public function renderTableNested(): string
	{
		foreach ( $this->Inputs as $Input ) {
			if ( 'group' === $Input->type() ) {
				$Input->cfg( 'html', static::buildTableNested( $Input ) );
			}
		}

		return $this->renderTable();
	}

	/**
	 * Render Inputs inside paragraph.
	 */
	public function renderParagraphs(): string
	{
		$this->html = '';
		$element = $this->get( 'element', 'p' );

		foreach ( $this->Inputs as $Input ) {
			$this->html .= "<{$element}>" . $Input->getContents() . "</$element>";
		}

		return $this->maybeEcho() ? '' : $this->html;
	}
}
