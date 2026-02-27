<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Model;

use Oro\Bundle\ApiBundle\Batch\Model\BatchAffectedEntities;

class BatchAffectedEntitiesTest extends \PHPUnit\Framework\TestCase
{
    public function testEmpty(): void
    {
        $affectedEntities = new BatchAffectedEntities();

        self::assertSame([], $affectedEntities->getPrimaryEntities());
        self::assertSame([], $affectedEntities->getIncludedEntities());
        self::assertSame([], $affectedEntities->getPayload());
    }

    public function testPrimaryEntities(): void
    {
        $affectedEntities = new BatchAffectedEntities();

        $affectedEntities->addPrimaryEntity(1, null, false);
        self::assertSame(
            [[1, null, false]],
            $affectedEntities->getPrimaryEntities()
        );

        $affectedEntities->addPrimaryEntity(2, 'entity_2', true);
        self::assertSame(
            [[1, null, false], [2, 'entity_2', true]],
            $affectedEntities->getPrimaryEntities()
        );

        $affectedEntities->addPrimaryEntity(1, 'entity_1', false);
        self::assertSame(
            [[1, 'entity_1', false], [2, 'entity_2', true]],
            $affectedEntities->getPrimaryEntities()
        );
    }

    public function testIncludedEntities(): void
    {
        $affectedEntities = new BatchAffectedEntities();

        $affectedEntities->addIncludedEntity('Test\Entity1', 1, null, false);
        self::assertSame(
            [['Test\Entity1', 1, null, false]],
            $affectedEntities->getIncludedEntities()
        );

        $affectedEntities->addIncludedEntity('Test\Entity1', 2, 'entity_2', true);
        self::assertSame(
            [['Test\Entity1', 1, null, false], ['Test\Entity1', 2, 'entity_2', true]],
            $affectedEntities->getIncludedEntities()
        );

        $affectedEntities->addIncludedEntity('Test\Entity1', 1, 'entity_1', false);
        self::assertSame(
            [['Test\Entity1', 1, 'entity_1', false], ['Test\Entity1', 2, 'entity_2', true]],
            $affectedEntities->getIncludedEntities()
        );

        $affectedEntities->addIncludedEntity('Test\Entity2', 1, 'entity_3', false);
        self::assertSame(
            [
                ['Test\Entity1', 1, 'entity_1', false],
                ['Test\Entity1', 2, 'entity_2', true],
                ['Test\Entity2', 1, 'entity_3', false]
            ],
            $affectedEntities->getIncludedEntities()
        );
    }

    public function testPayload(): void
    {
        $affectedEntities = new BatchAffectedEntities();

        $affectedEntities->setToPayload('key1', ['val1']);
        self::assertSame(['key1' => ['val1']], $affectedEntities->getPayload());

        $affectedEntities->setToPayload('key2', 'val2');
        self::assertSame(['key1' => ['val1'], 'key2' => 'val2'], $affectedEntities->getPayload());

        $affectedEntities->setToPayload('key1', ['updatedVal1']);
        self::assertSame(['key1' => ['updatedVal1'], 'key2' => 'val2'], $affectedEntities->getPayload());

        $affectedEntities->addToPayload('key1', ['anotherVal']);
        self::assertSame(
            ['key1' => ['updatedVal1', 'anotherVal'], 'key2' => 'val2'],
            $affectedEntities->getPayload()
        );

        $affectedEntities->addToPayload('key3', ['val3']);
        self::assertSame(
            ['key1' => ['updatedVal1', 'anotherVal'], 'key2' => 'val2', 'key3' => ['val3']],
            $affectedEntities->getPayload()
        );

        $affectedEntities->removeFromPayload('key1');
        self::assertSame(['key2' => 'val2', 'key3' => ['val3']], $affectedEntities->getPayload());

        $affectedEntities->removeFromPayload('key2');
        self::assertSame(['key3' => ['val3']], $affectedEntities->getPayload());

        $affectedEntities->removeFromPayload('key3');
        self::assertSame([], $affectedEntities->getPayload());
    }

    public function testToArray(): void
    {
        $affectedEntities = new BatchAffectedEntities();

        self::assertSame([], $affectedEntities->toArray());

        $affectedEntities->addPrimaryEntity(1, null, false);
        self::assertSame(
            [
                'primary' => [[1, null, false]]
            ],
            $affectedEntities->toArray()
        );

        $affectedEntities->addIncludedEntity('Test\Entity1', 2, null, false);
        self::assertSame(
            [
                'primary' => [[1, null, false]],
                'included' => [['Test\Entity1', 2, null, false]]
            ],
            $affectedEntities->toArray()
        );

        $affectedEntities->setToPayload('key1', 'val1');
        self::assertSame(
            [
                'primary' => [[1, null, false]],
                'included' => [['Test\Entity1', 2, null, false]],
                'payload' => ['key1' => 'val1']
            ],
            $affectedEntities->toArray()
        );
    }
}
