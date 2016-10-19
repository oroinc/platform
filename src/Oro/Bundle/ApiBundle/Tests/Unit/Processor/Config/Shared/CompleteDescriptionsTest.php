<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\MarkdownApiDocParser;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProviderInterface;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDescriptions;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;

class CompleteDescriptionsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityDescriptionProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourceDocProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $apiDocParser;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var ConfigProviderMock */
    protected $ownershipConfigProvider;

    /** @var CompleteDescriptions */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->entityDescriptionProvider = $this->getMockBuilder(EntityDescriptionProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceDocProvider = $this->getMock(ResourceDocProviderInterface::class);
        $this->apiDocParser = $this->getMockBuilder(MarkdownApiDocParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMock(TranslatorInterface::class);

        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ownershipConfigProvider = new ConfigProviderMock($configManager, 'ownership');

        $this->processor = new CompleteDescriptions(
            $this->entityDescriptionProvider,
            $this->resourceDocProvider,
            $this->apiDocParser,
            $this->translator,
            $this->ownershipConfigProvider
        );
    }

    public function testWithoutTargetAction()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'     => null,
                'field1' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'     => null,
                    'field1' => null,
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForIdentifierField()
    {
        $config = [
            'identifier_field_names' => ['id'],
            'exclusion_policy'       => 'all',
            'fields'                 => [
                'id'     => null,
                'field1' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'exclusion_policy'       => 'all',
                'fields'                 => [
                    'id'     => [
                        'description' => 'The identifier of an entity'
                    ],
                    'field1' => null,
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForIdentifierFieldWhenItAlreadyHasDescription()
    {
        $config = [
            'identifier_field_names' => ['id'],
            'exclusion_policy'       => 'all',
            'fields'                 => [
                'id'     => [
                    'description' => 'existing description'
                ],
                'field1' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'exclusion_policy'       => 'all',
                'fields'                 => [
                    'id'     => [
                        'description' => 'existing description'
                    ],
                    'field1' => null,
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForRenamedIdentifierField()
    {
        $config = [
            'identifier_field_names' => ['id1'],
            'exclusion_policy'       => 'all',
            'fields'                 => [
                'id'  => null,
                'id1' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id1'],
                'exclusion_policy'       => 'all',
                'fields'                 => [
                    'id'  => null,
                    'id1' => [
                        'description' => 'The identifier of an entity'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForCompositeIdentifierField()
    {
        $config = [
            'identifier_field_names' => ['id1', 'id2'],
            'exclusion_policy'       => 'all',
            'fields'                 => [
                'id'  => null,
                'id1' => null,
                'id2' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id1', 'id2'],
                'exclusion_policy'       => 'all',
                'fields'                 => [
                    'id'  => null,
                    'id1' => null,
                    'id2' => null,
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testWithoutIdentifierField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'     => null,
                'field1' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'     => null,
                    'field1' => null,
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForCreatedAtField()
    {
        $config = [
            'fields' => [
                'id'        => null,
                'createdAt' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id'        => null,
                    'createdAt' => [
                        'description' => 'The date and time of resource record creation'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForCreatedAtFieldWhenItAlreadyHasDescription()
    {
        $config = [
            'fields' => [
                'id'        => null,
                'createdAt' => [
                    'description' => 'existing description'
                ],
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id'        => null,
                    'createdAt' => [
                        'description' => 'existing description'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForUpdatedAtField()
    {
        $config = [
            'fields' => [
                'id'        => null,
                'created'   => null,
                'updatedAt' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id'        => null,
                    'created'   => null,
                    'updatedAt' => [
                        'description' => 'The date and time of the last update of the resource record'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForUpdatedAtFieldWhenItAlreadyHasDescription()
    {
        $config = [
            'fields' => [
                'id'        => null,
                'updatedAt' => [
                    'description' => 'existing description'
                ],
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id'        => null,
                    'updatedAt' => [
                        'description' => 'existing description'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testOwnershipFieldsForNonConfigurableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()
        );
    }

    public function testOwnershipFieldsWithoutConfiguration()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->ownershipConfigProvider->addEntityConfig(
            self::TEST_CLASS_NAME,
            []
        );

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()
        );
    }

    public function testForOwnerField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->ownershipConfigProvider->addEntityConfig(
            self::TEST_CLASS_NAME,
            ['owner_field_name' => 'owner']
        );

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'owner'        => [
                        'description' => 'An Owner record represents the ownership capabilities of the record'
                    ],
                    'organization' => null,
                ]

            ],
            $this->context->getResult()
        );
    }

    public function testForOrganizationField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->ownershipConfigProvider->addEntityConfig(
            self::TEST_CLASS_NAME,
            ['organization_field_name' => 'organization']
        );

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'owner'        => null,
                    'organization' => [
                        'description' => 'An Organization record represents a real enterprise, business, firm, '
                            . 'company or another organization, to which the record belongs'
                    ]
                ]

            ],
            $this->context->getResult()
        );
    }

    public function testForRenamedOwnerField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'owner2'       => ['property_path' => 'owner1'],
                'organization' => null,
            ]
        ];

        $this->ownershipConfigProvider->addEntityConfig(
            self::TEST_CLASS_NAME,
            ['owner_field_name' => 'owner1']
        );

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'owner2'       => [
                        'property_path' => 'owner1',
                        'description'   => 'An Owner record represents the ownership capabilities of the record'
                    ],
                    'organization' => null,
                ]

            ],
            $this->context->getResult()
        );
    }

    public function testForRenamedOrganizationField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'owner'         => null,
                'organization2' => ['property_path' => 'organization1'],
            ]
        ];

        $this->ownershipConfigProvider->addEntityConfig(
            self::TEST_CLASS_NAME,
            ['organization_field_name' => 'organization1']
        );

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'owner'         => null,
                    'organization2' => [
                        'property_path' => 'organization1',
                        'description'   => 'An Organization record represents a real enterprise, business, firm, '
                            . 'company or another organization, to which the record belongs'
                    ]
                ]

            ],
            $this->context->getResult()
        );
    }
}
