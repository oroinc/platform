<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Entity;

use Oro\Bundle\LoggerBundle\Entity\LogEntry;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class LogEntryTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123, false],
            ['message', 'some error message', false],
            ['context', ['aaa' => 'bbb', 'ccc' => new \stdClass()], false],
            ['level', 100, false],
            ['channel', 'database', false],
            ['datetime', new \DateTime(), false],
            ['extra', [], false],
        ];

        self::assertPropertyAccessors(new LogEntry(), $properties);
    }
}
