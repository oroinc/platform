<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue as EnumEntity;
use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\MarkdownApiDocParser;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProviderInterface;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDescriptions;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestDependedTextProcessor;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
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
        $this->resourceDocProvider = $this->createMock(ResourceDocProviderInterface::class);
        $this->apiDocParser = $this->getMockBuilder(MarkdownApiDocParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->createMock(TranslatorInterface::class);

        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ownershipConfigProvider = new ConfigProviderMock($configManager, 'ownership');

        $this->processor = new CompleteDescriptions(
            $this->entityDescriptionProvider,
            $this->resourceDocProvider,
            $this->apiDocParser,
            $this->translator,
            $this->ownershipConfigProvider,
            new RequestDependedTextProcessor(new RequestExpressionMatcher())
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
                        'description' =>  CompleteDescriptions::ID_DESCRIPTION
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
                        'description' => CompleteDescriptions::ID_DESCRIPTION
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
                        'description' => CompleteDescriptions::CREATED_AT_DESCRIPTION
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
                        'description' => CompleteDescriptions::UPDATED_AT_DESCRIPTION
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
                        'description' => CompleteDescriptions::OWNER_DESCRIPTION
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
                        'description' => CompleteDescriptions::ORGANIZATION_DESCRIPTION
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
                        'description'   => CompleteDescriptions::OWNER_DESCRIPTION
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
                        'description'   => CompleteDescriptions::ORGANIZATION_DESCRIPTION
                    ]
                ]

            ],
            $this->context->getResult()
        );
    }

    public function testEnumField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'name'     => null,
                'default'  => null,
                'priority' => null,
            ]
        ];

        $this->context->setClassName(EnumEntity::class);
        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'name'     => [
                        'description' => CompleteDescriptions::ENUM_NAME_DESCRIPTION
                    ],
                    'default'  => [
                        'description' => CompleteDescriptions::ENUM_DEFAULT_DESCRIPTION
                    ],
                    'priority' => [
                        'description' => CompleteDescriptions::ENUM_PRIORITY_DESCRIPTION
                    ],
                ]

            ],
            $this->context->getResult()
        );
    }

    public function testProcessRequestDependedContentForEntityDocumentation()
    {
        $config = [
            'exclusion_policy' => 'all',
            'documentation'    => '{@request:json_api}JSON API{@/request}{@request:rest}REST{@/request}'
        ];

        $this->context->getRequestType()->set(new RequestType([RequestType::JSON_API]));
        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'documentation'    => 'JSON API'
            ],
            $this->context->getResult()
        );
    }

    public function testProcessRequestDependedContentForFieldDescription()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'description' => '{@request:json_api}JSON API{@/request}{@request:rest}REST{@/request}'
                ]
            ]
        ];

        $this->context->getRequestType()->set(new RequestType([RequestType::JSON_API]));
        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1' => [
                        'description' => 'JSON API'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessRequestDependedContentForFilterDescription()
    {
        $filters = new FiltersConfig();
        $filter1 = $filters->addField('field1');
        $filter1->setDescription('{@request:json_api}JSON API{@/request}{@request:rest}REST{@/request}');

        $this->context->getRequestType()->set(new RequestType([RequestType::JSON_API]));
        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject([]));
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        $this->assertEquals(
            'JSON API',
            $filter1->getDescription()
        );
    }
}
