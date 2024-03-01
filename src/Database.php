<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2024 Orkan <orkans+utils@gmail.com>
 */
namespace Orkan;

/**
 * Abstraction layer for PDO (PHP Data Objects) interface.
 *
 * @author Orkan <orkans+filmweb@gmail.com>
 */
class Database
{
	/**
	 * PHP Data Objects interface.
	 * @var \PDO
	 */
	protected $PDO;

	/**
	 * Last query.
	 * @var \PDOStatement
	 */
	protected $Query;

	/**
	 * Create PHP::PDO() instance.
	 *
	 * @param string $dsn DB file in DSN SQLite connection syntax.
	 */
	public function __construct( string $dsn )
	{
		try {
			$this->PDO = new \PDO( $dsn );
		}
		catch ( \PDOException $E ) {
			throw new \RuntimeException(
				/**/ sprintf( 'DB connection to "%s" failed with error: %s.', $dsn, $E->getMessage() ),
				/**/ $E->getCode(),
				/**/ $E );
		}
	}

	/**
	 * @return \PDO
	 */
	public function PDO()
	{
		return $this->PDO;
	}

	/**
	 * @return \PDOStatement
	 */
	public function PDOStatement()
	{
		return $this->Query;
	}

	/**
	 * Generate BIND array for PDO::execute()
	 *
	 * @param  array $data Array ( [name] => value, ... )
	 * @return array       Array ( [:name] => value, ... )
	 */
	public static function bind( array $data ): array
	{
		$bind = [];
		foreach ( $data as $k => $v ) {
			$bind[":$k"] = $v;
		}

		return $bind;
	}

	/**
	 * Get database error message.
	 */
	public function error(): string
	{
		$err = $this->PDO->errorInfo();
		$msg = $err[2] ? sprintf( 'DB error: %s', $err[2] ) : '';

		if ( $this->Query ) {
			$err = $this->Query->errorInfo();
			$msg .= $err[2] ? sprintf( 'DB failed on query: "%s"; %s', $this->Query->queryString, $err[2] ) : '';
		}

		return $msg;
	}

	/**
	 * Check last action errors.
	 * @throws \RuntimeException on DB error
	 */
	public function checkError()
	{
		if ( $err = $this->error() ) {
			throw new \RuntimeException( $err );
		}
	}

	/**
	 * Execute statement and create PDOStatement object.
	 *
	 * @return \PDOStatement
	 */
	public function query( string $statement )
	{
		$this->Query = $this->PDO->query( $statement );
		$this->checkError();

		return $this->Query;
	}

	/**
	 * Parse statement and create PDOStatement object.
	 */
	public function prepare( string $statement )
	{
		$this->Query = $this->PDO->prepare( $statement );
		$this->checkError();

		return $this->Query;
	}

	/**
	 * Assisgn values and execute last Query.
	 */
	public function execute( array $bind )
	{
		$result = false;

		if ( $this->Query ) {
			$result = $this->Query->execute( $bind );
			$this->checkError();
		}

		return $result;
	}

	/**
	 * Get whole column from last Query results.
	 *
	 * @see \PDOStatement::fetchAll()    <-- returns values from all rows
	 * @see \PDOStatement::fetchColumn() <-- returns value from single row
	 *
	 * @return bool|array (
	 *   [0] => value 1,
	 *   [1] => value 2,
	 * )
	 */
	public function fetchColumn( int $column = 0 )
	{
		$result = false;

		if ( $this->Query ) {
			$result = $this->Query->fetchAll( \PDO::FETCH_COLUMN, $column );
			$this->checkError();
		}

		return $result;
	}

	/**
	 * Get all rows from last Query results.
	 *
	 * @see \PDO::FETCH_ASSOC Array(
	 *   Array( [name] => name, [value] => value ),
	 *   Array( [name] => name, [value] => value ),
	 * )
	 * @see \PDO::FETCH_KEY_PAIR Array(
	 *   [name] => value,
	 *   [name] => value,
	 * )
	 *
	 * @return bool|array (
	 *   Array( [name] => name, [value] => value ),
	 *   Array( [name] => name, [value] => value ),
	 * )
	 */
	public function fetchAll()
	{
		$result = false;

		if ( $this->Query ) {
			$result = $this->Query->fetchAll( \PDO::FETCH_ASSOC );
			$this->checkError();
		}

		return $result;
	}

	/**
	 * Get row from last Query results.
	 *
	 * @return bool|array ( [name] => name, [value] => value )
	 */
	public function fetch()
	{
		$result = false;

		if ( $this->Query ) {
			$result = $this->Query->fetch( \PDO::FETCH_ASSOC );
			$this->checkError();
		}

		return $result;
	}

	/**
	 * Get last query string.
	 */
	public function lastQuery(): string
	{
		return $this->Query ? $this->Query->queryString : '';
	}

	/**
	 * Get last affected rows count.
	 */
	public function lastCount(): int
	{
		return $this->Query ? $this->Query->rowCount() : 0;
	}

	/**
	 * Get last query debug string.
	 */
	public function lastInfo( ?string $query = null, int $count = 0 ): string
	{
		if ( null === $query ) {
			$query = $this->lastQuery();
			$count = $this->lastCount();
		}

		return $query ? sprintf( '%s; Rows affected: %s', $query, $count ) : '';
	}
}
