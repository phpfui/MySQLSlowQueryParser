# PHPFUI\MySQLSlowLog\Parser [![Tests](https://github.com/phpfui/MySQLSlowQueryParser/actions/workflows/tests.yml/badge.svg)](https://github.com/phpfui/MySQLSlowQueryParser/actions?query=workflow%3Atests) [![Latest Packagist release](https://img.shields.io/packagist/v/phpfui/mysql-slow-log-parser.svg)](https://packagist.org/packages/phpfui/mysql-slow-log-parser) ![](https://img.shields.io/badge/PHPStan-level%206-brightgreen.svg?style=flat)

PHP Parser for MySQL Slow Query Logs featuring sortable results

## Requirements
 * Modern PHP version
 * MySQL 5.7 or higher

## Usage
~~~php
$parser = new \PHPFUI\MySQLSlowQuery\Parser($logFilePath);

// Return the sessions in the file as array
$sessions = $parser->getSessions();

// Return all entries in file as array, or pass session number (0 based)
$entries = $parser->getEntries();

if (count($entries))
  {
  // Get the worst offender
  $entry = $parser->sortEntries()->getEntries()[0];
  echo 'Query ' . implode(' ', $entry->Query) . " took {$entry->Query_time} seconds at {$entry->Time}\n";

  // Get the most rows examined
  $entry = $parser->sortEntries('Rows_examined', 'desc')->getEntries()[0];
  echo 'Query ' . implode(' ', $entry->Query) . " looked at {$entry->Rows_examined} rows\n";
  }
~~~

## Entries
**\PHPFUI\MySQLSlowQuery\Entry** provides details on each query.
Supported fields:
 * Time
 * User
 * Host
 * Id
 * Query_time
 * Lock_time
 * Rows_sent
 * Rows_examined
 * Query (array)
 * Session (zero based)

## Sessions
**\PHPFUI\MySQLSlowQuery\Session** contains MySQL server information and are created on server restarts and log flushes. Pass the zero based session number to getEntries for only that Session's entries.
Supported fields:
 * Server
 * Port
 * Transport

## Sort Entries
By default, entries are returned in log order, but call sortEntries on the Parser to sort by any valid field (parameter 1). Sort defaults to 'desc', anything else will sort ascending.

## Full Class Documentation
[PHPFUI/InstaDoc](http://phpfui.com/?n=PHPFUI%5CMySQLSlowQuery)

## License
Distributed under the MIT License.
