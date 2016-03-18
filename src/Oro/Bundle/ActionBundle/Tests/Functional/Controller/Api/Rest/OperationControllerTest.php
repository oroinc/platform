<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures\LoadTestEntityData;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class OperationControllerTest extends WebTestCase
{
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
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->cacheProvider = $this->getContainer()->get('oro_action.cache.provider');
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
        $this->cacheProvider->delete(ConfigurationProvider::ROOT_NODE_NAME);

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
     */
    public function testExecuteAction(
        array $config,
        $route,
        $datagrid,
        $entityId,
        $entityClass,
        $statusCode,
        $message
    ) {
        $this->cacheProvider->save(ConfigurationProvider::ROOT_NODE_NAME, $config);

        $this->assertEquals(self::MESSAGE_DEFAULT, $this->entity->getMessage());

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_action_execute_operations',
                [
                    'operationName' => 'oro_action_test_action',
                    'route' => $route,
                    'datagrid' => $datagrid,
                    'entityId' => $entityId ? $this->entity->getId() : null,
                    'entityClass' => $entityClass,
                ]
            )
        );

        $result = $this->client->getResponse();

        $this->assertEquals($message, $this->entity->getMessage());
        $this->assertResponseStatusCodeEquals($result, $statusCode);
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
                'applications' => ['backend', 'frontend'],
                'frontend_options' => [],
                'entities' => [],
                'routes' => [],
                'datagrids' => [],
                'actions' => [['@assign_value' => ['$message', self::MESSAGE_NEW]]],
            ]
        ];

        return [
            'right conditions' => [
                'config' => array_merge_recursive(
                    $config,
                    [
                        'oro_action_test_action' => [
                            'entities' => ['Oro\Bundle\TestFrameworkBundle\Entity\TestActivity'],
                            'preconditions' => ['@equal' => ['$message', self::MESSAGE_DEFAULT]],
                        ],
                    ]
                ),
                'route' => 'oro_action_test_route',
                'datagrid' => '',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 200,
                'message' => self::MESSAGE_NEW,
            ],
            'wrong conditions' => [
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
                'datagrid' => '',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 404,
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
                'statusCode' => 200,
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
                'statusCode' => 200,
                'message' => self::MESSAGE_DEFAULT,
            ],
            'empty context' => [
                'config' => $config,
                'route' => 'oro_action_test_route',
                'datagrid' => '',
                'entityId' => true,
                'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity',
                'statusCode' => 200,
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
                'statusCode' => 200,
                'message' => self::MESSAGE_DEFAULT,
            ],
        ];
    }
}
