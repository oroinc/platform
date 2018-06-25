<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditingExtension;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class InlineEditingExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|InlineEditColumnOptionsGuesser */
    protected $guesser;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityClassNameHelper */
    protected $entityClassNameHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var InlineEditingExtension */
    protected $extension;

    public function setUp()
    {
        $this->guesser = $this->createMock(InlineEditColumnOptionsGuesser::class);
        $this->entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->extension = new InlineEditingExtension(
            $this->guesser,
            $this->entityClassNameHelper,
            $this->authorizationChecker
        );
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsApplicable()
    {
        $config = DatagridConfiguration::create([Configuration::BASE_CONFIG_KEY => ['enable' => true]]);
        $this->assertTrue($this->extension->isApplicable($config));

        $config = DatagridConfiguration::create([Configuration::BASE_CONFIG_KEY => ['enable' => false]]);
        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testIsNotApplicableInImportExportMode()
    {
        $params = new ParameterBag();
        $params->set(
            ParameterBag::DATAGRID_MODES_PARAMETER,
            [DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE]
        );
        $config = DatagridConfiguration::create([]);
        $this->extension->setParameters($params);
        self::assertFalse($this->extension->isApplicable($config));
    }

    public function testVisitMetadata()
    {
        $config = DatagridConfiguration::create([Configuration::BASE_CONFIG_KEY => ['enable' => true]]);
        $data = MetadataObject::create([]);

        $this->extension->visitMetadata($config, $data);
        $this->assertEquals(
            $config->offsetGet(Configuration::BASE_CONFIG_KEY),
            $data->offsetGet(Configuration::BASE_CONFIG_KEY)
        );
    }

    public function testProcessConfigsWithWrongConfiguration()
    {
        $config = DatagridConfiguration::create([Configuration::BASE_CONFIG_KEY => ['enable' => true]]);

        $this->expectException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');
        $this->extension->processConfigs($config);
    }

    /**
     * @param array $configValues
     * @param array $expectedValues
     * @param string $entityName
     * @dataProvider processConfigsProvider
     */
    public function testProcessConfigs(array $configValues, array $expectedValues, $entityName)
    {
        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(
                function ($permission, $object) use ($entityName) {
                    if ($object instanceof FieldVote) {
                        return !in_array($object->getField(), ['nonAvailable1', 'nonAvailable2'], true);
                    } elseif (null === $object) {
                        if ('resource1' === $permission) {
                            return false;
                        } elseif ('resource2' === $permission) {
                            return true;
                        } elseif ('EDIT;entity:' . $entityName === $permission) {
                            return true;
                        }
                    }
                    self::fail(sprintf('Unexpected isGranted call. Permission: %s', $permission));
                }
            );

        $config = DatagridConfiguration::create($configValues);

        $callback = $this->getProcessConfigsCallBack();
        $this->guesser->expects($this->any())
            ->method('getColumnOptions')
            ->will($this->returnCallback($callback));
        $this->entityClassNameHelper->expects($this->any())
            ->method('getUrlSafeClassName')
            ->willReturn('Oro_Bundle_EntityBundle_Tests_Unit_Fixtures_Stub_SomeEntity');

        $this->extension->processConfigs($config);

        $expectedResult = DatagridConfiguration::create($expectedValues);

        $key = Configuration::BASE_CONFIG_KEY;
        $this->assertEquals($config->offsetGet($key), $expectedResult->offsetGet($key));

        $key = FormatterConfiguration::COLUMNS_KEY;
        $this->assertEquals($config->offsetGet($key), $expectedResult->offsetGet($key));
    }

    /**
     * @param string $entityName
     * @return array
     */
    protected function getProcessConfigsExpectedValues($entityName)
    {
        return [
            Configuration::BASE_CONFIG_KEY => [
                'enable' => true,
                'entity_name' => $entityName,
                'behaviour' => 'enable_all',
                'save_api_accessor' => [
                    'route' => 'oro_api_patch_entity_data',
                    'http_method' => 'PATCH',
                    'default_route_parameters' =>
                        ['className' => 'Oro_Bundle_EntityBundle_Tests_Unit_Fixtures_Stub_SomeEntity'],
                    'query_parameter_names' => [],
                ],
            ],
            FormatterConfiguration::COLUMNS_KEY => [
                'testText' => [
                    'label' => 'test_text',
                    Configuration::BASE_CONFIG_KEY => ['enable' => 'true']
                ],
                'testSelect' => [
                    'label' => 'test_select',
                    PropertyInterface::FRONTEND_TYPE_KEY => 'select',
                    Configuration::BASE_CONFIG_KEY => ['enable' => 'true'],
                    'choices' => [
                        'one' => 'One',
                        'two' => 'Two',
                    ]
                ],
                'testRel' => [
                    'label' => 'test_rel',
                    PropertyInterface::FRONTEND_TYPE_KEY => 'relation',
                    Configuration::BASE_CONFIG_KEY => [
                        'enable' => 'true',
                        'editor' => [
                            'view_options' => [
                                'value_field_name' => 'owner'
                            ]
                        ],
                        'autocomplete_api_accessor' => [
                            'class' => 'orouser/js/tools/acl-users-search-api-accessor',
                            'permission_check_entity_name' => 'Oro_Bundle_TestBundle_Entity_Test'
                        ]
                    ]
                ],
                'testAnotherText' => [
                    'label' => 'test_config_overwrite',
                    'inline_editing' => ['enable' => false]
                ],
                'id' => ['label' => 'test_black_list'],
                'updatedAt' => ['label' => 'test_black_list'],
                'createdAt' => ['label' => 'test_black_list'],
                'nonAvailable1' => [
                    'label' => 'nonAvailable1'
                ],
                'nonAvailable2' => [
                    'label' => 'nonAvailable2',
                    'inline_editing' => ['enable' => false]
                ]
            ]
        ];
    }

    protected function getProcessConfigsCallBack()
    {
        return function ($columnName, $entity, $column) {
            switch ($columnName) {
                case 'testText':
                case 'testAnotherText':
                case 'id':
                case 'updatedAt':
                case 'createdAt':
                    return [Configuration::BASE_CONFIG_KEY => ['enable' => 'true']];
                case 'testSelect':
                    return [
                        Configuration::BASE_CONFIG_KEY => ['enable' => 'true'],
                        PropertyInterface::FRONTEND_TYPE_KEY => 'select',
                        'choices' => [
                            'one' => 'One',
                            'two' => 'Two',
                        ]
                    ];
                case 'testRel':
                    return [
                        Configuration::BASE_CONFIG_KEY => [],
                        PropertyInterface::FRONTEND_TYPE_KEY => 'relation'
                    ];
                case 'nonAvailable1':
                case 'nonAvailable2':
                    return [Configuration::BASE_CONFIG_KEY => ['enable' => 'true']];
            }

            return [];
        };
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processConfigsProvider()
    {
        $entityName = 'Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub\SomeEntity';

        $expectedValues = $this->getProcessConfigsExpectedValues($entityName);

        return [
            'with entity_name and ACL resource1' => [
                [
                    Configuration::BASE_CONFIG_KEY => [
                        'enable' => true,
                        'entity_name' => $entityName,
                        'acl_resource' => 'resource1',
                    ],
                    FormatterConfiguration::COLUMNS_KEY => [
                        'testText' => ['label' => 'test_text'],
                        'testSelect' => [
                            'label' => 'test_select',
                            PropertyInterface::FRONTEND_TYPE_KEY => 'string',
                        ],
                        'testRel' => [
                            'label' => 'test_rel',
                            PropertyInterface::FRONTEND_TYPE_KEY => 'relation',
                            Configuration::BASE_CONFIG_KEY => [
                                'enable' => 'true',
                                'editor' => [
                                    'view_options' => [
                                        'value_field_name' => 'owner'
                                    ]
                                ],
                                'autocomplete_api_accessor' => [
                                    'class' => 'orouser/js/tools/acl-users-search-api-accessor',
                                    'permission_check_entity_name' => 'Oro_Bundle_TestBundle_Entity_Test'
                                ]
                            ],
                        ],
                        'testAnotherText' => [
                            'label' => 'test_config_overwrite',
                            'inline_editing' => ['enable' => false]
                        ],
                        'id' => ['label' => 'test_black_list'],
                        'updatedAt' => ['label' => 'test_black_list'],
                        'createdAt' => ['label' => 'test_black_list'],
                        'nonAvailable1' => ['label' => 'nonAvailable1'],
                        'nonAvailable2' => [
                            'label' => 'nonAvailable2',
                            'inline_editing' => ['enable' => true]
                        ]
                    ]
                ],
                [
                    Configuration::BASE_CONFIG_KEY => array_merge(
                        $expectedValues[Configuration::BASE_CONFIG_KEY],
                        [
                            Configuration::CONFIG_ACL_KEY => 'resource1',
                            Configuration::CONFIG_ENABLE_KEY => false,
                        ]
                    ),
                    FormatterConfiguration::COLUMNS_KEY => $expectedValues[FormatterConfiguration::COLUMNS_KEY],
                ],
                $entityName
            ],
            'with entity_name and ACL resource2' => [
                [
                    Configuration::BASE_CONFIG_KEY => [
                        'enable' => true,
                        'entity_name' => $entityName,
                        'acl_resource' => 'resource2',
                    ],
                    FormatterConfiguration::COLUMNS_KEY => [
                        'testText' => ['label' => 'test_text'],
                        'testSelect' => [
                            'label' => 'test_select',
                            PropertyInterface::FRONTEND_TYPE_KEY => 'string',
                        ],
                        'testRel' => [
                            'label' => 'test_rel',
                            PropertyInterface::FRONTEND_TYPE_KEY => 'relation',
                            Configuration::BASE_CONFIG_KEY => [
                                'enable' => 'true',
                                'editor' => [
                                    'view_options' => [
                                        'value_field_name' => 'owner'
                                    ]
                                ],
                                'autocomplete_api_accessor' => [
                                    'class' => 'orouser/js/tools/acl-users-search-api-accessor',
                                    'permission_check_entity_name' => 'Oro_Bundle_TestBundle_Entity_Test'
                                ]
                            ],
                        ],
                        'testAnotherText' => [
                            'label' => 'test_config_overwrite',
                            'inline_editing' => ['enable' => false]
                        ],
                        'id' => ['label' => 'test_black_list'],
                        'updatedAt' => ['label' => 'test_black_list'],
                        'createdAt' => ['label' => 'test_black_list'],
                        'nonAvailable1' => ['label' => 'nonAvailable1'],
                        'nonAvailable2' => [
                            'label' => 'nonAvailable2',
                            'inline_editing' => ['enable' => true]
                        ]
                    ]
                ],
                [
                    Configuration::BASE_CONFIG_KEY => array_merge(
                        $expectedValues[Configuration::BASE_CONFIG_KEY],
                        [
                            Configuration::CONFIG_ACL_KEY => 'resource2',
                        ]
                    ),
                    FormatterConfiguration::COLUMNS_KEY => $expectedValues[FormatterConfiguration::COLUMNS_KEY],
                ],
                $entityName
            ],
            'without entity_name' => [
                [
                    'extended_entity_name' => $entityName,
                    Configuration::BASE_CONFIG_KEY => [
                        'enable' => true,
                    ],
                    FormatterConfiguration::COLUMNS_KEY => [
                        'testText' => ['label' => 'test_text'],
                        'testSelect' => [
                            'label' => 'test_select',
                            PropertyInterface::FRONTEND_TYPE_KEY => 'string',
                        ],
                        'testRel' => [
                            'label' => 'test_rel',
                            PropertyInterface::FRONTEND_TYPE_KEY => 'relation',
                            Configuration::BASE_CONFIG_KEY => [
                                'enable' => 'true',
                                'editor' => [
                                    'view_options' => [
                                        'value_field_name' => 'owner'
                                    ]
                                ],
                                'autocomplete_api_accessor' => [
                                    'class' => 'orouser/js/tools/acl-users-search-api-accessor',
                                    'permission_check_entity_name' => 'Oro_Bundle_TestBundle_Entity_Test'
                                ]
                            ],
                        ],
                        'testAnotherText' => [
                            'label' => 'test_config_overwrite',
                            'inline_editing' => ['enable' => false]
                        ],
                        'id' => ['label' => 'test_black_list'],
                        'updatedAt' => ['label' => 'test_black_list'],
                        'createdAt' => ['label' => 'test_black_list'],
                        'nonAvailable1' => ['label' => 'nonAvailable1'],
                        'nonAvailable2' => [
                            'label' => 'nonAvailable2',
                            'inline_editing' => ['enable' => true]
                        ]
                    ]
                ],
                $this->getProcessConfigsExpectedValues($entityName),
                $entityName
            ],
            'entity_name & extended_entity_name' => [
                [
                    'extended_entity_name' => $entityName . '_test',
                    Configuration::BASE_CONFIG_KEY => [
                        'enable' => true,
                        'entity_name' => $entityName,
                    ],
                    FormatterConfiguration::COLUMNS_KEY => [
                        'testText' => ['label' => 'test_text'],
                        'testSelect' => [
                            'label' => 'test_select',
                            PropertyInterface::FRONTEND_TYPE_KEY => 'string',
                        ],
                        'testRel' => [
                            'label' => 'test_rel',
                            PropertyInterface::FRONTEND_TYPE_KEY => 'relation',
                            Configuration::BASE_CONFIG_KEY => [
                                'enable' => 'true',
                                'editor' => [
                                    'view_options' => [
                                        'value_field_name' => 'owner'
                                    ]
                                ],
                                'autocomplete_api_accessor' => [
                                    'class' => 'orouser/js/tools/acl-users-search-api-accessor',
                                    'permission_check_entity_name' => 'Oro_Bundle_TestBundle_Entity_Test'
                                ]
                            ],
                        ],
                        'testAnotherText' => [
                            'label' => 'test_config_overwrite',
                            'inline_editing' => ['enable' => false]
                        ],
                        'id' => ['label' => 'test_black_list'],
                        'updatedAt' => ['label' => 'test_black_list'],
                        'createdAt' => ['label' => 'test_black_list'],
                        'nonAvailable1' => ['label' => 'nonAvailable1'],
                        'nonAvailable2' => [
                            'label' => 'nonAvailable2',
                            'inline_editing' => ['enable' => true]
                        ]
                    ]
                ],
                $this->getProcessConfigsExpectedValues($entityName),
                $entityName
            ]
        ];
    }
}
