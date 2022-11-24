<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Action;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Provider\PhpArrayConfigCacheModifier;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems;
use Symfony\Component\Routing\RouterInterface;

class RedirectTest extends WebTestCase
{
    use OperationAwareTestTrait;

    /** @var ConfigurationProvider */
    private $configProvider;

    /** @var PhpArrayConfigCacheModifier */
    private $configModifier;

    /** @var RouterInterface */
    private $router;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->configProvider = $this->getContainer()->get('oro_action.tests.configuration.provider');
        $this->configModifier = new PhpArrayConfigCacheModifier($this->configProvider);
        $this->router = $this->getContainer()->get('router');

        $this->loadFixtures([LoadItems::class]);
    }

    protected function tearDown(): void
    {
        $this->configModifier->resetCache();
    }

    public function testExecuteWithURLConfig()
    {
        $result = $this->executeAction([
            'url' => 'https://oroinc.com',
        ]);
        self::assertEquals([
            'success' => true,
            'message' => '',
            'messages' => [],
            'redirectUrl' => 'https://oroinc.com',
            'pageReload' => true,
        ], $result);
    }

    public function testExecuteWithRouteConfig()
    {
        $result = $this->executeAction([
            'route' => 'oro_test_item_index',
        ]);
        self::assertEquals([
            'success' => true,
            'message' => '',
            'messages' => [],
            'redirectUrl' => $this->router->generate('oro_test_item_index'),
            'pageReload' => true,
        ], $result);
    }

    public function testExecuteWithRouteParamsConfig()
    {
        $result = $this->executeAction([
            'route' => 'oro_test_item_view',
            'route_parameters' => ['id' => 1],
        ]);
        self::assertEquals([
            'success' => true,
            'message' => '',
            'messages' => [],
            'redirectUrl' => $this->router->generate('oro_test_item_view', ['id' => 1]),
            'pageReload' => true,
        ], $result);
    }

    public function testExecuteWithFullConfig()
    {
        $result = $this->executeAction([
            'url' => 'https://oroinc.com',
            'route' => 'oro_test_item_view',
            'route_parameters' => ['id' => 1],
        ]);
        self::assertEquals([
            'success' => true,
            'message' => '',
            'messages' => [],
            // Route config has higher priority than URL
            'redirectUrl' => $this->router->generate('oro_test_item_view', ['id' => 1]),
            'pageReload' => true,
        ], $result);
    }

    private function executeAction(array $definition): array
    {
        $config = [
            'oro_action_test_action' => [
                'label' => 'oro.action.test.label',
                'enabled' => true,
                'order' => 10,
                'applications' => ['default', 'test'],
                'entities' => [],
                'routes' => [],
                'datagrids' => [],
                OperationDefinition::ACTIONS => [
                    [
                        '@redirect' => $definition,
                    ],
                ],
            ],
        ];

        $this->setOperationsConfig($config);

        $item = $this->getReference(LoadItems::ITEM1);
        $operationName = 'oro_action_test_action';
        $entityClass = Item::class;
        $entityId = $item->getId();
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                ]
            ),
            $this->getOperationExecuteParams($operationName, $entityId, $entityClass),
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        return json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function setOperationsConfig(array $operations)
    {
        $config = $this->configProvider->getConfiguration();
        $config['operations'] = $operations;
        $this->configModifier->updateCache($config);
    }
}
