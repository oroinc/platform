<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller;

use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AjaxControllerTest extends WebTestCase
{
    const ROOT_NODE_NAME = 'operations';

    const MESSAGE_DEFAULT = 'test message';
    const MESSAGE_NEW = 'new test message';

    /** @var TestActivity */
    private $entity;

    /** @var FilesystemCache */
    protected $cacheProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->cacheProvider = $this->getContainer()->get('oro_action.cache.provider.operations');
        $this->loadFixtures([
            'Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData',
        ]);

        $this->entity = $this->getReference(LoadTestEntityData::TEST_ENTITY_1)
            ->setMessage(self::MESSAGE_DEFAULT);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->cacheProvider->delete(self::ROOT_NODE_NAME);

        parent::tearDown();
    }

    /**
     * @dataProvider executeActionDataProvider
     *
     * @param array $config
     * @param string $route
     * @param string $datagrid
     * @param bool $entityId
     * @param string $entityClass
     * @param int $statusCode
     * @param string $message
     * @param string $redirectRoute
     * @param array $flashMessages
     * @param array $headers
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testExecuteAction(
        array $config,
        $route,
        $datagrid,
        $entityId,
        $entityClass,
        $statusCode,
        $message,
        $redirectRoute = '',
        array $flashMessages = [],
        array $headers = ['HTTP_X-Requested-With' => 'XMLHttpRequest']
    ) {
        $this->cacheProvider->save(self::ROOT_NODE_NAME, $config);

        $this->assertEquals(self::MESSAGE_DEFAULT, $this->entity->getMessage());

        $operationName = 'oro_action_test_action';
        $entityId = $entityId ? $this->entity->getId() : null;
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => $route,
                    'datagrid' => $datagrid,
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                ]
            ),
            $this->getOperationExecuteParams($operationName, $entityId, $entityClass, $datagrid),
            [],
            $headers
        );

        $result = $this->client->getResponse();

        $this->assertEquals($message, $this->entity->getMessage());
        $this->assertResponseStatusCodeEquals($result, $statusCode);

        if ($result->isRedirection()) {
            $location = $this->getContainer()->get('router')->generate($redirectRoute);
            $this->assertTrue($result->isRedirect($location));
        }

        $this->assertEquals($flashMessages, $this->getContainer()->get('session')->getFlashBag()->all());

        if ($statusCode === Response::HTTP_FORBIDDEN) {
            $response = self::getJsonResponseContent($result, Response::HTTP_FORBIDDEN);

            $this->assertEquals(['Expected error message'], $response['messages']);
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeActionDataProvider()
    {
        $label = 'oro.action.test.label';

        $config = [
            'oro_action_test_action' => [
                'label' => $label,
                'enabled' => true,
                'order' => 10,
                'applications' => ['default', 'test'],
                'frontend_options' => [],
                'entities' => [],
                'routes' => [],
                'datagrids' => [],
                OperationDefinition::ACTIONS => [['@assign_value' => ['$message', self::MESSAGE_NEW]]],
            ]
        ];

        return [
            'right conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            OperationDefinition::PRECONDITIONS => ['@equal' => ['$message', self::MESSAGE_DEFAULT]],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'datagrid' => '',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => Response::HTTP_OK,
                'message' => self::MESSAGE_NEW,
            ],
            'wrong conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            OperationDefinition::PRECONDITIONS => [
                                '@equal' => [
                                    'message' => 'Expected error message',
                                    'parameters' => ['$message', 'test message wrong']
                                ]
                            ],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'datagrid' => '',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => Response::HTTP_FORBIDDEN,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'unknown entity' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['entities' => ['Oro\Bundle\TestFrameworkBundle\Enti\UnknownEntity']]]
                ),
                'route' => 'oro_action_test_route',
                'datagrid' => '',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => Response::HTTP_OK,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'unknown route' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['routes' => ['oro_action_unknown_route']]]
                ),
                'route' => 'oro_action_test_route',
                'datagrid' => '',
                'entityId' => false,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => Response::HTTP_OK,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'empty context' => [
                'config' => $config,
                'route' => 'oro_action_test_route',
                'datagrid' => '',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => Response::HTTP_OK,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'datagrid' => [
                'config' => array_merge_recursive(
                    $config,
                    ['oro_action_test_action' => ['datagrids' => ['test_datagrid']]]
                ),
                'route' => 'oro_action_test_route',
                'datagrid' => 'test_datagrid',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => Response::HTTP_OK,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'redirect' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            OperationDefinition::ACTIONS => [
                                [
                                    '@redirect' => [
                                        'route' => 'oro_action_widget_buttons'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ),
                'route' => null,
                'datagrid' => null,
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => Response::HTTP_FOUND,
                'message' => self::MESSAGE_DEFAULT,
                'redirectRoute' => 'oro_action_widget_buttons',
                'flashMessages' => [],
                'headers' => []
            ],
            'redirect ajax' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            OperationDefinition::ACTIONS => [
                                [
                                    '@redirect' => [
                                        'route' => 'oro_action_widget_buttons'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ),
                'route' => null,
                'datagrid' => null,
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => Response::HTTP_OK,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'redirect_invalid_non_ajax' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            OperationDefinition::PRECONDITIONS => [
                                '@equal' => [
                                    'message' => 'Expected error message',
                                    'parameters' => ['$message', 'test message wrong']
                                ]
                            ],
                        ],
                    ]
                ),
                'route' => 'oro_action_widget_buttons',
                'datagrid' => '',
                'entityId' => null,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => Response::HTTP_FORBIDDEN,
                'message' => self::MESSAGE_DEFAULT,
                'redirectRoute' => 'oro_action_widget_buttons',
                'headers' => [],
            ],
        ];
    }

    /**
     * @param $operationName
     * @param $entityId
     * @param $entityClass
     * @param $datagrid
     *
     * @return array
     */
    protected function getOperationExecuteParams($operationName, $entityId, $entityClass, $datagrid)
    {
        $actionContext = [
            'entityId'    => $entityId,
            'entityClass' => $entityClass,
            'datagrid'    => $datagrid
        ];
        $container = self::getContainer();
        $operation = $container->get('oro_action.operation_registry')->findByName($operationName);
        $actionData = $container->get('oro_action.helper.context')->getActionData($actionContext);

        $tokenData = $container->get('oro_action.operation.execution.form_provider')
            ->createTokenData($operation, $actionData);
        $container->get('session')->save();

        return $tokenData;
    }
}
