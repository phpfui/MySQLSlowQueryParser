<?php

namespace PHPFUI\MySQLSlowQuery;

/**
 * @property string $Server
 * @property string $Version
 * @property string $Port
 * @property string $Transport
 */
class Session extends \PHPFUI\MySQLSlowQuery\BaseObject
	{
	/** @param array<int, string> $sessionData */
	public function __construct(array $sessionData = [], string $parseMode = '')
		{
		$this->fields = [
			// Almost the full first line, i.e. executable and version.
			'Server' => '',
			// The version number also found in the 'Server' line, without "Version: "
			// e.g. "8.0.21 (MySQL Community Server - GPL)"
			//      "10.7.1-MariaDB-1:10.7.1+maria~focal-log (mariadb.org binary distribution)"
			'Version' => '',
			// Not only port number; includes "TCP port: "
			'Port' => '',
			// e.g. "Named pipe: ..." (mysql) or "Unix socket: ..." (mariadb)
			'Transport' => '',
		];

		$this->Server = \trim(\str_replace('. started with:', '', $sessionData[0] ?? 'unknown'));
		$this->Version = \substr(\strstr($this->Server, 'Version: ') ?: '', 9);

		$delimiter = 'mariadb' == $parseMode ? '  ' : ', ';

		if (\strpos($sessionData[1] ?? '', $delimiter))
			{
			[$this->Port, $this->Transport] = \explode($delimiter, \trim($sessionData[1]));
			}
		}
	}
