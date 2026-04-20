<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityBundle\Helper\IdHelper;
use PHPUnit\Framework\TestCase;

class IdHelperTest extends TestCase
{
    public function testGetIdsSequenceWithLargeNumberOfIds(): void
    {
        // Test with more than 65535 IDs (BB-26595)
        $identifiers = \range(1, 70000);
        $result = IdHelper::getIdsSequence($identifiers);

        $this->assertStringStartsWith('{', $result);
        $this->assertStringEndsWith('70000}', $result);
        $this->assertStringContainsString(',', $result);
        $this->assertSame(
            69999,
            \substr_count($result, ','),
            'Should have 69999 commas for 70000 IDs'
        );
    }

    /** @dataProvider getIdsSequenceDataProvider */
    public function testGetIdsSequenceDataProvider(array $identifiers, string $expected): void
    {
        $result = IdHelper::getIdsSequence($identifiers);
        $this->assertSame($expected, $result);
    }

    public function getIdsSequenceDataProvider(): array
    {
        return [
            'empty array' => [
                'identifiers' => [],
                'expected' => '{}',
            ],
            'single numeric id' => [
                'identifiers' => [42],
                'expected' => '{42}',
            ],
            'multiple numeric ids' => [
                'identifiers' => [10, 20, 30],
                'expected' => '{10,20,30}',
            ],
            'single string id' => [
                'identifiers' => ['abc'],
                'expected' => '{abc}',
            ],
            'multiple string ids' => [
                'identifiers' => ['uuid-1', 'uuid-2', 'uuid-3'],
                'expected' => '{uuid-1,uuid-2,uuid-3}',
            ],
            'mixed types' => [
                'identifiers' => [1, 'two', 3],
                'expected' => '{1,two,3}',
            ],
        ];
    }
}
