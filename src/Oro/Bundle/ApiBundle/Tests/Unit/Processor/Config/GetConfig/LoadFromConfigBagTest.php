<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\LoadFromConfigBag;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
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
                    'description'  => 'Action Description',
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

        $this->context->setTargetAction('create');
        $this->context->setExtras([new DescriptionsConfigExtra(), new FiltersConfigExtra()]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'description'  => 'Action Description',
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
        $this->assertFalse($this->context->has(ConfigUtil::ACTIONS));
    }

    public function testProcessWithoutInheritance()
    {
        $config = [
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
                    'description'  => 'Action Description',
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

        $this->context->setTargetAction('create');
        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'description'  => 'Action Description',
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
        $this->assertFalse($this->context->has(ConfigUtil::ACTIONS));
    }

    public function testProcessForPrimaryResourceWithSubresourcesConfig()
    {
        $config = [
            'description'  => 'Test Description',
            'form_type'    => 'test_form',
            'form_options' => ['option' => 'value'],
            'fields'       => [
                'field1' => null,
                'field2' => null,
                'field3' => null,
                'field4' => [
                    'exclude'      => true,
                    'form_type'    => 'field_form',
                    'form_options' => ['option' => 'value'],
                ],
            ],
            'actions'      => [
                'create' => [
                    'status_codes' => [
                        123 => ['description' => 'status 123'],
                        456 => ['exclude' => true]
                    ],
                    'description'  => 'Action Description',
                    'form_type'    => 'action_form',
                    'form_options' => ['action_option' => 'action_value'],
                    'fields'       => [
                        'field2' => [
                            'exclude' => true
                        ],
                        'field4' => [
                            'form_type'    => 'action_field_form',
                            'form_options' => ['action_option' => 'action_value'],
                        ],
                    ]
                ]
            ],
            'subresources' => [
                'testSubresource' => [
                    'actions' => [
                        'create' => [
                            'status_codes' => [
                                123 => ['description' => 'subresource status 123'],
                                234 => ['description' => 'subresource status 234'],
                                345 => ['exclude' => true]
                            ],
                            'description'  => 'Subresource Description',
                            'form_type'    => 'subresource_form',
                            'form_options' => ['subresource_option' => 'subresource_value'],
                            'fields'       => [
                                'field3' => [
                                    'exclude' => true
                                ],
                                'field4' => [
                                    'form_type'    => 'subresource_field_form',
                                    'form_options' => ['subresource_option' => 'subresource_value'],
                                ],
                            ]
                        ]
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

        $this->context->setTargetAction('create');
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'description'  => 'Action Description',
                'form_type'    => 'action_form',
                'form_options' => ['action_option' => 'action_value'],
                'fields'       => [
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => null,
                    'field4' => [
                        'exclude'      => true,
                        'form_type'    => 'action_field_form',
                        'form_options' => ['action_option' => 'action_value'],
                    ],
                ]
            ],
            $this->context->getResult()
        );
        $this->assertFalse($this->context->has(ConfigUtil::ACTIONS));
        $this->assertFalse($this->context->has(ConfigUtil::SUBRESOURCES));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForSubresourceWithSubresourcesConfig()
    {
        $config = [
            'description'  => 'Test Description',
            'form_type'    => 'test_form',
            'form_options' => ['option' => 'value'],
            'fields'       => [
                'field1' => null,
                'field2' => null,
                'field3' => null,
                'field4' => [
                    'exclude'      => true,
                    'form_type'    => 'field_form',
                    'form_options' => ['option' => 'value'],
                ],
            ],
            'actions'      => [
                'create' => [
                    'status_codes' => [
                        123 => ['description' => 'status 123'],
                        456 => ['exclude' => true]
                    ],
                    'description'  => 'Action Description',
                    'form_type'    => 'action_form',
                    'form_options' => ['action_option' => 'action_value'],
                    'fields'       => [
                        'field2' => [
                            'exclude' => true
                        ],
                        'field4' => [
                            'form_type'    => 'action_field_form',
                            'form_options' => ['action_option' => 'action_value'],
                        ],
                    ]
                ]
            ]
        ];
        $parentConfig = [
            'subresources' => [
                'testSubresource' => [
                    'actions' => [
                        'create' => [
                            'status_codes' => [
                                123 => ['description' => 'subresource status 123'],
                                234 => ['description' => 'subresource status 234'],
                                345 => ['exclude' => true]
                            ],
                            'description'  => 'Subresource Description',
                            'form_type'    => 'subresource_form',
                            'form_options' => ['subresource_option' => 'subresource_value'],
                            'fields'       => [
                                'field3' => [
                                    'exclude' => true
                                ],
                                'field4' => [
                                    'form_type'    => 'subresource_field_form',
                                    'form_options' => ['subresource_option' => 'subresource_value'],
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->configBag->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, $this->context->getVersion(), $config],
                    ['Test\ParentClass', $this->context->getVersion(), $parentConfig],
                ]
            );

        $this->entityHierarchyProvider->expects($this->exactly(2))
            ->method('getHierarchyForClassName')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, []],
                    ['Test\ParentClass', []],
                ]
            );

        $this->context->setTargetAction('create');
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('testSubresource');
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'description'  => 'Subresource Description',
                'form_type'    => 'subresource_form',
                'form_options' => ['subresource_option' => 'subresource_value'],
                'fields'       => [
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ],
                    'field4' => [
                        'exclude'      => true,
                        'form_type'    => 'subresource_field_form',
                        'form_options' => ['subresource_option' => 'subresource_value'],
                    ],
                ]
            ],
            $this->context->getResult()
        );
        $this->assertFalse($this->context->has(ConfigUtil::ACTIONS));
        $this->assertFalse($this->context->has(ConfigUtil::SUBRESOURCES));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForSubresourceWithSubresourcesConfigAndDescriptionsConfigExtra()
    {
        $config = [
            'description'  => 'Test Description',
            'form_type'    => 'test_form',
            'form_options' => ['option' => 'value'],
            'fields'       => [
                'field1' => null,
                'field2' => null,
                'field3' => null,
                'field4' => [
                    'exclude'      => true,
                    'form_type'    => 'field_form',
                    'form_options' => ['option' => 'value'],
                ],
            ],
            'actions'      => [
                'create' => [
                    'status_codes' => [
                        123 => ['description' => 'status 123'],
                        456 => ['exclude' => true]
                    ],
                    'description'  => 'Action Description',
                    'form_type'    => 'action_form',
                    'form_options' => ['action_option' => 'action_value'],
                    'fields'       => [
                        'field2' => [
                            'exclude' => true
                        ],
                        'field4' => [
                            'form_type'    => 'action_field_form',
                            'form_options' => ['action_option' => 'action_value'],
                        ],
                    ]
                ]
            ]
        ];
        $parentConfig = [
            'subresources' => [
                'testSubresource' => [
                    'actions' => [
                        'create' => [
                            'status_codes' => [
                                123 => ['description' => 'subresource status 123'],
                                234 => ['description' => 'subresource status 234'],
                                345 => ['exclude' => true]
                            ],
                            'description'  => 'Subresource Description',
                            'form_type'    => 'subresource_form',
                            'form_options' => ['subresource_option' => 'subresource_value'],
                            'fields'       => [
                                'field3' => [
                                    'exclude' => true
                                ],
                                'field4' => [
                                    'form_type'    => 'subresource_field_form',
                                    'form_options' => ['subresource_option' => 'subresource_value'],
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->configBag->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, $this->context->getVersion(), $config],
                    ['Test\ParentClass', $this->context->getVersion(), $parentConfig],
                ]
            );

        $this->entityHierarchyProvider->expects($this->exactly(2))
            ->method('getHierarchyForClassName')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, []],
                    ['Test\ParentClass', []],
                ]
            );

        $this->context->setExtras([new DescriptionsConfigExtra()]);
        $this->context->setTargetAction('create');
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('testSubresource');
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'status_codes' => [
                    123 => ['description' => 'subresource status 123'],
                    234 => ['description' => 'subresource status 234'],
                    345 => ['exclude' => true],
                    456 => ['exclude' => true]
                ],
                'description'  => 'Subresource Description',
                'form_type'    => 'subresource_form',
                'form_options' => ['subresource_option' => 'subresource_value'],
                'fields'       => [
                    'field1' => null,
                    'field2' => [
                        'exclude' => true
                    ],
                    'field3' => [
                        'exclude' => true
                    ],
                    'field4' => [
                        'exclude'      => true,
                        'form_type'    => 'subresource_field_form',
                        'form_options' => ['subresource_option' => 'subresource_value'],
                    ],
                ]
            ],
            $this->context->getResult()
        );
        $this->assertFalse($this->context->has(ConfigUtil::ACTIONS));
        $this->assertFalse($this->context->has(ConfigUtil::SUBRESOURCES));
    }

    public function testProcessMergeSubresourceFilters()
    {
        $config = [
            'filters' => [
                'fields' => [
                    'field1' => [
                        'description' => 'filter 1'
                    ],
                    'field2' => [
                        'description' => 'filter 2'
                    ],
                ]
            ]
        ];
        $parentConfig = [
            'subresources' => [
                'testSubresource' => [
                    'filters' => [
                        'fields' => [
                            'field2' => [
                                'description' => 'Subresource filter 2'
                            ],
                            'field3' => [
                                'description' => 'Subresource filter 3'
                            ],
                        ]
                    ]
                ]
            ]
        ];

        $this->configBag->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, $this->context->getVersion(), $config],
                    ['Test\ParentClass', $this->context->getVersion(), $parentConfig],
                ]
            );

        $this->entityHierarchyProvider->expects($this->exactly(2))
            ->method('getHierarchyForClassName')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, []],
                    ['Test\ParentClass', []],
                ]
            );

        $this->context->setExtras([new DescriptionsConfigExtra()]);
        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->context->setTargetAction('create');
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('testSubresource');
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => [
                        'description' => 'filter 1'
                    ],
                    'field2' => [
                        'description' => 'Subresource filter 2'
                    ],
                    'field3' => [
                        'description' => 'Subresource filter 3'
                    ],
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testProcessSubresourceFiltersShouldCompletelyReplaceOwnFilters()
    {
        $config = [
            'filters' => [
                'fields' => [
                    'field1' => [
                        'description' => 'filter 1'
                    ],
                    'field2' => [
                        'description' => 'filter 2'
                    ],
                ]
            ]
        ];
        $parentConfig = [
            'subresources' => [
                'testSubresource' => [
                    'filters' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'field2' => [
                                'description' => 'Subresource filter 2'
                            ],
                            'field3' => [
                                'description' => 'Subresource filter 3'
                            ],
                        ]
                    ]
                ]
            ]
        ];

        $this->configBag->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, $this->context->getVersion(), $config],
                    ['Test\ParentClass', $this->context->getVersion(), $parentConfig],
                ]
            );

        $this->entityHierarchyProvider->expects($this->exactly(2))
            ->method('getHierarchyForClassName')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, []],
                    ['Test\ParentClass', []],
                ]
            );

        $this->context->setExtras([new DescriptionsConfigExtra()]);
        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->context->setTargetAction('create');
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('testSubresource');
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field2' => [
                        'description' => 'Subresource filter 2'
                    ],
                    'field3' => [
                        'description' => 'Subresource filter 3'
                    ],
                ]
            ],
            $this->context->getFilters()
        );
    }

    public function testProcessMergeSubresourceFiltersWhenTargetEntityDoesNotHaveOwnFilters()
    {
        $config = [
        ];
        $parentConfig = [
            'subresources' => [
                'testSubresource' => [
                    'filters' => [
                        'fields' => [
                            'field1' => [
                                'description' => 'Subresource filter 1'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->configBag->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, $this->context->getVersion(), $config],
                    ['Test\ParentClass', $this->context->getVersion(), $parentConfig],
                ]
            );

        $this->entityHierarchyProvider->expects($this->exactly(2))
            ->method('getHierarchyForClassName')
            ->willReturnMap(
                [
                    [self::TEST_CLASS_NAME, []],
                    ['Test\ParentClass', []],
                ]
            );

        $this->context->setExtras([new DescriptionsConfigExtra()]);
        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->context->setTargetAction('create');
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('testSubresource');
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => [
                        'description' => 'Subresource filter 1'
                    ]
                ]
            ],
            $this->context->getFilters()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWithInheritance()
    {
        $config = [
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

        $this->context->setTargetAction('create');
        $this->context->setExtras([new FiltersConfigExtra()]);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
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
