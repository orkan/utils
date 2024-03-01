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
	 *
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
		$this->cfg = array_merge( $this->defaults(), $cfg );

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
		$value = $this->cfg[$key] ?? '';

		if ( isset( $val ) ) {
			$this->cfg[$key] = $val;
		}

		if ( '' === $key ) {
			return $this->cfg;
		}

		return $value;
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
	public function get( string $name ): ?Input
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
		return $this->get( $name )->isChecked();
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
		if ( $this->cfg( 'echo' ) ) {
			echo $this->html;
			return true;
		}

		return false;
	}

	/**
	 * Render Inputs inside FORM table.
	 */
	public function renderTable( ?Input $Group = null ): string
	{
		$Group = $Group ?? new Input( [ 'type' => 'group' ] );
		$Group->addClass( 'form-table' );

		/* @formatter:off */
		$this->html = sprintf( '<table%1$s%2$s%3$s>',
			/*1*/ $Group->getAttr( 'id' ),
			/*2*/ $Group->getAttr( 'class' ),
			/*3*/ $Group->getAttr( 'style' ),
		);
		/* @formatter:on */

		foreach ( $this->Inputs as $Input ) {
			/* @formatter:off */
			$this->html .= sprintf( '<tr%3$s><th scope="row"><label for="%1$s">%2$s</label></th><td>%4$s</td></tr>' . PHP_EOL,
				/*1*/ $Input->get( 'for' ),
				/*2*/ $Input->get( 'title', $Input->name() ),
				/*3*/ $Input->getAttr( 'class', null, [ 'class' => $Input->get( 'class_tr' ) ] ),
				/*4*/ $Input->getContents(),
			);
			/* @formatter:on */
		}

		$this->html .= '</table>';

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
	 * All nested groups are rendered separatelly and saved in Group->attr( html, ... )
	 * Otherwise Input::getContents() will flatten all sub-group elements and render them in one level.
	 *
	 * @see Inputs::renderTableNested()
	 * @see Input::getContents()
	 */
	public function renderTableNested(): string
	{
		foreach ( $this->Inputs as $Input ) {
			if ( 'group' === $Input->type() ) {
				$Input->attr( 'html', static::buildTableNested( $Input ) );
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
		foreach ( $this->Inputs as $Input ) {
			$this->html .= '<p>' . $Input->getContents() . '</p>' . PHP_EOL;
		}

		return $this->maybeEcho() ? '' : $this->html;
	}
}
