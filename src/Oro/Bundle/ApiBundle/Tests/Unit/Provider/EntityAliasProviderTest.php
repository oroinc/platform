<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityAliasProvider;
use Oro\Bundle\EntityBundle\Model\EntityAlias;

class EntityAliasProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityAliasProvider */
    private $entityAliasProvider;

    protected function setUp()
    {
        $this->entityAliasProvider = new EntityAliasProvider(
            [
                'Test\Entity1' => [
                    'alias'        => 'entity1',
                    'plural_alias' => 'entity1_plural'
                ]
            ],
            ['Test\Entity2']
        );
    }

    public function testGetClassNames()
    {
        self::assertEquals(
            ['Test\Entity1'],
            $this->entityAliasProvider->getClassNames()
        );
    }

    public function testGetEntityAliasForExistingEntity()
    {
        self::assertEquals(
            new EntityAlias('entity1', 'entity1_plural'),
            $this->entityAliasProvider->getEntityAlias('Test\Entity1')
        );
    }

    public function testGetEntityAliasForExcludedEntity()
    {
        self::assertFalse(
            $this->entityAliasProvider->getEntityAlias('Test\Entity2')
        );
    }

    public function testGetEntityAliasForNotExistingEntity()
    {
        self::assertNull(
            $this->entityAliasProvider->getEntityAlias('Test\Entity3')
        );
    }
}
