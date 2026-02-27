<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Model;

use Oro\Bundle\ApiBundle\Batch\Model\BatchAffectedEntitiesMerger;
use PHPUnit\Framework\TestCase;

class BatchAffectedEntitiesMergerTest extends TestCase
{
    /**
     * @dataProvider mergePrimaryEntitiesDataProvider
     */
    public function testMergePrimaryEntities(array $value, array $toMergeValue, array $expected): void
    {
        $affectedEntities = ['primary' => $value];
        BatchAffectedEntitiesMerger::mergeAffectedEntities($affectedEntities, ['primary' => $toMergeValue]);
        self::assertSame($expected, $affectedEntities['primary']);
    }

    public static function mergePrimaryEntitiesDataProvider(): array
    {
        return [
            'empty' => [[], [], []],
            'no value to merge' => [
                [[1, 'item1', false]],
                [],
                [[1, 'item1', false]]
            ],
            'no existing value' => [
                [],
                [[1, 'item1', false]],
                [[1, 'item1', false]]
            ],
            'merge' => [
                [[1, 'item1', false]],
                [[2, 'item2', true]],
                [[1, 'item1', false], [2, 'item2', true]]
            ],
        ];
    }

    /**
     * @dataProvider mergeIncludedEntitiesDataProvider
     */
    public function testMergeIncludedEntities(array $value, array $toMergeValue, array $expected): void
    {
        $affectedEntities = ['included' => $value];
        BatchAffectedEntitiesMerger::mergeAffectedEntities($affectedEntities, ['included' => $toMergeValue]);
        self::assertSame($expected, $affectedEntities['included']);
    }

    public static function mergeIncludedEntitiesDataProvider(): array
    {
        return [
            'empty' => [[], [], []],
            'no value to merge' => [
                [['Test\Entity', 1, 'item1', false]],
                [],
                [['Test\Entity', 1, 'item1', false]]
            ],
            'no existing value' => [
                [],
                [['Test\Entity', 1, 'item1', false]],
                [['Test\Entity', 1, 'item1', false]]
            ],
            'merge' => [
                [['Test\Entity', 1, 'item1', false]],
                [['Test\Entity', 2, 'item2', true]],
                [['Test\Entity', 1, 'item1', false], ['Test\Entity', 2, 'item2', true]]
            ],
        ];
    }

    /**
     * @dataProvider mergePayloadDataProvider
     */
    public function testMergePayload(array $value, array $toMergeValue, array $expected): void
    {
        $affectedEntities = ['payload' => $value];
        BatchAffectedEntitiesMerger::mergeAffectedEntities($affectedEntities, ['payload' => $toMergeValue]);
        self::assertSame($expected, $affectedEntities['payload']);
    }

    public static function mergePayloadDataProvider(): array
    {
        return [
            'empty' => [[], [], []],
            'no value to merge' => [
                ['key' => 'v'],
                [],
                ['key' => 'v']
            ],
            'no existing value' => [
                [],
                ['key' => 'v'],
                ['key' => 'v']
            ],
            'string values' => [
                ['key' => 'v1'],
                ['key' => 'v2'],
                ['key' => 'v2']
            ],
            'int values' => [
                ['key' => 1],
                ['key' => 2],
                ['key' => 2]
            ],
            'associative arrays' => [
                ['key' => ['k1' => 'v1', 'k2' => 'v2']],
                ['key' => ['k1' => 'm1', 'k3' => 'm3']],
                ['key' => ['k1' => 'm1', 'k2' => 'v2', 'k3' => 'm3']]
            ],
            'indexed arrays' => [
                ['key' => ['v1', 'v2']],
                ['key' => ['m1', 'm2']],
                ['key' => ['v1', 'v2', 'm1', 'm2']]
            ],
            'array, non array' => [
                ['key' => ['v1']],
                ['key' => 'v2'],
                ['key' => 'v2']
            ],
            'non array, array' => [
                ['key' => 'v1'],
                ['key' => ['v2']],
                ['key' => ['v2']]
            ],
            'associative nested array' => [
                ['key' => ['k1' => ['k' => 'v1'], 'k2' => ['k' => 'v2']]],
                ['key' => ['k1' => ['k' => 'm1'], 'k3' => ['k' => 'v3']]],
                ['key' => ['k1' => ['k' => 'm1'], 'k2' => ['k' => 'v2'], 'k3' => ['k' => 'v3']]]
            ],
            'indexed nested array' => [
                ['key' => ['k1' => ['v1'], 'k2' => ['v2']]],
                ['key' => ['k1' => ['m1'], 'k3' => ['v3']]],
                ['key' => ['k1' => ['v1', 'm1'], 'k2' => ['v2'], 'k3' => ['v3']]]
            ],
        ];
    }

    public function testMerge(): void
    {
        $affectedEntities = [
            'primary' => [[1, 'primary1', false]],
            'included' => [['Test\Entity', 1, 'included1', false]],
            'payload' => ['key' => 'val1']
        ];
        $toMerge = [
            'primary' => [[2, 'primary2', false]],
            'included' => [['Test\Entity', 2, 'included2', false]],
            'payload' => ['key' => 'val2']
        ];
        BatchAffectedEntitiesMerger::mergeAffectedEntities($affectedEntities, $toMerge);
        self::assertSame(
            [
                'primary' => [[1, 'primary1', false], [2, 'primary2', false]],
                'included' => [['Test\Entity', 1, 'included1', false], ['Test\Entity', 2, 'included2', false]],
                'payload' => ['key' => 'val2']
            ],
            $affectedEntities
        );
    }
}
