<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\LoadFromConfigBag;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeActionConfigHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeEntityConfigHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeFilterConfigHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeParentResourceHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeSubresourceConfigHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourceHierarchyProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class LoadFromConfigBagTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourceHierarchyProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourcesProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var LoadFromConfigBag */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->resourceHierarchyProvider = $this->getMockBuilder(ResourceHierarchyProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configBag = $this->getMockBuilder(ConfigBag::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourcesProvider = $this->getMockBuilder(ResourcesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mergeActionConfigHelper = new MergeActionConfigHelper();

        $this->processor = new LoadFromConfigBag(
            $this->configExtensionRegistry,
            new ConfigLoaderFactory($this->configExtensionRegistry),
            $this->resourceHierarchyProvider,
            $this->configBag,
            $this->resourcesProvider,
            new MergeParentResourceHelper($this->configProvider),
            new MergeEntityConfigHelper($this->configExtensionRegistry),
            $mergeActionConfigHelper,
            new MergeSubresourceConfigHelper($mergeActionConfigHelper, new MergeFilterConfigHelper())
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

        $this->resourceHierarchyProvider->expects($this->once())
            ->method('getParentClassNames')
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

        $this->resourceHierarchyProvider->expects($this->once())
            ->method('getParentClassNames')
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

        $this->resourceHierarchyProvider->expects($this->never())
            ->method('getParentClassNames');

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

        $this->resourceHierarchyProvider->expects($this->once())
            ->method('getParentClassNames')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn([]);

        $this->context->setTargetAction('create');
        $this->context->setExtras(
            [
                new DescriptionsConfigExtra(),
                new FiltersConfigExtra(),
                new SortersConfigExtra()
            ]
        );
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
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null,
                ]
            ],
            $this->context->getSorters()
        );
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

        $this->resourceHierarchyProvider->expects($this->once())
            ->method('getParentClassNames')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn([]);

        $this->context->setTargetAction('create');
        $this->context->setExtras([new FiltersConfigExtra(), new SortersConfigExtra()]);
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
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null,
                ]
            ],
            $this->context->getSorters()
        );
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

        $this->resourceHierarchyProvider->expects($this->once())
            ->method('getParentClassNames')
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

        $this->resourceHierarchyProvider->expects($this->exactly(2))
            ->method('getParentClassNames')
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

        $this->resourceHierarchyProvider->expects($this->exactly(2))
            ->method('getParentClassNames')
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

        $this->resourceHierarchyProvider->expects($this->exactly(2))
            ->method('getParentClassNames')
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

        $this->resourceHierarchyProvider->expects($this->exactly(2))
            ->method('getParentClassNames')
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
        $config = [];
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

        $this->resourceHierarchyProvider->expects($this->exactly(2))
            ->method('getParentClassNames')
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
            'fields'  => [
                'field1' => null,
                'field2' => null,
                'field3' => null,
                'field4' => null,
            ],
            'filters' => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'sorters' => [
                'fields' => [
                    'field1' => null
                ]
            ],
            'actions' => [
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
            'actions'  => [
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

        $this->resourceHierarchyProvider->expects($this->once())
            ->method('getParentClassNames')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['Test\ParentClass1', 'Test\ParentClass2', 'Test\ParentClass3', 'Test\ParentClass4']);

        $this->context->setTargetAction('create');
        $this->context->setExtras([new FiltersConfigExtra(), new SortersConfigExtra()]);
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
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null,
                    'field2' => null,
                    'field3' => null,
                ]
            ],
            $this->context->getSorters()
        );
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

        $this->resourceHierarchyProvider->expects($this->once())
            ->method('getParentClassNames')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['Test\ParentClass1']);

        $this->context->setExtras([new FiltersConfigExtra(), new SortersConfigExtra()]);
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
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null,
                ]
            ],
            $this->context->getSorters()
        );
    }

    public function testProcessWithInheritanceWhenParentClassIsAvailableAsStandaloneResource()
    {
        $config = [
            'fields'  => [
                'field1' => ['exclude' => true]
            ],
            'filters' => [
                'fields' => [
                    'field1' => ['exclude' => true]
                ]
            ],
            'sorters' => [
                'fields' => [
                    'field1' => ['exclude' => true]
                ]
            ]
        ];

        $parentConfig = new Config();
        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->setExcludeAll();
        $parentDefinition->addField('field1');
        $parentDefinition->addField('field2');
        $parentConfig->setDefinition($parentDefinition);
        $parentFilters = new FiltersConfig();
        $parentFilters->setExcludeAll();
        $parentFilters->addField('field1');
        $parentFilters->addField('field2');
        $parentConfig->setFilters($parentFilters);
        $parentSorters = new SortersConfig();
        $parentSorters->setExcludeAll();
        $parentSorters->addField('field1');
        $parentSorters->addField('field2');
        $parentConfig->setSorters($parentSorters);

        $this->context->setExtras([new FiltersConfigExtra(), new SortersConfigExtra()]);

        $this->resourceHierarchyProvider->expects(self::once())
            ->method('getParentClassNames')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['Test\ParentClass1', 'Test\ParentClass2', 'Test\ParentClass3', 'Test\ParentClass4']);
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceKnown')
            ->with('Test\ParentClass1', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(true);
        $this->configBag->expects(self::once())
            ->method('getConfig')
            ->with(self::TEST_CLASS_NAME, $this->context->getVersion())
            ->willReturn($config);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                'Test\ParentClass1',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getExtras()
            )
            ->willReturn($parentConfig);

        $this->context->setTargetAction('create');
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'parent_resource_class' => 'Test\ParentClass1',
                'fields'                => [
                    'field1' => ['exclude' => true],
                    'field2' => null,
                ]
            ],
            $this->context->getResult()
        );
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => ['exclude' => true],
                    'field2' => null,
                ]
            ],
            $this->context->getFilters()
        );
        $this->assertConfig(
            [
                'fields' => [
                    'field1' => ['exclude' => true],
                    'field2' => null,
                ]
            ],
            $this->context->getSorters()
        );
    }
}
