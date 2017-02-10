<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Provider;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Button\OperationButton;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Tests\Functional\Stub\ButtonProviderExtensionStub;
use Oro\Bundle\ActionBundle\Tests\Functional\Stub\ButtonStub;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ButtonProviderTest extends WebTestCase
{
    const ROOT_NODE_NAME = 'operations';

    const ROUTE_NAME = 'test_route_name';

    /** @var ButtonProvider */
    private $buttonProvider;

    /** @var FilesystemCache */
    private $cacheProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->buttonProvider = $this->getContainer()->get('oro_action.provider.button');
        $this->cacheProvider = $this->getContainer()->get('oro_action.cache.provider.operations');
    }

    protected function tearDown()
    {
        $this->cacheProvider->delete(self::ROOT_NODE_NAME);

        parent::tearDown();
    }

    /**
     * @dataProvider stubButtonProvider
     *
     * @param callable $findButton
     * @param callable $isAvailable
     * @param int $countAll
     * @param int $countAvailable
     */
    public function testFindButtons(callable $findButton, callable $isAvailable, $countAll, $countAvailable)
    {
        $config = $this->getConfig('oro_action_test_operation', [self::ROUTE_NAME]);
        $this->cacheProvider->save(self::ROOT_NODE_NAME, $config);

        $this->buttonProvider->addExtension(new ButtonProviderExtensionStub($findButton, $isAvailable));

        $buttons = $this->buttonProvider->findAll(
            (new ButtonSearchContext())->setRouteName(self::ROUTE_NAME)
        );

        $this->assertCount($countAll + 1, $buttons);

        $buttons = $this->buttonProvider->findAvailable(
            (new ButtonSearchContext())->setRouteName(self::ROUTE_NAME)
        );

        $this->assertCount($countAvailable + 1, $buttons);
    }

    /**
     * @return array
     */
    public function stubButtonProvider()
    {
        $falseCallable = function () {
            return false;
        };

        $buttonCallable = function (ButtonInterface $button, ButtonSearchContext $buttonSearchContext) {
            return $button instanceof ButtonInterface && $buttonSearchContext->getRouteName() === self::ROUTE_NAME;
        };

        $twoButtons = function () {
            return [new ButtonStub(), new ButtonStub()];
        };

        $oneButton = function () {
            return [new ButtonStub()];
        };

        return [
            [
                'findButton' => $twoButtons,
                'isAvailable' => $buttonCallable,
                'countAll' => 2,
                'countAvailable' => 2
            ],
            [
                'findButton' => $twoButtons,
                'isAvailable' => $falseCallable,
                'countAll' => 2,
                'countAvailable' => 0
            ],
            [
                'findButton' => $oneButton,
                'isAvailable' => $buttonCallable,
                'countAll' => 1,
                'countAvailable' => 1
            ]
        ];
    }

    public function testPassedActionData()
    {
        $config = $this->getConfig('oro_pass_test', ['test_2']);
        $config = array_merge_recursive(
            $config,
            [
                'oro_pass_test' => [
                    'preactions' => [
                        ['@assign_value' => ['$.test', 'test_value']]
                    ]
                ]
            ]
        );

        $this->cacheProvider->save(self::ROOT_NODE_NAME, $config);

        $buttons = $this->buttonProvider->findAvailable(
            (new ButtonSearchContext())->setRouteName('test_2')
        );

        $this->assertCount(1, $buttons);
        /** @var OperationButton $operationButton */
        $operationButton = end($buttons);
        $this->assertInstanceOf(OperationButton::class, $operationButton);
        /** @var ActionData $actionData */
        $actionData = $operationButton->getTemplateData()['actionData'];
        $this->assertEquals($actionData->get('test'), 'test_value');
    }

    /**
     * @param $name
     * @param array $routes
     * @param array $entities
     * @return array
     */
    private function getConfig($name, array $routes = [], array $entities = [])
    {
        $config = [
            $name => [
                'label' => 'test_label',
                'enabled' => true,
                'order' => 10,
                'applications' => [],
                'datagrids' => [],
                'frontend_options' => [],
                'entities' => $entities,
                'routes' => $routes,
                'groups' => [],
                'exclude_entities' => [],
                'for_all_entities' => false,
                'exclude_datagrids' => [],
                'for_all_datagrids' => false
            ]
        ];

        return $config;
    }
}
