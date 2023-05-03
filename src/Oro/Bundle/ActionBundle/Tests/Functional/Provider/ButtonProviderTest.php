<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Provider;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Button\OperationButton;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Tests\Functional\Stub\ButtonProviderExtensionStub;
use Oro\Bundle\ActionBundle\Tests\Functional\Stub\ButtonStub;
use Oro\Bundle\TestFrameworkBundle\Provider\PhpArrayConfigCacheModifier;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ButtonProviderTest extends WebTestCase
{
    private const ROUTE_NAME = 'test_route_name';

    /** @var ButtonProvider */
    private $buttonProvider;

    /** @var ConfigurationProvider */
    private $configProvider;

    /** @var PhpArrayConfigCacheModifier */
    private $configModifier;

    protected function setUp(): void
    {
        $this->initClient();
        $this->buttonProvider = $this->getContainer()->get('oro_action.provider.button');
        $this->configProvider = $this->getContainer()->get('oro_action.tests.configuration.provider');
        $this->configModifier = new PhpArrayConfigCacheModifier($this->configProvider);
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('oro_action.tests.provider.button.extension')
            ->setDecoratedExtension(null);
        $this->configModifier->resetCache();
    }

    /**
     * @dataProvider stubButtonProvider
     */
    public function testFindButtons(callable $findButton, callable $isAvailable, int $countAll, int $countAvailable)
    {
        $config = $this->getConfig('oro_action_test_operation', [self::ROUTE_NAME]);
        $this->setOperationsConfig($config);

        $this->getContainer()->get('oro_action.tests.provider.button.extension')
            ->setDecoratedExtension(new ButtonProviderExtensionStub($findButton, $isAvailable));

        $buttons = $this->buttonProvider->findAll(
            (new ButtonSearchContext())->setRouteName(self::ROUTE_NAME)
        );

        $this->assertCount($countAll + 1, $buttons);

        $buttons = $this->buttonProvider->findAvailable(
            (new ButtonSearchContext())->setRouteName(self::ROUTE_NAME)
        );

        $this->assertCount($countAvailable + 1, $buttons);
    }

    public function stubButtonProvider(): array
    {
        $falseCallable = function () {
            return false;
        };

        $buttonCallable = function (ButtonInterface $button, ButtonSearchContext $buttonSearchContext) {
            return $buttonSearchContext->getRouteName() === self::ROUTE_NAME;
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
        $this->setOperationsConfig($config);

        $buttons = $this->buttonProvider->findAvailable(
            (new ButtonSearchContext())->setRouteName('test_2')
        );

        $this->assertCount(1, $buttons);
        /** @var OperationButton $operationButton */
        $operationButton = end($buttons);
        $this->assertInstanceOf(OperationButton::class, $operationButton);
        /** @var ActionData $actionData */
        $actionData = $operationButton->getTemplateData()['actionData'];
        $this->assertEquals('test_value', $actionData->get('test'));
    }

    private function getConfig(string $name, array $routes = [], array $entities = []): array
    {
        return [
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
    }

    private function setOperationsConfig(array $operations): void
    {
        $config = $this->configProvider->getConfiguration();
        $config['operations'] = $operations;
        $this->configModifier->updateCache($config);
    }
}
