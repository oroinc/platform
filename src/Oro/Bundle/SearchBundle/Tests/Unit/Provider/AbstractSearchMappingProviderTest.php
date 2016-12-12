<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractSearchMappingProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    protected $testMapping = [
        'Oro\TestBundle\Entity\TestEntity' => [
            'alias'  => 'test_entity',
            'fields' => [
                [
                    'name' => 'firstname',
                    'target_type' => 'text',
                    'target_columns' => ['firstname']
                ],
                [
                    'name' => 'qty',
                    'target_type' => 'integer',
                    'target_columns' => ['qty']
                ]
            ]
        ]
    ];

    public function testGetEntitiesListAliases()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntitiesListAliases()
        );
    }

    public function testGetEntityAliases()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntityAliases(['Oro\TestBundle\Entity\TestEntity'])
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The search alias for the entity "Oro\TestBundle\Entity\UnknownEntity" not found.
     */
    public function testGetEntityAliasesForUnknownEntity()
    {
        $this->getProvider()->getEntityAliases(
            ['Oro\TestBundle\Entity\TestEntity', 'Oro\TestBundle\Entity\UnknownEntity']
        );
    }

    public function testGetEntityAliasesForEmptyClassNames()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntityAliases()
        );
    }

    public function testGetEntityAlias()
    {
        $this->assertEquals(
            'test_entity',
            $this->getProvider()->getEntityAlias('Oro\TestBundle\Entity\TestEntity')
        );
    }

    public function testGetEntityAliasForUnknownEntity()
    {
        $this->assertNull(
            $this->getProvider()->getEntityAlias('Oro\TestBundle\Entity\UnknownEntity')
        );
    }

    public function testGetEntityClasses()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity'],
            $this->getProvider()->getEntityClasses()
        );
    }

    public function testIsClassSupported()
    {
        $this->assertTrue($this->getProvider()->isClassSupported('Oro\TestBundle\Entity\TestEntity'));
        $this->assertFalse($this->getProvider()->isClassSupported('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testHasFieldsMapping()
    {
        $this->assertTrue($this->getProvider()->hasFieldsMapping('Oro\TestBundle\Entity\TestEntity'));
        $this->assertFalse($this->getProvider()->hasFieldsMapping('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testGetEntityMapParameter()
    {
        $this->assertEquals(
            'test_entity',
            $this->getProvider()->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'alias')
        );
        $this->assertFalse(
            $this->getProvider()->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'badParameter', false)
        );
    }

    public function testGetEntityClass()
    {
        $this->assertEquals(
            'Oro\TestBundle\Entity\TestEntity',
            $this->getProvider()->getEntityClass('test_entity')
        );
    }

    public function testGetEntityClassForUnknownAlias()
    {
        $this->assertNull(
            $this->getProvider()->getEntityClass('unknown_entity')
        );
    }

    /**
     * @return AbstractSearchMappingProvider
     */
    abstract protected function getProvider();
}
