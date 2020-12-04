<?php

namespace PHPFUI\MySQLSlowQuery;

class Parser
	{
	private const PORT = 'TCP Port: ';
	private const TIME = '# Time: ';
	private $entries = [];
	private $extraLines = [];

	private $fileName = '';
	private $handle;
	private $inSession = true;
	private $sessions = [];

	/**
	 * Parse a MySQL Slow Query Log file
	 */
	public function __construct(string $fileName)
		{
		$this->fileName = $fileName;
		}

	/**
	 * Return \PHPFUI\MySQLSlowQuery\Entry entries from file
	 *
	 * @param int $session return entries from the specified session
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
	 * Return \PHPFUI\MySQLSlowQuery\Session sessions from file
	 */
	public function getSessions() : array
		{
		if (! $this->sessions)
			{
			$this->parse();
			}

		return $this->sessions;
		}

	private function getNextLine()
		{
		if ($this->extraLines)
			{
			return array_shift($this->extraLines);
			}

		$line = fgets($this->handle);

		return $line;
		}

	private function parse() : void
		{
		$this->handle = @fopen($this->fileName, 'r');

		if (! $this->handle)
			{
			throw new Exception(__CLASS__ . ': ' . $this->fileName . ' appears to not exist or is empty');
			}

		$currentSession = [];

		while (strlen($line = $this->getNextLine()))
			{
			if (0 === strpos($line, self::PORT))	// in middle of session, end it
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
				$currentSession[] = $line;
				}
			elseif (0 === strpos($line, self::TIME))	// start of log entry
				{
				$entry = new \PHPFUI\MySQLSlowQuery\Entry();
				// parse the next three lines
				$entry->setFromLine($line);
				$entry->setFromLine(fgets($this->handle));
				$entry->setFromLine(fgets($this->handle));

				$query = [];

				while (strlen($line = $this->getNextLine()) && '#' !== $line[0])
					{
					if (0 === strpos($line, self::PORT))	// found a session
						{
						$this->pushLine($line);
						// push this and previous line back on to stack
						if (count($query))
							{
							$this->pushLine(array_pop($query));
							}
						$line = '';
						$currentSession = [];
						$this->inSession = true;

						break;
						}
					else
						{
						$query[] = trim($line);
						}
					}

				if (strlen($line) && '#' === $line[0])
					{
					$this->pushLine($line);
					}
				$entry->Query = $query;
				$entry->Session = count($this->sessions) - 1;
				$this->entries[] = $entry;
				}
			}

		fclose($this->handle);
		}

	private function pushLine(string $line) : self
		{
		array_unshift($this->extraLines, $line);

		return $this;
		}

	}
