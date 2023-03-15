<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData;
use Oro\Bundle\ActionBundle\Tests\Functional\Stub\ButtonProviderExtensionStub;
use Oro\Bundle\ActionBundle\Tests\Functional\Stub\ButtonStub;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Provider\PhpArrayConfigCacheModifier;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class WidgetControllerTest extends WebTestCase
{
    /** @var int */
    private $entityId;

    /** @var ConfigurationProvider */
    private $configProvider;

    /** @var PhpArrayConfigCacheModifier */
    private $configModifier;

    protected ?PropertyAccessorInterface $propertyAccessor = null;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->configProvider = $this->getContainer()->get('oro_action.tests.configuration.provider');
        $this->configModifier = new PhpArrayConfigCacheModifier($this->configProvider);

        $this->loadFixtures([LoadTestEntityData::class, LoadItems::class]);
        $this->entityId = $this->getReference(LoadTestEntityData::TEST_ENTITY_1)->getId();
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('oro_action.tests.provider.button.extension')
            ->setDecoratedExtension(null);
        $this->configModifier->resetCache();
    }

    /**
     * @dataProvider buttonsOperationDataProvider
     */
    public function testButtonsOperation(
        array $config,
        string $route,
        ?bool $entityId,
        string $entityClass,
        array $expected
    ) {
        $this->setOperationsConfig($config);
        $this->getContainer()->get('oro_action.tests.provider.button.extension')
            ->setDecoratedExtension(new ButtonProviderExtensionStub());

        if ($entityId) {
            $entityId = $this->entityId;
        }

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_buttons',
                [
                    '_widgetContainer' => 'dialog',
                    'route' => $route,
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                ]
            )
        );

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        if ($expected) {
            foreach ($expected as $item) {
                self::assertStringContainsString($item, $crawler->html());
            }
        } else {
            $this->assertEmpty($crawler);
        }
    }

    /**
     * @dataProvider formOperationDataProvider
     */
    public function testFormOperation(
        string $entity,
        array $inputData,
        array $submittedData,
        array $expectedFormData,
        array $expectedData,
        string $expectedMessage
    ) {
        $this->setOperationsConfig($this->getConfigurationForFormOperation());

        $entity = $this->getReference($entity);

        $this->assertEntityFields($entity, $inputData);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_form',
                [
                    '_wid' => 'test-uuid',
                    '_widgetContainer' => 'dialog',
                    'operationName' => 'oro_action_test_operation',
                    'entityId' => $entity->getId(),
                    'entityClass' => TestActivity::class,
                ]
            )
        );

        $form = $crawler->selectButton('Submit')->form();
        foreach ($expectedFormData as $name => $value) {
            $this->assertEquals($value, $form->offsetGet($name)->getValue());
        }
        foreach ($submittedData as $name => $value) {
            $form->offsetSet($name, $value);
        }

        $crawler = $this->client->submit($form);

        self::assertStringContainsString($expectedMessage, $crawler->html());
        $this->assertEntityFields($entity, $expectedData);
    }

    /**
     * @dataProvider buttonsOperationAndGroupsProvider
     */
    public function testButtonsOperationAndGroups(string $groupName, array $actions)
    {
        $item = $this->getReference(LoadItems::ITEM1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_buttons',
                [
                    '_widgetContainer' => 'dialog',
                    'entityClass' => Item::class,
                    'entityId' => $item->getId(),
                    'group' => $groupName
                ]
            )
        );

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        if ($actions) {
            foreach ($actions as $action) {
                $this->assertNotEmpty($crawler->selectLink($action));
            }
        } else {
            $this->assertEmpty($crawler);
        }
    }

    public function formOperationDataProvider(): array
    {
        return [
            'valid operation' => [
                'entity' => LoadTestEntityData::TEST_ENTITY_1,
                'inputData' => [
                    'message' => 'test message',
                    'description' => null
                ],
                'submittedData' => [
                    'oro_action_operation[message_attr]' => 'new message',
                ],
                'expectedFormData' => [
                    'oro_action_operation[message_attr]' => 'test message',
                    'oro_action_operation[descr_attr]' => 'Test Description'
                ],
                'expectedData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'expectedMessage' => json_encode([
                    'widget' => [
                        'trigger' => [
                            [
                                'eventBroker' => 'widget',
                                'name' => 'formSave',
                                'args' => [
                                    [
                                        'success' => true,
                                        'pageReload' => true
                                    ]
                                ]
                            ]
                        ]
                    ]
                ], JSON_THROW_ON_ERROR)
            ],
            'operation not allowed' => [
                'entity' => LoadTestEntityData::TEST_ENTITY_2,
                'inputData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'submittedData' => [
                    'oro_action_operation[message_attr]' => 'new message',
                    'oro_action_operation[descr_attr]' => 'new description text'
                ],
                'expectedFormData' => [
                    'oro_action_operation[message_attr]' => 'new message',
                    'oro_action_operation[descr_attr]' => ''
                ],
                'expectedData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'expectedMessage' => 'Operation "oro_action_test_operation" is not allowed.'
            ],
            'operation not allowed (constraint message)' => [
                'entity' => LoadTestEntityData::TEST_ENTITY_2,
                'inputData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'submittedData' => [
                    'oro_action_operation[message_attr]' => 'new message text',
                    'oro_action_operation[descr_attr]' => 'Test Description'
                ],
                'expectedFormData' => [
                    'oro_action_operation[message_attr]' => 'new message',
                    'oro_action_operation[descr_attr]' => ''
                ],
                'expectedData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'expectedMessage' => 'Please, write other description.'
            ],
            'operation with form error' => [
                'entity' => LoadTestEntityData::TEST_ENTITY_2,
                'inputData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'submittedData' => [
                    'oro_action_operation[message_attr]' => '',
                    'oro_action_operation[descr_attr]' => 'new description text'
                ],
                'expectedFormData' => [
                    'oro_action_operation[message_attr]' => 'new message',
                    'oro_action_operation[descr_attr]' => ''
                ],
                'expectedData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'expectedMessage' => 'This value should not be blank.'
            ]
        ];
    }

    private function assertEntityFields(object $entity, array $fields): void
    {
        $entity = $this->getEntity($entity->getId());

        foreach ($fields as $name => $value) {
            $this->assertEquals($value, $this->getPropertyAccessor()->getValue($entity, $name));
        }
    }

    private function getEntity(int $id): ?TestActivity
    {
        return $this->getContainer()->get('doctrine')->getRepository(TestActivity::class)
            ->find($id);
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buttonsOperationDataProvider(): array
    {
        $label = 'oro.action.test.label';

        $config = [
            'oro_action_test_operation' => [
                'label' => $label,
                'enabled' => true,
                'order' => 10,
                'applications' => ['default', 'test'],
                'frontend_options' => [],
                'entities' => [],
                'routes' => [],
            ]
        ];

        $configuration = [
            'existing entity right conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_operation' => [
                            'entities' => [TestActivity::class],
                            OperationDefinition::PRECONDITIONS => ['@equal' => ['$message', 'test message']],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => TestActivity::class,
                'expected' => [$label, ButtonStub::LABEL]
            ],
            'existing entity wrong conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_operation' => [
                            'entities' => [TestActivity::class],
                            OperationDefinition::PRECONDITIONS => ['@equal' => ['$message', 'test message wrong']],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => TestActivity::class,
                'expected' => []
            ],
            'existing entity short syntax' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' => ['entities' => [TestActivity::class]]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => TestActivity::class,
                'expected' => [$label]
            ],
            'existing entity with root namespace' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_operation' => [
                            'entities' => [TestActivity::class]
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => TestActivity::class,
                'expected' => [$label]
            ],
            'unknown entity' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_operation' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\UnknownEntity']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => TestActivity::class,
                'expected' => []
            ],
            'unknown entity short syntax' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_operation' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\UnknownEntity']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => TestActivity::class,
                'expected' => []
            ],
            'existing route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' => ['routes' => ['oro_action_test_route']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => TestActivity::class,
                'expected' => [$label]
            ],
            'unknown route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' => ['routes' => ['oro_action_unknown_route']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => TestActivity::class,
                'expected' => []
            ],
            'empty context' => [
                'config' => $config,
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => TestActivity::class,
                'expected' => []
            ],
            'existing route and entity' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' =>
                        [
                            'entities' => [TestActivity::class],
                            'routes' => ['oro_action_test_route']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => null,
                'entityClass' => TestActivity::class,
                'expected' => [$label]
            ],
            'modal action' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' =>
                        [
                            'entities' => [TestActivity::class],
                            'routes' => ['oro_action_test_route'],
                            'frontend_options' => ['show_dialog' => false],
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => null,
                'entityClass' => TestActivity::class,
                'expected' => ['"showDialog":false', '"hasDialog":false'],
            ],
            'non modal action' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' =>
                        [
                            'entities' => [TestActivity::class],
                            'routes' => ['oro_action_test_route'],
                            'frontend_options' => ['show_dialog' => true],
                            'form_options' => [
                                'attribute_fields' => [
                                    'attribute1' => 'value1',
                                ],
                            ],
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => null,
                'entityClass' => TestActivity::class,
                'expected' => [
                    'data-options',
                    '"showDialog":true',
                    '"hasDialog":true',
                    '"dialogOptions"',
                    '"executionUrl"',
                    '"dialogUrl"',
                    '"title":"' . $label . '"'
                ],
            ]
        ];

        return array_map(
            function ($item) {
                $item['config'] = array_map(
                    function ($config) {
                        return array_merge(
                            [
                                'enabled' => true,
                                'applications' => [],
                                'groups' => [],
                                'entities' => [],
                                'exclude_entities' => [],
                                'for_all_entities' => false,
                                'routes' => [],
                                'datagrids' => [],
                                'exclude_datagrids' => [],
                                'for_all_datagrids' => false
                            ],
                            $config
                        );
                    },
                    $item['config']
                );

                return $item;
            },
            $configuration
        );
    }

    private function getConfigurationForFormOperation(): array
    {
        return [
            'oro_action_test_operation' => [
                'label' => 'oro.action.test.label',
                'enabled' => true,
                'order' => 10,
                'entities' => [TestActivity::class],
                'routes' => [],
                'frontend_options' => ['show_dialog' => true],
                'attributes' => [
                    'message_attr' => ['label' => 'Message', 'type' => 'string'],
                    'descr_attr' => ['label' => 'Description', 'type' => 'string']
                ],
                'form_options' => [
                    'attribute_fields' => [
                        'message_attr' => [
                            'form_type' => TextType::class,
                            'options' => ['required' => true, 'constraints' => [['NotBlank' => []]]]
                        ],
                        'descr_attr' => [
                            'form_type' => TextType::class
                        ]
                    ],
                    'attribute_default_values' => ['message_attr' => '$message']
                ],
                OperationDefinition::PREACTIONS => [],
                OperationDefinition::PRECONDITIONS => [],
                OperationDefinition::FORM_INIT => [
                    ['@assign_value' => [
                        OperationDefinition::CONDITIONS => ['@empty' => '$description'],
                        'parameters' => ['$.descr_attr', 'Test Description'],
                    ]]
                ],
                OperationDefinition::CONDITIONS => [
                    '@and' => [
                        [
                            '@not' => [['@equal' => ['$message', '$.message_attr']]]
                        ],
                        [
                            '@not' => [
                                'parameters' => [['@equal' => ['$description', '$.descr_attr']]],
                                'message' => 'Please, write other description.'
                            ]
                        ]
                    ]
                ],
                OperationDefinition::ACTIONS => [
                    ['@assign_value' => ['$message', '$.message_attr']],
                    ['@assign_value' => ['$description', '$.descr_attr']]
                ]
            ]
        ];
    }

    public function buttonsOperationAndGroupsProvider(): array
    {
        return [
            'default group' => [
                'group' => '',
                OperationDefinition::ACTIONS => [],
            ],
            'view_navButtons' => [
                'group' => 'view_navButtons',
                OperationDefinition::ACTIONS => ['Edit', 'Delete'],
            ],
            'update_navButtons' => [
                'group' => 'update_navButtons',
                OperationDefinition::ACTIONS => ['Delete'],
            ],
            'datagridRowAction' => [
                'group' => 'datagridRowAction',
                OperationDefinition::ACTIONS => ['Edit', 'Delete'],
            ],
        ];
    }

    private function setOperationsConfig(array $operations): void
    {
        $config = $this->configProvider->getConfiguration();
        $config['operations'] = $operations;
        $this->configModifier->updateCache($config);
    }
}
