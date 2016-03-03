<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\PropertyAccess\PropertyAccessor;

/**
 * @dbIsolation
 */
class WidgetControllerTest extends WebTestCase
{
    /** @var int */
    private $entityId;

    /** @var FilesystemCache */
    protected $cacheProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->cacheProvider = $this->getContainer()->get('oro_action.cache.provider');
        $this->loadFixtures([
            'Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData',
        ]);
        $this->entityId = $this->getReference(LoadTestEntityData::TEST_ENTITY_1)->getId();
    }

    protected function tearDown()
    {
        $this->cacheProvider->delete(ActionConfigurationProvider::ROOT_NODE_NAME);
    }

    /**
     * @dataProvider buttonsActionDataProvider
     *
     * @param array $config
     * @param string $route
     * @param bool $entityId
     * @param string $entityClass
     * @param bool|string $expected
     */
    public function testButtonsAction(array $config, $route, $entityId, $entityClass, $expected)
    {
        $this->cacheProvider->save(ActionConfigurationProvider::ROOT_NODE_NAME, $config);

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
            $this->assertContains($expected, $crawler->html());
        } else {
            $this->assertEmpty($crawler);
        }
    }

    /**
     * @dataProvider formActionDataProvider
     *
     * @param array $inputData
     * @param array $submittedData
     * @param array $expectedFormData
     * @param array $expectedData
     * @param string $expectedMessage
     */
    public function testFormAction(
        array $inputData,
        array $submittedData,
        array $expectedFormData,
        array $expectedData,
        $expectedMessage
    ) {
        $this->cacheProvider->save(ActionConfigurationProvider::ROOT_NODE_NAME, $this->getConfigurationForFormAction());

        $this->assertEntityFields($inputData);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_form',
                [
                    '_widgetContainer' => 'dialog',
                    'actionName' => 'oro_action_test_action',
                    'entityId' => $this->entityId,
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
        $this->assertEntityFields($expectedData);
    }

    /**
     * @return array
     */
    public function formActionDataProvider()
    {
        return [
            'valid action' => [
                'inputData' => [
                    'message' => 'test message',
                    'description' => null
                ],
                'submittedData' => [
                    'oro_action[message_attr]' => 'new message',
                ],
                'expectedFormData' => [
                    'oro_action[message_attr]' => 'test message',
                    'oro_action[descr_attr]' => 'Test Description'
                ],
                'expectedData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'expectedMessage' => 'widget.trigger(\'formSave\', []);'
            ],
            'action not allowed' => [
                'inputData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'submittedData' => [
                    'oro_action[message_attr]' => 'new message',
                    'oro_action[descr_attr]' => 'new description text'
                ],
                'expectedFormData' => [
                    'oro_action[message_attr]' => 'new message',
                    'oro_action[descr_attr]' => ''
                ],
                'expectedData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'expectedMessage' => 'Action "oro_action_test_action" is not allowed.'
            ],
            'action not allowed (constraint message)' => [
                'inputData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'submittedData' => [
                    'oro_action[message_attr]' => 'new message text',
                    'oro_action[descr_attr]' => 'Test Description'
                ],
                'expectedFormData' => [
                    'oro_action[message_attr]' => 'new message',
                    'oro_action[descr_attr]' => ''
                ],
                'expectedData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'expectedMessage' => 'Please, write other description.'
            ],
            'action with form error' => [
                'inputData' => [
                    'message' => 'new message',
                    'description' => 'Test Description'
                ],
                'submittedData' => [
                    'oro_action[message_attr]' => '',
                    'oro_action[descr_attr]' => 'new description text'
                ],
                'expectedFormData' => [
                    'oro_action[message_attr]' => 'new message',
                    'oro_action[descr_attr]' => ''
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
     * @param array $fields
     */
    protected function assertEntityFields(array $fields)
    {
        $entity = $this->getEntity($this->entityId);

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
    public function buttonsActionDataProvider()
    {
        $label = 'oro.action.test.label';

        $config = [
            'oro_action_test_action' => [
                'label' => $label,
                'enabled' => true,
                'order' => 10,
                'applications' => ['backend', 'frontend'],
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
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            'preconditions' => ['@equal' => ['$message', 'test message']],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => $label
            ],
            'existing entity wrong conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            'preconditions' => ['@equal' => ['$message', 'test message wrong']],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => false
            ],
            'existing entity short syntax' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['OroTestFrameworkBundle:TestActivity']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => $label
            ],
            'existing entity with root namespace' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['\Oro\Bundle\TestFrameworkBundle\Entity\TestActivity']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => $label
            ],
            'unknown entity' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['Oro\Bundle\TestFrameworkBundle\Enti\UnknownEntity']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => false
            ],
            'unknown entity short syntax' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['OroTestFrameworkBundle:UnknownEntity']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => false
            ],
            'existing route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['routes' => ['oro_action_test_route']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => $label
            ],
            'unknown route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['routes' => ['oro_action_unknown_route']]]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => false
            ],
            'empty context' => [
                'config' => $config,
                'route' => 'oro_action_test_route',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => false
            ],
            'existing route and entity' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' =>
                        [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            'routes' => ['oro_action_test_route']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'entityId' => null,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'expected' => $label
            ],
            'non modal action' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' =>
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
                'expected' => 'data-dialog-show=""'
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getConfigurationForFormAction()
    {
        return [
            'oro_action_test_action' => [
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
                'prefunctions' => [],
                'preconditions' => [],
                'form_init' => [
                    ['@assign_value' => [
                        'conditions' => ['@empty' => '$description'],
                        'parameters' => ['$.descr_attr', 'Test Description'],
                    ]]
                ],
                'conditions' => [
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
                'functions' => [
                    ['@assign_value' => ['$message', '$.message_attr']],
                    ['@assign_value' => ['$description', '$.descr_attr']]
                ]
            ]
        ];
    }
}
