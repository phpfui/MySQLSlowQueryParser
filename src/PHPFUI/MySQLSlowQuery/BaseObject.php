<?php

namespace PHPFUI\MySQLSlowQuery;

abstract class BaseObject
	{
	/** @var array<string, mixed> $fields */
	protected array $fields = [];

	/** @param array<string, string> $parameters $parameters */
	abstract public function __construct(array $parameters = []);

	/**
	 * Allows for $object->field syntax
	 *
	 * @return string | array<int, mixed>
	 */
	public function __get(string $field)
		{
		if (! \array_key_exists($field, $this->fields))
			{
			throw new Exception\Get("{$field} is not a valid field for " . static::class);
			}

		return $this->fields[$field];
		}

	/**
	 * Allows for $object->field = $x syntax
	 *
	 * @param mixed $value value to set
	 *
	 * @return mixed returns $value so you can string together assignments
	 */
	public function __set(string $field, $value)
		{
		if (! \array_key_exists($field, $this->fields))
			{
			throw new Exception\Set("{$field} is not a valid field for " . static::class);
			}

		$this->fields[$field] = $value;

		return $value;
		}

	/** @return array<string, mixed>  */
	public function asArray() : array
		{
		return $this->fields;
		}
	}
