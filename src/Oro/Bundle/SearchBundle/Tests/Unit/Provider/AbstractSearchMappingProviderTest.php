<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractSearchMappingProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var AbstractSearchMappingProvider */
    protected $provider;

    /** @var array */
    protected $testMapping = [
        'Oro\TestBundle\Entity\TestEntity' => [
            'alias'  => 'test_entity',
            'fields' => [
                'name'           => 'firstname',
                'target_type'    => 'text',
                'target_columns' => ['firstname']
            ]
        ]
    ];

    protected function setUp()
    {
        $this->provider = $this->getProvider();
    }

    protected function tearDown()
    {
        unset($this->provider);
    }

    public function testGetEntitiesListAliases()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->provider->getEntitiesListAliases()
        );
    }

    public function testGetEntityAliases()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->provider->getEntityAliases(['Oro\TestBundle\Entity\TestEntity'])
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The search alias for the entity "Oro\TestBundle\Entity\UnknownEntity" not found.
     */
    public function testGetEntityAliasesForUnknownEntity()
    {
        $this->provider->getEntityAliases(
            ['Oro\TestBundle\Entity\TestEntity', 'Oro\TestBundle\Entity\UnknownEntity']
        );
    }

    public function testGetEntityAliasesForEmptyClassNames()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->provider->getEntityAliases()
        );
    }

    public function testGetEntityAlias()
    {
        $this->assertEquals(
            'test_entity',
            $this->provider->getEntityAlias('Oro\TestBundle\Entity\TestEntity')
        );
    }

    public function testGetEntityAliasForUnknownEntity()
    {
        $this->assertNull(
            $this->provider->getEntityAlias('Oro\TestBundle\Entity\UnknownEntity')
        );
    }

    public function testGetEntityClasses()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity'],
            $this->provider->getEntityClasses()
        );
    }

    public function testIsClassSupported()
    {
        $this->assertTrue($this->provider->isClassSupported('Oro\TestBundle\Entity\TestEntity'));
        $this->assertFalse($this->provider->isClassSupported('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testIsFieldsMappingExists()
    {
        $this->assertTrue($this->provider->isFieldsMappingExists('Oro\TestBundle\Entity\TestEntity'));
        $this->assertFalse($this->provider->isFieldsMappingExists('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testGetEntityMapParameter()
    {
        $this->assertEquals(
            'test_entity',
            $this->provider->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'alias')
        );
        $this->assertFalse(
            $this->provider->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'badParameter', false)
        );
    }

    public function testGetEntityClass()
    {
        $this->assertEquals(
            'Oro\TestBundle\Entity\TestEntity',
            $this->provider->getEntityClass('test_entity')
        );
    }

    public function testGetEntityClassForUnknownAlias()
    {
        $this->assertNull(
            $this->provider->getEntityClass('unknown_entity')
        );
    }

    /**
     * @return AbstractSearchMappingProvider
     */
    abstract protected function getProvider();
}
