<?php

namespace PHPFUI\MySQLSlowQuery;

abstract class BaseObject
	{

	protected $fields = [];

	abstract public function __construct(array $paramters = []);

	/**
	 * Allows for $object->field syntax
	 */
	public function __get(string $field)
		{
		if (! array_key_exists($field, $this->fields))
			{
			throw new GetException("{$field} is not a valid field for " . get_class($this));
			}

		return $this->fields[$field];
		}

	/**
	 * Allows for $object->field = $x syntax
	 *
	 * @return mixed returns $value so you can string together assignments
	 */
	public function __set(string $field, $value)
		{
		if (! array_key_exists($field, $this->fields))
			{
			throw new SetException("{$field} is not a valid field for " . get_class($this));
			}

		$this->fields[$field] = $value;

		return $value;
		}

	public function asArray() : array
		{
		return $this->fields;
		}

	}
