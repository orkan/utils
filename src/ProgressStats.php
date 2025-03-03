<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2025 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Progress Bar statistics.
 *
 * @property int   $step          Current step
 * @property int   $steps         Total steps
 * @property int   $centDone      Progress (%)
 * @property int   $centLeft      Remaining (%)
 * @property int   $timeDone      Elapsed (sec)
 * @property int   $timeLeft      Remaining (sec)
 * @property int   $timeCent      Avg sec per cent
 * @property int   $sumCentDone   12%
 * @property int   $sumCentLeft   -88%
 * @property int   $sumTimeDone   12s
 * @property int   $sumTimeLeft   -1m 34s
 *
 * @author Orkan <orkans+utils@gmail.com>
 */
class ProgressStats extends Dataset
{
	protected $start;

	/*
	 * Services:
	 */
	protected $Factory;
	protected $Utils;

	/**
	 * Setup.
	 */
	public function __construct( Factory $Factory, int $steps )
	{
		$this->Factory = $Factory;
		$this->Utils = $Factory->Utils();

		$this->start = $this->Utils->exectime();

		/* @formatter:off */
		$this->data = [
			'step'     => 0,
			'steps'    => $steps,
			'centDone' => 0,
			'centLeft' => 100,
			'timeDone' => 0,
			'timeLeft' => 0,
			'sumCentDone' => '0%',
			'sumCentLeft' => '-100%',
			'sumTimeDone' => '0s',
			'sumTimeLeft' => '--',
		];
		/* @formatter:on */

		$this->dirty = true;
	}

	/**
	 * {@inheritDoc}
	 * @see \Orkan\Dataset::rebuild()
	 */
	protected function rebuild(): void
	{
		if ( !$this->data['step'] ) {
			return;
		}

		$this->data['centDone'] = $this->Utils->matchCent( $this->data['step'], $this->data['steps'] );
		$this->data['centLeft'] = 100 - $this->data['centDone'];
		$this->data['timeDone'] = $this->Utils->exectime( $this->start );
		$this->data['timeCent'] = $this->data['timeDone'] / $this->data['centDone'];
		$this->data['timeLeft'] = $this->data['timeCent'] * $this->data['centLeft'];

		if ( $this->data['centDone'] > 1 ) {
			$this->data['sumCentDone'] = $this->data['centDone'] . '%';
			$this->data['sumCentLeft'] = '-' . $this->data['centLeft'] . '%';
		}

		if ( $this->data['timeDone'] > 1 ) {
			$this->data['sumTimeDone'] = $this->Utils->timeString( $this->data['timeDone'], 0 );
			$this->data['sumTimeLeft'] = '-' . $this->Utils->timeString( $this->data['timeLeft'], 0 );
		}
	}

	public function setStep( int $value )
	{
	}

	public function setSteps( int $value )
	{
	}
}
