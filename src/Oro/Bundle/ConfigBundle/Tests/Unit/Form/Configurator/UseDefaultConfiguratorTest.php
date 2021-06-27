<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Configurator\UseDefaultConfigurator;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Oro\Bundle\ConfigBundle\Form\Type\ParentScopeCheckbox;
use Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type\Stub\ConfigFieldStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class UseDefaultConfiguratorTest extends FormIntegrationTestCase
{
    /**
     * @var ConfigHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configHandler;

    /**
     * @var UseDefaultConfigurator
     */
    private $configurator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configHandler = $this->createMock(ConfigHandler::class);
        $this->configurator = new UseDefaultConfigurator($this->configHandler);
    }

    /**
     * @dataProvider buildFormDataProvider
     *
     * @param null|int $data
     * @param string $scope
     * @param string $expectedType
     * @param bool $expectValue
     */
    public function testConfigurator(?array $data, string $scope, string $expectedType, bool $expectValue): void
    {
        $this->configurator->disableUseDefaultFor('app', 'oro_test', 'field1');

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('getScopeEntityName')
            ->willReturn($scope);

        $this->configHandler->expects($this->any())
            ->method('getConfigManager')
            ->willReturn($configManager);

        $builder = $this->factory->createBuilder(FormType::class, $data);
        $builder->add('oro_test___field1', ConfigFieldStub::class);

        $this->configurator->buildForm($builder);

        $form = $builder->getForm();

        $config = $form->get('oro_test___field1')
            ->get('use_parent_scope_value')
            ->getConfig();

        $this->assertInstanceOf($expectedType, $config->getType()->getInnerType());
        $this->assertEquals($expectValue, isset($config->getOptions()['value']));
    }

    /**
     * {@inheritdoc}
     */
    public function buildFormDataProvider(): array
    {
        return [
            [
                'data' => null,
                'scope' => 'app',
                'expectedType' => ParentScopeCheckbox::class,
                'expectValue' => true
            ],
            [
                'data' => [
                    'oro_test___field1' => [
                        'use_parent_scope_value' => true
                    ]
                ],
                'scope' => 'test',
                'expectedType' => ParentScopeCheckbox::class,
                'expectValue' => true
            ],
            [
                'data' => [
                    'oro_test___field1' => [
                        'use_parent_scope_value' => true
                    ]
                ],
                'scope' => 'app',
                'expectedType' => HiddenType::class,
                'expectValue' => false
            ]
        ];
    }
}
