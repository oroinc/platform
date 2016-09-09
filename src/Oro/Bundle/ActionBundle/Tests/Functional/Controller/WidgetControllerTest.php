<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller;

use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems;

use Oro\Component\PropertyAccess\PropertyAccessor;

/**
 * @dbIsolation
 */
class WidgetControllerTest extends WebTestCase
{
    const ROOT_NODE_NAME = 'operations';

    /** @var int */
    private $entityId;

    /** @var FilesystemCache */
    protected $cacheProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->cacheProvider = $this->getContainer()->get('oro_action.cache.provider.operations');
        $this->loadFixtures([
            'Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData',
            'Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems',
        ]);
        $this->entityId = $this->getReference(LoadTestEntityData::TEST_ENTITY_1)->getId();
    }

    protected function tearDown()
    {
        $this->cacheProvider->delete(self::ROOT_NODE_NAME);

        parent::tearDown();
    }

    /**
     * @dataProvider buttonsOperationDataProvider
     *
     * @param array $config
     * @param string $route
     * @param bool $entityId
     * @param string $entityClass
     * @param array $expected
     */
    public function testButtonsOperation(array $config, $route, $entityId, $entityClass, array $expected)
    {
        $this->cacheProvider->save(self::ROOT_NODE_NAME, $config);

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
                $this->assertContains($item, $crawler->html());
            }
        } else {
            $this->assertEmpty($crawler);
        }
    }

    /**
     * @dataProvider formOperationDataProvider
     *
     * @param string $entity
     * @param array $inputData
     * @param array $submittedData
     * @param array $expectedFormData
     * @param array $expectedData
     * @param string $expectedMessage
     */
    public function testFormOperation(
        $entity,
        array $inputData,
        array $submittedData,
        array $expectedFormData,
        array $expectedData,
        $expectedMessage
    ) {
        $this->cacheProvider->save(self::ROOT_NODE_NAME, $this->getConfigurationForFormOperation());

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
                    'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
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

        $this->assertContains($expectedMessage, $crawler->html());
        $this->assertEntityFields($entity, $expectedData);
    }

    /**
     * @param string $groupName
     * @param array $actions
     *
     * @dataProvider buttonsOperationAndGroupsProvider
     */
    public function testButtonsOperationAndGroups($groupName, array $actions)
    {
        $item = $this->getReference(LoadItems::ITEM1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_buttons',
                [
                    '_widgetContainer' => 'dialog',
                    'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Item',
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

    /**
     * @return array
     */
    public function formOperationDataProvider()
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
                'expectedMessage' => 'widget.trigger(\'formSave\', {"success":true});'
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

    /**
     * @param object $entity
     * @param array $fields
     */
    protected function assertEntityFields($entity, array $fields)
    {
        $entity = $this->getEntity($entity->getId());

        foreach ($fields as $name => $value) {
            $this->assertEquals($value, $this->getPropertyAccessor()->getValue($entity, $name));
        }
    }

    /**
     * @param int $id
     * @return object|null
     */
    protected function getEntity($id)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('Oro\Bundle\TestFrameworkBundle\Entity\TestActivity')
            ->find($id);
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buttonsOperationDataProvider()
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

        return [
            'existing entity right conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_operation' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            OperationDefinition::PRECONDITIONS => ['@equal' => ['$message', 'test message']],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => [$label]
            ],
            'existing entity wrong conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_operation' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            OperationDefinition::PRECONDITIONS => ['@equal' => ['$message', 'test message wrong']],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => []
            ],
            'existing entity short syntax' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' => ['entities' => ['OroTestFrameworkBundle:TestActivity']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => [$label]
            ],
            'existing entity with root namespace' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_operation' => [
                            'entities' => ['\Oro\Bundle\TestFrameworkBundle\Entity\TestActivity']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => [$label]
            ],
            'unknown entity' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_operation' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Enti\UnknownEntity']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => []
            ],
            'unknown entity short syntax' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' => ['entities' => ['OroTestFrameworkBundle:UnknownEntity']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => []
            ],
            'existing route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' => ['routes' => ['oro_action_test_route']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => [$label]
            ],
            'unknown route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' => ['routes' => ['oro_action_unknown_route']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => []
            ],
            'empty context' => [
                'config' => $config,
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => []
            ],
            'existing route and entity' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' =>
                        [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            'routes' => ['oro_action_test_route']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => null,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => [$label]
            ],
            'modal action' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' =>
                        [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            'routes' => ['oro_action_test_route'],
                            'frontend_options' => ['show_dialog' => false],
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => null,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => ['"showDialog":false', '"hasDialog":false'],
            ],
            'non modal action' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_operation' =>
                        [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
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
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
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
    }

    /**
     * @return array
     */
    protected function getConfigurationForFormOperation()
    {
        return [
            'oro_action_test_operation' => [
                'label' => 'oro.action.test.label',
                'enabled' => true,
                'order' => 10,
                'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                'routes' => [],
                'frontend_options' => ['show_dialog' => true],
                'attributes' => [
                    'message_attr' => ['label' => 'Message', 'type' => 'string'],
                    'descr_attr' => ['property_path' => 'data.description']
                ],
                'form_options' => [
                    'attribute_fields' => [
                        'message_attr' => [
                            'form_type' => 'text',
                            'options' => ['required' => true, 'constraints' => [['NotBlank' => []]]]
                        ],
                        'descr_attr' => [
                            'form_type' => 'text'
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

    /**
     * @return array
     */
    public function buttonsOperationAndGroupsProvider()
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
}
