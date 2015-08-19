<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

class SearchMappingProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SearchMappingProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

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

    public function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher->expects($this->any())
            ->method('dispatch');

        $this->provider = new SearchMappingProvider($this->eventDispatcher);
        $this->provider->setMappingConfig($this->testMapping);
    }

    public function testGetMappingConfig()
    {
        $this->assertEquals($this->testMapping, $this->provider->getMappingConfig());
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
}
