<?php

namespace PHPFUI\MySQLSlowQuery;

class Session extends \PHPFUI\MySQLSlowQuery\BaseObject
	{
	public function __construct(array $sessionData = [])
		{
		$this->fields = [
			'Server' => '',
			'Port' => '',
			'Transport' => '',
		];

		$this->Server = \trim(\str_replace('. started with:', '', $sessionData[0] ?? 'unknown'));

		if (\strpos($sessionData[1] ?? '', ', '))
			{
			[$this->Port, $this->Transport] = \explode(', ', \trim($sessionData[1]));
			}
		}
	}
