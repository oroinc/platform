<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\LoadFromConfigBag;
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
        $this->configBag = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigBag')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadFromConfigBag(
            $this->configExtensionRegistry,
            new ConfigLoaderFactory($this->configExtensionRegistry),
            $this->entityHierarchyProvider,
            $this->configBag
        );

        $this->context->setTargetAction('create');
    }

    public function testProcessWhenConfigAlreadyExists()
    {
        $config = [];

        $this->configBag->expects($this->never())
            ->method('getConfig');

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
            ->method('getConfig')
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
            ->method('getConfig')
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
            ->method('getConfig')
            ->with(self::TEST_CLASS_NAME, $this->context->getVersion())
            ->willReturn(['inherit' => false]);

        $this->entityHierarchyProvider->expects($this->never())
            ->method('getHierarchyForClassName');

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWithDescriptions()
    {
        $config = [
            'label'        => 'Test Entity',
            'plural_label' => 'Test Entities',
            'form_type'    => 'test_form',
            'form_options' => ['option' => 'value'],
            'fields'       => [
                'field1' => null,
                'field2' => null,
                'field3' => null,
            ],
            'filters'      => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'sorters'      => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'actions'      => [
                'create' => [
                    'status_codes' => [
                        123 => ['description' => 'status 123'],
                        456 => ['exclude' => true]
                    ],
                    'form_type'    => 'action_form',
                    'form_options' => ['action_option' => 'action_value'],
                ]
            ]
        ];

        $this->configBag->expects($this->once())
            ->method('getConfig')
            ->with(self::TEST_CLASS_NAME, $this->context->getVersion())
            ->willReturn($config);

        $this->entityHierarchyProvider->expects($this->once())
            ->method('getHierarchyForClassName')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn([]);

        $this->context->setExtras([new DescriptionsConfigExtra(), new FiltersConfigExtra()]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'label'        => 'Test Entity',
                'plural_label' => 'Test Entities',
                'form_type'    => 'action_form',
                'form_options' => ['action_option' => 'action_value'],
                'status_codes' => [
                    123 => ['description' => 'status 123'],
                    456 => ['exclude' => true]
                ],
                'fields'       => [
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
        $this->assertFalse($this->context->has('actions'));
    }

    public function testProcessWithoutInheritance()
    {
        $config = [
            'label'        => 'Test Entity',
            'plural_label' => 'Test Entities',
            'form_type'    => 'test_form',
            'form_options' => ['option' => 'value'],
            'fields'       => [
                'field1' => null,
                'field2' => null,
                'field3' => [
                    'exclude'      => true,
                    'form_type'    => 'field_form',
                    'form_options' => ['option' => 'value'],
                ],
            ],
            'filters'      => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'sorters'      => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'actions'      => [
                'create' => [
                    'status_codes' => [
                        123 => ['description' => 'status 123'],
                        456 => ['exclude' => true]
                    ],
                    'form_type'    => 'action_form',
                    'form_options' => ['action_option' => 'action_value'],
                    'fields'       => [
                        'field2' => [
                            'exclude' => true
                        ],
                        'field3' => [
                            'form_type'    => 'action_field_form',
                            'form_options' => ['action_option' => 'value'],
                        ],
                    ]
                ]
            ]
        ];

        $this->configBag->expects($this->once())
            ->method('getConfig')
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
                'label'        => 'Test Entity',
                'plural_label' => 'Test Entities',
                'form_type'    => 'action_form',
                'form_options' => ['action_option' => 'action_value'],
                'fields'       => [
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude'      => true,
                        'form_type'    => 'action_field_form',
                        'form_options' => ['action_option' => 'value'],
                    ],
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
        $this->assertFalse($this->context->has('actions'));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWithInheritance()
    {
        $config = [
            'label'        => 'Other Entity',
            'plural_label' => 'Other Entities',
            'fields'       => [
                'field1' => null,
                'field2' => null,
                'field3' => null,
                'field4' => null,
            ],
            'filters'      => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'sorters'      => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'actions'      => [
                'create' => [
                    'fields' => [
                        'field2' => [
                            'form_type'    => 'field_form',
                            'form_options' => ['option' => 'value'],
                        ],
                    ]
                ]
            ]
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
            'inherit'      => false,
            'label'        => 'Test Entity',
            'plural_label' => 'Test Entities',
            'order_by'     => [
                'field3' => 'ASC'
            ],
            'fields'       => [
                'field3' => [
                    'exclude' => true
                ],
            ],
            'filters'      => [
                'fields' => [
                    'field3' => null,
                ]
            ],
            'sorters'      => [
                'fields' => [
                    'field3' => null,
                ]
            ],
            'actions'      => [
                'create' => [
                    'form_type'    => 'parent3_action_form',
                    'form_options' => ['parent3_action_option' => 'value'],
                    'fields'       => [
                        'field2' => [
                            'form_type'    => 'parent3_action_field_form',
                            'form_options' => ['parent3_action_option' => 'value'],
                        ],
                        'field3' => [
                            'form_type'    => 'parent3_action_field_form',
                            'form_options' => ['parent3_action_option' => 'value'],
                        ],
                    ]
                ]
            ]
        ];

        $this->configBag->expects($this->exactly(4))
            ->method('getConfig')
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
                'label'        => 'Other Entity',
                'plural_label' => 'Other Entities',
                'order_by'     => [
                    'field2' => 'ASC'
                ],
                'form_type'    => 'parent3_action_form',
                'form_options' => ['parent3_action_option' => 'value'],
                'fields'       => [
                    'field1' => null,
                    'field2' => [
                        'exclude'      => true,
                        'form_type'    => 'field_form',
                        'form_options' => ['option' => 'value'],
                    ],
                    'field3' => [
                        'exclude'      => true,
                        'form_type'    => 'parent3_action_field_form',
                        'form_options' => ['parent3_action_option' => 'value'],
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
            ->method('getConfig')
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
