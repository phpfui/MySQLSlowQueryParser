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

	private bool $inSession = true;

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

	private function parse() : void
		{
		$this->handle = @\fopen($this->fileName, 'r');

		if (! $this->handle)
			{
			throw new Exception\EmptyLog(self::class . ': ' . $this->fileName . ' appears to not exist or is empty');
			}

		$currentSession = [];

		// Get line from stack (if pushed in below loop) or file. Any comment line
		// not starting with "# Time: " gets discarded.
		while (\strlen($line = $this->getNextLine()))
			{
			if (0 === \stripos($line, self::PORT))	// in middle of session, end it
				{
				$currentSession[] = $line;
				// eat the next line
				$this->getNextLine();
				// create a new session
				$this->sessions[] = new \PHPFUI\MySQLSlowQuery\Session($currentSession);
				$currentSession = [];
				$this->inSession = false;
				}
			elseif ($this->inSession)	// in session, grab line
				{
				// store lines until "TCP Port: " is found in a next line
				$currentSession[] = $line;
				}
			elseif (\str_starts_with($line, self::TIME))	// start of log entry
				{
				$entry = new \PHPFUI\MySQLSlowQuery\Entry();
				// parse the next three lines
				$entry->setFromLine($line);
				$entry->setFromLine(\fgets($this->handle));
				$entry->setFromLine(\fgets($this->handle));

				$query = [];

				// gather query lines until a non-query line is reached
				// @phpstan-ignore-next-line
				while (\strlen($line = $this->getNextLine()) > 0 && '#' !== $line[0])
					{
					if (0 === \stripos($line, self::PORT))	// found a session
						{
						$this->pushLine($line);
						// Push this and previous line back on to stack. (Implicitly assume
						// "TCP Port: " is on the second line of the new session header.)
						if (\count($query))
							{
							$this->pushLine(\array_pop($query));
							}
						$line = '';
						$currentSession = [];
						$this->inSession = true;

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
