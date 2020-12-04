# PHPFUI\MySQLSlowLog\Parser [![Latest Packagist release](https://img.shields.io/packagist/v/phpfui/mysql-slow-log-parser.svg)](https://packagist.org/packages/phpfui/mysql-slow-log-parser)

PHP Parser for MySQL Slow Query Logs

## Requirements
PHP 7.1 or higher
MySQL 5.7 or higher

## Usage
~~~php
$parser = new \PHPFUI\MySQLSlowQuery\Parser($logFilePath);
// Return the sessions in the file
$sessions = $parser->getSessions();
// Return all entries in file, or pass session number (0 based)
$entries = $parser->getEntries();
~~~

**\PHPFUI\MySQLSlowQuery\Session** provides server information (Server, Port, Transport). Sessions are created on MySQL server restarts and log flushes.

**\PHPFUI\MySQLSlowQuery\Entry** provides details on each query.  Supported fields:
 * Time
 * User
 * Host
 * Id
 * Query_time
 * Lock_time
 * Rows_sent
 * Rows_examined
 * Query (array)
 * Session

## Documentation
Via [PHPFUI/InstaDoc](http://phpfui.com/?n=PHPFUI%5CMySQLSlowLog)

## License
Distributed under the MIT License.
