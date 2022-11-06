<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData;
use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Provider\PhpArrayConfigCacheModifier;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class AjaxControllerTest extends WebTestCase
{
    use OperationAwareTestTrait;

    private const MESSAGE_DEFAULT = 'test message';
    private const MESSAGE_NEW = 'new test message';

    /** @var TestActivity */
    private $entity;

    /** @var ConfigurationProvider */
    private $configProvider;

    /** @var PhpArrayConfigCacheModifier */
    private $configModifier;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->configProvider = $this->getContainer()->get('oro_action.tests.configuration.provider');
        $this->configModifier = new PhpArrayConfigCacheModifier($this->configProvider);

        $this->loadFixtures([LoadTestEntityData::class]);
        $this->entity = $this->getReference(LoadTestEntityData::TEST_ENTITY_1)
            ->setMessage(self::MESSAGE_DEFAULT);
    }

    protected function tearDown(): void
    {
        $this->configModifier->resetCache();
    }

    /**
     * @dataProvider executeActionDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testExecuteAction(
        array $config,
        ?string $route,
        ?string $datagrid,
        ?bool $entityId,
        string $entityClass,
        int $statusCode,
        string $message,
        string $redirectRoute = '',
        array $flashMessages = [],
        array $headers = ['HTTP_X-Requested-With' => 'XMLHttpRequest']
    ) {
        $this->setOperationsConfig($config);

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

        $this->assertEquals(
            $flashMessages,
            $this->getSession()->getFlashBag()->all()
        );

        if ($statusCode === Response::HTTP_FORBIDDEN) {
            $response = self::getJsonResponseContent($result, Response::HTTP_FORBIDDEN);

            $this->assertEquals(['Expected error message'], $response['messages']);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeActionDataProvider(): array
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
                            'entities' => [TestActivity::class],
                            OperationDefinition::PRECONDITIONS => ['@equal' => ['$message', self::MESSAGE_DEFAULT]],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'datagrid' => '',
                'entityId' => true,
                'entityClass' => TestActivity::class,
                'statusCode' => Response::HTTP_OK,
                'message' => self::MESSAGE_NEW,
            ],
            'wrong conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => [TestActivity::class],
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
                'entityClass' => TestActivity::class,
                'statusCode' => Response::HTTP_FORBIDDEN,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'unknown entity' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\UnknownEntity']
                        ]
                    ]
                ),
                'route' => 'oro_action_test_route',
                'datagrid' => '',
                'entityId' => true,
                'entityClass' => TestActivity::class,
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
                'entityClass' => TestActivity::class,
                'statusCode' => Response::HTTP_OK,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'empty context' => [
                'config' => $config,
                'route' => 'oro_action_test_route',
                'datagrid' => '',
                'entityId' => true,
                'entityClass' => TestActivity::class,
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
                'entityClass' => TestActivity::class,
                'statusCode' => Response::HTTP_OK,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'redirect' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => [TestActivity::class],
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
                'entityClass' => TestActivity::class,
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
                            'entities' => [TestActivity::class],
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
                'entityClass' => TestActivity::class,
                'statusCode' => Response::HTTP_OK,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'redirect_invalid_non_ajax' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => [TestActivity::class],
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
                'entityClass' => TestActivity::class,
                'statusCode' => Response::HTTP_FORBIDDEN,
                'message' => self::MESSAGE_DEFAULT,
                'redirectRoute' => 'oro_action_widget_buttons',
                'headers' => [],
            ],
        ];
    }

    public function testExecuteActionNotFound(): void
    {
        $operationName = 'oro_action_test_action';
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => 'oro_user_view',
                    'entityId' => 42,
                    'entityClass' => User::class,
                ]
            )
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NOT_FOUND);
        $this->assertEquals(
            ['error' => ['Operation with name "oro_action_test_action" not found']],
            $this->getSession()->getFlashBag()->all()
        );
    }

    private function setOperationsConfig(array $operations): void
    {
        $config = $this->configProvider->getConfiguration();
        $config['operations'] = $operations;
        $this->configModifier->updateCache($config);
    }
}
