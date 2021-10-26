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
class SyntaxTest extends \PHPFUI\PHPUnitSyntaxCoverage\Extensions
	{
	public function testDirectory() : void
		{
		$this->assertValidPHPDirectory(__DIR__ . '/../src', 'PHPFUI directory has an error');
		}
	}
