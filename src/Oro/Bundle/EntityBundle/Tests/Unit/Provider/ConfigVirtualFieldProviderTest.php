<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;
use Oro\Bundle\EntityBundle\Provider\ConfigVirtualFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;

class ConfigVirtualFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigVirtualFieldProvider */
    private $virtualFieldProvider;

    /** @var array */
    private $virtualFieldsConfig;

    protected function setUp(): void
    {
        $hierarchy = [
            'TestEntity1' => ['AbstractEntity1'],
            'TestEntity2' => ['AbstractEntity2', 'AbstractEntity1'],
            'TestEntity3' => ['AbstractEntity1']
        ];
        $this->virtualFieldsConfig = [
            'AbstractEntity1' => [
                'virtual_field1' => [
                    'query' => [
                        'select' => ['select AbstractEntity1.virtual_field1'],
                        'join'   => ['join AbstractEntity1.virtual_field1']
                    ]
                ],
                'virtual_field3' => [
                    'query' => [
                        'select' => ['select AbstractEntity1.virtual_field3'],
                        'join'   => ['join AbstractEntity1.virtual_field3']
                    ]
                ]
            ],
            'AbstractEntity2' => [
                'virtual_field1'  => [
                    'query' => [
                        'select' => ['select AbstractEntity2.virtual_field1']
                    ]
                ],
                'virtual_field2' => [
                    'query' => [
                        'select' => ['select AbstractEntity2.virtual_field2']
                    ]
                ]
            ],
            'TestEntity3'     => [
                'virtual_field1'  => [
                    'query' => [
                        'select' => ['select TestEntity3.virtual_field1']
                    ]
                ],
                'virtual_field2' => [
                    'query' => [
                        'select' => ['select TestEntity3.virtual_field2']
                    ]
                ]
            ],
            'TestEntity4'     => [
                'virtual_field1' => [
                    'query' => [
                        'select' => ['select TestEntity4.virtual_field1']
                    ]
                ]
            ]
        ];

        $entityHierarchyProvider = $this->createMock(EntityHierarchyProviderInterface::class);
        $entityHierarchyProvider->expects($this->any())
            ->method('getHierarchy')
            ->willReturn($hierarchy);
        $configProvider = $this->createMock(EntityConfigurationProvider::class);
        $configProvider->expects(self::any())
            ->method('getConfiguration')
            ->with(EntityConfiguration::VIRTUAL_FIELDS)
            ->willReturn($this->virtualFieldsConfig);

        $this->virtualFieldProvider = new ConfigVirtualFieldProvider(
            $entityHierarchyProvider,
            $configProvider
        );
    }

    public function testEntityInheritedFromAnotherEntityWithVirtualField()
    {
        $this->assertEquals(
            ['virtual_field1', 'virtual_field3'],
            $this->virtualFieldProvider->getVirtualFields('TestEntity1')
        );

        $this->assertTrue($this->virtualFieldProvider->isVirtualField('TestEntity1', 'virtual_field1'));
        $this->assertTrue($this->virtualFieldProvider->isVirtualField('TestEntity1', 'virtual_field3'));
        $this->assertFalse($this->virtualFieldProvider->isVirtualField('TestEntity1', 'field'));

        $this->assertEquals(
            $this->virtualFieldsConfig['AbstractEntity1']['virtual_field1']['query'],
            $this->virtualFieldProvider->getVirtualFieldQuery('TestEntity1', 'virtual_field1')
        );
        $this->assertEquals(
            $this->virtualFieldsConfig['AbstractEntity1']['virtual_field3']['query'],
            $this->virtualFieldProvider->getVirtualFieldQuery('TestEntity1', 'virtual_field3')
        );
    }

    public function testEntityInheritedFromSeveralEntitiesWithVirtualFields()
    {
        $this->assertEquals(
            ['virtual_field1', 'virtual_field3', 'virtual_field2'],
            $this->virtualFieldProvider->getVirtualFields('TestEntity2')
        );

        $this->assertTrue($this->virtualFieldProvider->isVirtualField('TestEntity2', 'virtual_field1'));
        $this->assertTrue($this->virtualFieldProvider->isVirtualField('TestEntity2', 'virtual_field2'));
        $this->assertTrue($this->virtualFieldProvider->isVirtualField('TestEntity2', 'virtual_field3'));
        $this->assertFalse($this->virtualFieldProvider->isVirtualField('TestEntity2', 'field'));

        $this->assertEquals(
            $this->virtualFieldsConfig['AbstractEntity2']['virtual_field1']['query'],
            $this->virtualFieldProvider->getVirtualFieldQuery('TestEntity2', 'virtual_field1')
        );
        $this->assertEquals(
            $this->virtualFieldsConfig['AbstractEntity2']['virtual_field2']['query'],
            $this->virtualFieldProvider->getVirtualFieldQuery('TestEntity2', 'virtual_field2')
        );
        $this->assertEquals(
            $this->virtualFieldsConfig['AbstractEntity1']['virtual_field3']['query'],
            $this->virtualFieldProvider->getVirtualFieldQuery('TestEntity2', 'virtual_field3')
        );
    }

    public function testEntityWithOwnVirtualFieldsAndInheritedFromAnotherEntityWithVirtualField()
    {
        $this->assertEquals(
            ['virtual_field1', 'virtual_field3', 'virtual_field2'],
            $this->virtualFieldProvider->getVirtualFields('TestEntity3')
        );

        $this->assertTrue($this->virtualFieldProvider->isVirtualField('TestEntity3', 'virtual_field1'));
        $this->assertTrue($this->virtualFieldProvider->isVirtualField('TestEntity3', 'virtual_field2'));
        $this->assertTrue($this->virtualFieldProvider->isVirtualField('TestEntity3', 'virtual_field3'));
        $this->assertFalse($this->virtualFieldProvider->isVirtualField('TestEntity3', 'field'));

        $this->assertEquals(
            $this->virtualFieldsConfig['TestEntity3']['virtual_field1']['query'],
            $this->virtualFieldProvider->getVirtualFieldQuery('TestEntity3', 'virtual_field1')
        );
        $this->assertEquals(
            $this->virtualFieldsConfig['TestEntity3']['virtual_field2']['query'],
            $this->virtualFieldProvider->getVirtualFieldQuery('TestEntity3', 'virtual_field2')
        );
        $this->assertEquals(
            $this->virtualFieldsConfig['AbstractEntity1']['virtual_field3']['query'],
            $this->virtualFieldProvider->getVirtualFieldQuery('TestEntity3', 'virtual_field3')
        );
    }

    public function testEntityWithOwnVirtualField()
    {
        $this->assertEquals(
            ['virtual_field1'],
            $this->virtualFieldProvider->getVirtualFields('TestEntity4')
        );

        $this->assertTrue($this->virtualFieldProvider->isVirtualField('TestEntity4', 'virtual_field1'));
        $this->assertFalse($this->virtualFieldProvider->isVirtualField('TestEntity4', 'field'));

        $this->assertEquals(
            $this->virtualFieldsConfig['TestEntity4']['virtual_field1']['query'],
            $this->virtualFieldProvider->getVirtualFieldQuery('TestEntity4', 'virtual_field1')
        );
    }

    public function testEntityWithoutVirtualFields()
    {
        $this->assertEquals(
            [],
            $this->virtualFieldProvider->getVirtualFields('EntityWithoutVirtualFields')
        );

        $this->assertFalse($this->virtualFieldProvider->isVirtualField('EntityWithoutVirtualFields', 'field'));
    }
}
