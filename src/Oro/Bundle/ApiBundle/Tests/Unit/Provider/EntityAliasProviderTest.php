<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityAliasProvider;
use Oro\Bundle\EntityBundle\Model\EntityAlias;

class EntityAliasProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAliasProvider */
    protected $entityAliasProvider;

    protected function setUp()
    {
        $this->entityAliasProvider = new EntityAliasProvider(
            [
                'Test\Entity1' => [
                    'alias'        => 'entity1',
                    'plural_alias' => 'entity1_plural',
                ]
            ],
            ['Test\Entity2']
        );
    }

    public function testGetEntityAliasForExistingEntity()
    {
        $this->assertEquals(
            new EntityAlias('entity1', 'entity1_plural'),
            $this->entityAliasProvider->getEntityAlias('Test\Entity1')
        );
    }

    public function testGetEntityAliasForExcludedEntity()
    {
        $this->assertFalse(
            $this->entityAliasProvider->getEntityAlias('Test\Entity2')
        );
    }

    public function testGetEntityAliasForNotExistingEntity()
    {
        $this->assertNull(
            $this->entityAliasProvider->getEntityAlias('Test\Entity3')
        );
    }
}
