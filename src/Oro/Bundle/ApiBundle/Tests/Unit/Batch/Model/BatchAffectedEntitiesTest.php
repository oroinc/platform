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
}
