<?php

namespace PHPFUI\MySQLSlowQuery;

class Entry extends \PHPFUI\MySQLSlowQuery\BaseObject
	{
	public function __construct(array $parameters = [])
		{
		$this->fields = [
			'Time' => '',
			'User' => '',
			'Host' => '',
			'Id' => 0,
			'Query_time' => 0.0,
			'Lock_time' => 0.0,
			'Rows_sent' => 0,
			'Rows_examined' => 0,
			'Query' => [],
			'Session' => 0,
		];
		}

	/**
	 * Parse a line from the log file into fields (label before :)
	 * Additional fields can easily be added if they follow the same format.
	 * Just add an entry to the fields table a above to support a new field.
	 *
	 * @throws Exception\LogLine
	 */
	public function setFromLine(string $line) : self
		{
		if (\strpos($line, '# '))
			{
			throw new Exception\LogLine('Not a valid Slow log line: ' . $line);
			}

		// parse the following lines:
		//
		// # Time: 2020-12-02T19:08:43.462468Z
		// # User@Host: root[root] @ localhost [::1]  Id:     8
		// # Query_time: 0.001519  Lock_time: 0.000214 Rows_sent: 0  Rows_examined: 0

		$line = \trim($line);
		// special handling for # User@Host: root[root] @ localhost [::1]  Id:     8
		if (\strpos($line, 'User@Host:'))
			{
			$line = \str_replace('# User@Host', '# User', $line);
			$line = \str_replace('@', 'Host:', $line);
			$line = \str_replace(' [', '[', $line);
			}

		$parts = \explode(' ', \substr($line, 2));

		while (\count($parts))
			{
			$field = \trim(\str_replace(':', '', \array_shift($parts)));

			if (isset($this->fields[$field]))
				{
				do
					{
					$value = \trim(\array_shift($parts));
					}
				while ('' === $value);
				$this->fields[$field] = $value;
				}
			}

		return $this;
		}
	}
