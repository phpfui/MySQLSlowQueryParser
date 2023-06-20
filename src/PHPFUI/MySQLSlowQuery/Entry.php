<?php

namespace PHPFUI\MySQLSlowQuery;

/**
 * @property array<string> $Query
 * @property float $Lock_time
 * @property float $Query_time
 * @property int $Bytes_sent
 * @property int $Id
 * @property int $Merge_passes
 * @property int $Rows_affected
 * @property int $Rows_examined
 * @property int $Rows_sent
 * @property int $Session
 * @property int $Thread_id
 * @property int $Tmp_disk_tables
 * @property int $Tmp_table_sizes
 * @property int $Tmp_tables
 * @property string $explain
 * @property string $Filesort
 * @property string $Filesort_on_disk
 * @property string $Full_join
 * @property string $Full_scan
 * @property string $Host
 * @property string $Priority_queue
 * @property string $QC_hit
 * @property string $Schema
 * @property string $Time
 * @property string $Tmp_table
 * @property string $Tmp_table_on_disk
 * @property string $User
 */
class Entry extends \PHPFUI\MySQLSlowQuery\BaseObject
	{
	/**
	 * @var array<string, string>
	 */
	private array $parameters = [];

	/**
	 * @param array<string, string> $parameters
	 */
	public function __construct(array $parameters = [])
		{
		$this->parameters = $parameters;

		// Ignore $parameters; restrict available fields based on known log files
		// produced by specific server versions.
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
			// Session is not present in logfile. It starts at 0 and is pointed to
			// $session (not object but index in array of sessions) later.
			'Session' => 0,
		];

		if (($this->parameters['parse_mode'] ?? '') == 'mariadb')
			{
			unset($this->fields['Id']);
			$this->fields += [
				'Thread_id' => 0,
				'Schema' => '',
				'QC_hit' => '',
				'Rows_affected' => 0,
				'Bytes_sent' => 0,
				// Apparently dependent on configuration - not present in all logfiles:
				'Tmp_tables' => 0,
				'Tmp_disk_tables' => 0,
				'Tmp_table_sizes' => 0,
				'Full_scan' => '',
				'Full_join' => '',
				'Tmp_table' => '',
				'Tmp_table_on_disk' => '',
				'Filesort' => '',
				'Filesort_on_disk' => '',
				'Merge_passes' => 0,
				'Priority_queue' => '',
				// 'explain: ' is not just one property; needs special parsing.
				'explain' => '',
			];
			}
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

		if (\str_starts_with($line, '# Time') && ($this->parameters['parse_mode'] ?? '') == 'mariadb')
			{
			// A "Time" value is so for the only one with a space inside the value.
			// Unify with mysql log format: replace space with T, replace second space
			// with leading zero, expand YYMMDD value and add microseconds.
			$parts = \explode(' ', \substr($line, 8), 2);

			if (' ' === $parts[1][0])
				{
				$parts[1][0] = '0';
				}
			$line = \preg_replace('/(\d{2})(\d{2})(\d{2})/', "# Time: 20\\1-\\2-\\3T{$parts[1]}.000000Z", $parts[0]);
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
