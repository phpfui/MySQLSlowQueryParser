<?php

namespace PHPFUI\MySQLSlowQuery;

class Parser
	{
	private const PORT = 'TCP Port: ';

	private const TIME = '# Time: ';

	/** @var array<int, \PHPFUI\MySQLSlowQuery\Entry> */
	private array $entries = [];

	/** @var array<int, string> */
	private array $extraLines = [];

	// @phpstan-ignore-next-line
	private $handle;

	/** @var array<int, \PHPFUI\MySQLSlowQuery\Session> */
	private array $sessions = [];

	private string $sortColumn = 'Query_time';

	private string $sortOrder = 'desc';

	/**
	 * Parse a MySQL Slow Query Log file
	 */
	public function __construct(private string $fileName)
		{
		}

	/**
	 * Return \PHPFUI\MySQLSlowQuery\Entry entries from file
	 *
	 * @param int $session return entries from the specified session
	 *
	 * @throws Exception\EmptyLog
	 * @throws Exception\LogLine
	 * @return array<int, \PHPFUI\MySQLSlowQuery\Entry>
	 *
	 */
	public function getEntries(int $session = -1) : array
		{
		if (! $this->entries)
			{
			$this->parse();
			}

		if ($session > -1)
			{
			$entries = [];

			foreach ($this->entries as $entry)
				{
				if ($entry->Session == $session)
					{
					$entries[] = $entry;
					}
				}

			return $entries;
			}

		return $this->entries;
		}

	/**
	 * @throws Exception\EmptyLog
	 * @throws Exception\LogLine
	 * @return array<int, \PHPFUI\MySQLSlowQuery\Session> sessions from file
	 *
	 */
	public function getSessions() : array
		{
		if (! $this->sessions)
			{
			$this->parse();
			}

		return $this->sessions;
		}

	/**
	 * Sort \PHPFUI\MySQLSlowQuery\Entry entries.  Defaults to Query_time, desc
	 *
	 * @throws Exception\EmptyLog
	 * @throws Exception\LogLine
	 */
	public function sortEntries(string $sortColumn = 'Query_time', string $sortOrder = 'desc') : self
		{
		$this->sortColumn = $sortColumn;
		$this->sortOrder = $sortOrder;

		if (! $this->entries)
			{
			$this->parse();
			}

		\usort($this->entries, [$this, 'entrySort']);

		return $this;
		}

	protected function entrySort(\PHPFUI\MySQLSlowQuery\Entry $lhs, \PHPFUI\MySQLSlowQuery\Entry $rhs) : int
		{
		$column = $this->sortColumn;

		if ('desc' == $this->sortOrder)
			{
			return $rhs->{$column} <=> $lhs->{$column};
			}

		return $lhs->{$column} <=> $rhs->{$column};
		}

	private function getNextLine() : string
		{
		if ($this->extraLines)
			{
			return \array_shift($this->extraLines);
			}

		$line = \fgets($this->handle);

		return $line;
		}

	/**
	 * Derive a string value that determines how the log is parsed.
	 */
	private function getParseMode(string $sessionHeaderFirstLine) : string
		{
		return \stripos($sessionHeaderFirstLine, 'MariaDB') ? 'mariadb' : '';
		}

	private function parse() : void
		{
		$this->handle = @\fopen($this->fileName, 'r');

		if (! $this->handle)
			{
			throw new Exception\EmptyLog(self::class . ': ' . $this->fileName . ' appears to not exist or is empty');
			}

		$newSessionHeader = [];
		$parseMode = '';
		$previousTimeLine = '';
		$processingSessionHeader = true;

		// Get line from stack (if pushed in below loop) or file. Any comment line
		// not starting with "# Time: " gets discarded.
		while (\strlen($line = $this->getNextLine()))
			{
			if (0 === \stripos($line, self::PORT))
				{
				$newSessionHeader[] = $line;
				$parseMode = $this->getParseMode($newSessionHeader[0]);
				// eat the next line
				$this->getNextLine();
				// create a new session
				$this->sessions[] = new \PHPFUI\MySQLSlowQuery\Session($newSessionHeader, $parseMode);
				$newSessionHeader = [];
				$processingSessionHeader = false;

				// The next line is expected to be a comment connected to the first
				// query in this session. In non backward compatible mode, check this:
				// if it's not a comment line, this is assumed to be the start of the
				// next session header (i.e. there are zero queries in this session).
				if ('mariadb' === $parseMode && \strlen($line = $this->getNextLine()) > 0)
					{
					if ('#' !== $line[0])
						{
						$processingSessionHeader = true;
						}
					$this->pushLine($line);
					}
				}
			elseif ($processingSessionHeader) // not in session yet
				{
				// store lines until "TCP Port: " is found in a next line
				$newSessionHeader[] = $line;
				}
			elseif ('#' === $line[0])	// start of log entry
				{
				$entry = new \PHPFUI\MySQLSlowQuery\Entry(['parse_mode' => $parseMode]);
				$query = [];

				if ('' === $parseMode)
					{
					// Backward compatible parsing:
					// - Ignore comment lines up until "# Time:"
					// - Iarse exactly three lines. If any of these are non-comments,
					//   throw an exception.
					// - If there are more than three comment lines, the query below
					//   them is ignored.
					if (! \str_starts_with($line, self::TIME))
						{
						continue;
						}
					$entry->setFromLine($line);
					$entry->setFromLine(\fgets($this->handle));
					$entry->setFromLine(\fgets($this->handle));
					}
				else
					{
					$timeLineFound = false;

					// Parse any following comment lines, and interpret the next
					// non-comment line as a query line.
					do
						{
						$entry->setFromLine($line);

						if ('mariadb' == $parseMode && \str_starts_with($line, self::TIME))
							{
							$timeLineFound = true;
							$previousTimeLine = $line;
							}
						}
					while (\strlen($line = $this->getNextLine()) > 0 && '#' === $line[0]); // @phpstan-ignore-line

					if ('mariadb' == $parseMode && ! $timeLineFound && $previousTimeLine)
						{
						// Always add the Time property. Assume that if it is not in the
						// log, it's the same as the previous logged query, and that the
						// line contains no other properties we don't want to add.
						$entry->setFromLine($previousTimeLine);
						}
					$query[] = \trim($line);
					}

				// gather (more) query lines until a non-query line is reached
				while (\strlen($line = $this->getNextLine()) > 0 && '#' !== $line[0])
					{
					if (0 === \stripos($line, self::PORT))	// found a new session header
						{
						$this->pushLine($line);

						// Push this and previous line back on to stack. (Implicitly assume
						// "TCP Port: " is on the second line of the new session header.)
						if (\count($query))
							{
							$this->pushLine(\array_pop($query));
							}
						$line = '';
						$newSessionHeader = [];
						$processingSessionHeader = true;

						break;
						}
					$query[] = \trim($line);
					}

				// push unprocessed comment line (for next query) back on to stack
				if (\strlen($line) && '#' === $line[0])
					{
					$this->pushLine($line);
					}
				$entry->Query = $query;
				$entry->Session = \count($this->sessions) - 1;
				$this->entries[] = $entry;
				}
			}

		\fclose($this->handle);
		}

	/**
	 * Push line back on to stack for further processing.
	 *
	 * Lines will later be processed by getNextLine() in the reverse order as
	 * they are pushed.
	 */
	private function pushLine(string $line) : self
		{
		\array_unshift($this->extraLines, $line);

		return $this;
		}
	}
