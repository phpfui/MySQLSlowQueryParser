<?php

namespace PHPFUI\Tests;

/**
 * This file is part of the PHPFUI package
 *
 * (c) Bruce Wells
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source
 * code
 */
class UnitTest extends \PHPUnit\Framework\TestCase
	{

	public function testBadFile() : void
		{
		$parser = new \PHPFUI\MySQLSlowQuery\Parser(__DIR__ . '/logs/mysql.log');
		$this->assertCount(0, $parser->getSessions());
		$this->assertCount(0, $parser->getEntries());
		}

	public function testDoubleSession() : void
		{
		$parser = new \PHPFUI\MySQLSlowQuery\Parser(__DIR__ . '/logs/doubleSession.log');
		$sessions = $parser->getSessions();
		$this->assertCount(2, $sessions);
		$session = $sessions[1];
		$this->assertEquals('c:\wamp64\bin\mysql\mysql8.0.21\bin\mysqld.exe, Version: 8.0.21 (MySQL Community Server - GPL)', $session->Server);
		$this->assertEquals('TCP Port: 3388', $session->Port);
		$this->assertEquals('Named Pipe: /tmp/mysql.sock', $session->Transport);
		}

	public function testInvalidGet() : void
		{
		$this->expectException(\PHPFUI\MySQLSlowQuery\Exception\Get::class);
		$entry = new \PHPFUI\MySQLSlowQuery\Entry();
		$entry->fred;
		}

	public function testInvalidSet() : void
		{
		$this->expectException(\PHPFUI\MySQLSlowQuery\Exception\Set::class);
		$entry = new \PHPFUI\MySQLSlowQuery\Entry();
		$entry->fred = 'Ethyl';
		}

	public function testMissingFile() : void
		{
		$this->expectException(\PHPFUI\MySQLSlowQuery\Exception\EmptyLog::class);
		$parser = new \PHPFUI\MySQLSlowQuery\Parser(__DIR__ . '/logs/missing.log');
		$parser->getSessions();
		}

	public function testSingleSession() : void
		{
		$parser = new \PHPFUI\MySQLSlowQuery\Parser(__DIR__ . '/logs/singleSession.log');
		$sessions = $parser->getSessions();
		$this->assertCount(1, $sessions);
		$session = $sessions[0];
		$this->assertEquals('c:\wamp64\bin\mysql\mysql8.0.21\bin\mysqld.exe, Version: 8.0.21 (MySQL Community Server - GPL)', $session->Server);
		$this->assertEquals('TCP Port: 3306', $session->Port);
		$this->assertEquals('Named Pipe: /tmp/mysql.sock', $session->Transport);

		$entries = $parser->getEntries();
		$this->assertCount(61, $entries);
		$entry = $entries[4];
		$this->assertEquals('2020-12-02T20:05:04.602866Z', $entry->Time);
		$this->assertEquals('root[root]', $entry->User);
		$this->assertEquals('localhost[::1]', $entry->Host);
		$this->assertEquals('17', $entry->Id);
		$this->assertEquals('0.003723', $entry->Query_time);
		$this->assertEquals('0.000374', $entry->Lock_time);
		$this->assertEquals('1', $entry->Rows_sent);
		$this->assertEquals('414', $entry->Rows_examined);
		$this->assertCount(2, $entry->Query);

		$this->assertEquals('SET timestamp=1606939504;', $entry->Query[0]);
		$this->assertEquals("SELECT * FROM Tercero WHERE `tipoTercero` LIKE '%*17*%';", $entry->Query[1]);
		}

	public function testSortedEntries() : void
		{
		$parser = new \PHPFUI\MySQLSlowQuery\Parser(__DIR__ . '/logs/singleSession.log');
		$entries = $parser->sortEntries()->getEntries();
		$this->assertCount(61, $entries);
		$entry = $entries[0];
		$this->assertEquals('2020-12-02T20:05:00.650946Z', $entry->Time);
		$this->assertEquals('14', $entry->Id);
		$this->assertEquals('0.062181', $entry->Query_time);
		$this->assertEquals('0.001285', $entry->Lock_time);
		$this->assertEquals('1', $entry->Rows_sent);
		$this->assertEquals('414', $entry->Rows_examined);
		$this->assertCount(2, $entry->Query);
		}

	public function testTripleSession() : void
		{
		$parser = new \PHPFUI\MySQLSlowQuery\Parser(__DIR__ . '/logs/tripleSession.log');
		$sessions = $parser->getSessions();
		$this->assertCount(3, $sessions);

		for ($i = 0; $i < 3; ++$i)
			{
			$session = $sessions[$i];
			$port = ($i + 1) * 1111;
			$this->assertEquals("TCP Port: {$port}", $session->Port);
			}
		$entries = $parser->getEntries(2);
		$this->assertCount(1, $entries);
		$entry = $entries[0];
		$this->assertEquals('2020-12-03T17:58:07.934615Z', $entry->Time);
		$this->assertEquals('root[root]', $entry->User);
		$this->assertEquals('localhost[::1]', $entry->Host);
		$this->assertEquals('478', $entry->Id);
		$this->assertEquals('0.000582', $entry->Query_time);
		$this->assertEquals('0.000375', $entry->Lock_time);
		$this->assertEquals('0', $entry->Rows_sent);
		$this->assertEquals('6', $entry->Rows_examined);
		}

	}
