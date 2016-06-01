<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetRelationConfig;

use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig\LoadFromConfigBag;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class LoadFromConfigBagTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityHierarchyProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configBag;

    /** @var LoadFromConfigBag */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->entityHierarchyProvider = $this
            ->getMock('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface');
        $this->configBag               = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigBag')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadFromConfigBag(
            $this->configExtensionRegistry,
            new ConfigLoaderFactory($this->configExtensionRegistry),
            $this->entityHierarchyProvider,
            $this->configBag
        );
    }

    public function testProcessWhenConfigAlreadyExists()
    {
        $config = [];

        $this->configBag->expects($this->never())
            ->method('getRelationConfig');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [],
            $this->context->getResult()
        );
    }

    public function testProcessWhenNoConfigIsReturnedFromConfigBag()
    {
        $this->configBag->expects($this->exactly(2))
            ->method('getRelationConfig')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, $this->context->getVersion(), null],
                    ['Test\ParentClass', $this->context->getVersion(), null],
                ]
            );

        $this->entityHierarchyProvider->expects($this->once())
            ->method('getHierarchyForClassName')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['Test\ParentClass']);

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWithInheritanceWhenNoParentConfigIsReturnedFromConfigBag()
    {
        $this->configBag->expects($this->exactly(2))
            ->method('getRelationConfig')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, $this->context->getVersion(), ['inherit' => true]],
                    ['Test\ParentClass', $this->context->getVersion(), null],
                ]
            );

        $this->entityHierarchyProvider->expects($this->once())
            ->method('getHierarchyForClassName')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['Test\ParentClass']);

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWhenConfigWithoutInheritanceIsReturnedFromConfigBag()
    {
        $this->configBag->expects($this->once())
            ->method('getRelationConfig')
            ->with(self::TEST_CLASS_NAME, $this->context->getVersion())
            ->willReturn(['inherit' => false]);

        $this->entityHierarchyProvider->expects($this->never())
            ->method('getHierarchyForClassName');

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWithoutInheritance()
    {
        $config = [
            'collapse' => true,
            'fields'   => [
                'field1' => null,
                'field2' => null,
                'field3' => null,
            ],
            'filters'  => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'sorters'  => [
                'fields' => [
                    'field1' => null
                ]
            ],
        ];

        $this->configBag->expects($this->once())
            ->method('getRelationConfig')
            ->with(self::TEST_CLASS_NAME, $this->context->getVersion())
            ->willReturn($config);

        $this->entityHierarchyProvider->expects($this->once())
            ->method('getHierarchyForClassName')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn([]);

        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'collapse' => true,
                'fields'   => [
                    'field1' => null,
                    'field2' => null,
                    'field3' => null,
                ]
            ],
            $this->context->getResult()
        );
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null,
                ]
            ],
            $this->context->getFilters()
        );
        $this->assertFalse($this->context->hasSorters());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWithInheritance()
    {
        $config = [
            'collapse' => false,
            'fields'   => [
                'field1' => null,
                'field2' => null,
                'field3' => null,
                'field4' => null,
            ],
            'filters'  => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'sorters'  => [
                'fields' => [
                    'field1' => null
                ]
            ],
        ];

        $parentConfig1 = [
            'order_by' => [
                'field2' => 'ASC'
            ],
            'fields'   => [
                'field2' => [
                    'exclude' => true
                ],
            ],
            'filters'  => [
                'fields' => [
                    'field2' => null,
                ]
            ],
            'sorters'  => [
                'fields' => [
                    'field2' => null,
                ]
            ],
        ];

        $parentConfig3 = [
            'collapse' => true,
            'inherit'  => false,
            'order_by' => [
                'field3' => 'ASC'
            ],
            'fields'   => [
                'field3' => [
                    'exclude' => true
                ],
            ],
            'filters'  => [
                'fields' => [
                    'field3' => null,
                ]
            ],
            'sorters'  => [
                'fields' => [
                    'field3' => null,
                ]
            ],
        ];

        $this->configBag->expects($this->exactly(4))
            ->method('getRelationConfig')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, $this->context->getVersion(), $config],
                    ['Test\ParentClass1', $this->context->getVersion(), $parentConfig1],
                    ['Test\ParentClass2', $this->context->getVersion(), null],
                    ['Test\ParentClass3', $this->context->getVersion(), $parentConfig3],
                ]
            );

        $this->entityHierarchyProvider->expects($this->once())
            ->method('getHierarchyForClassName')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['Test\ParentClass1', 'Test\ParentClass2', 'Test\ParentClass3', 'Test\ParentClass4']);

        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'order_by' => [
                    'field2' => 'ASC'
                ],
                'fields'   => [
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ],
                    'field4' => null,
                ]
            ],
            $this->context->getResult()
        );
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null,
                    'field2' => null,
                    'field3' => null,
                ]
            ],
            $this->context->getFilters()
        );
        $this->assertFalse($this->context->hasSorters());
    }

    public function testProcessWithInheritanceAndNoConfigIsReturnedFromConfigBag()
    {
        $parentConfig1 = [
            'order_by' => [
                'field1' => 'ASC'
            ],
            'fields'   => [
                'field1' => [
                    'property_path' => 'realField1'
                ],
            ],
            'filters'  => [
                'fields' => [
                    'field1' => null,
                ]
            ],
            'sorters'  => [
                'fields' => [
                    'field1' => null,
                ]
            ],
        ];

        $this->configBag->expects($this->exactly(2))
            ->method('getRelationConfig')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, $this->context->getVersion(), null],
                    ['Test\ParentClass1', $this->context->getVersion(), $parentConfig1],
                ]
            );

        $this->entityHierarchyProvider->expects($this->once())
            ->method('getHierarchyForClassName')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['Test\ParentClass1']);

        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'order_by' => [
                    'field1' => 'ASC'
                ],
                'fields'   => [
                    'field1' => [
                        'property_path' => 'realField1'
                    ],
                ],
            ],
            $this->context->getResult()
        );
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null,
                ]
            ],
            $this->context->getFilters()
        );
        $this->assertFalse($this->context->hasSorters());
    }
}
