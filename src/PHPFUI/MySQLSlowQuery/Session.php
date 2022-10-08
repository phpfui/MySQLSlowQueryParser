<?php

namespace PHPFUI\MySQLSlowQuery;

class Session extends \PHPFUI\MySQLSlowQuery\BaseObject
	{
	/** @param array<int, string> $sessionData */
	public function __construct(array $sessionData = [])
		{
		$this->fields = [
			'Server' => '',
			'Port' => '',
			'Transport' => '',
		];

		// @phpstan-ignore-next-line
		$this->Server = \trim(\str_replace('. started with:', '', $sessionData[0] ?? 'unknown'));

		if (\strpos($sessionData[1] ?? '', ', '))
			{
			// @phpstan-ignore-next-line
			[$this->Port, $this->Transport] = \explode(', ', \trim($sessionData[1]));
			}
		}
	}
