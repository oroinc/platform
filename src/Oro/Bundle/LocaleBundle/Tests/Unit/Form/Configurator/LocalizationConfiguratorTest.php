<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Oro\Bundle\ConfigBundle\Form\Type\ParentScopeCheckbox;
use Oro\Bundle\LocaleBundle\Form\Configurator\LocalizationConfigurator;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationSelectionType;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationSelectionTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class LocalizationConfiguratorTest extends FormIntegrationTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var LocalizationConfigurator|\PHPUnit\Framework\MockObject\MockObject */
    private $configurator;

    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->createMock(ConfigManager::class);

        /** @var ConfigHandler|\PHPUnit\Framework\MockObject\MockObject $configHandler */
        $configHandler = $this->createMock(ConfigHandler::class);
        $configHandler->expects($this->any())
            ->method('getConfigManager')
            ->willReturn($this->configManager);

        $this->configurator = new LocalizationConfigurator($configHandler);
    }

    /**
     * @dataProvider buildFormDataProvider
     *
     * @param null|int $data
     * @param string $scope
     * @param string $expectedType
     * @param bool $expectValue
     */
    public function testBuildForm(?int $data, string $scope, string $expectedType, bool $expectValue): void
    {
        /** @var callable $callable */
        $callable = null;

        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->any())
            ->method('addEventListener')
            ->willReturnCallback(
                function (string $eventName, callable $listener, $priority) use (&$callable) {
                    $this->assertEquals(FormEvents::PRE_SET_DATA, $eventName);
                    $this->assertEquals(0, $priority);

                    $callable = $listener;
                }
            );

        $this->configurator->buildForm($builder);

        $form = $this->factory->create(FormType::class);
        $form->add('oro_locale___default_localization', LocalizationSelectionTypeStub::class);
        $form->add('oro_locale___enabled_localizations', LocalizationSelectionTypeStub::class);

        $this->configManager->expects($this->any())
            ->method('getScopeEntityName')
            ->willReturn($scope);

        $enabledLocalizations = $data ? [42] : [];

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_locale.enabled_localizations', false, false, null)
            ->willReturn($enabledLocalizations);

        $callable(new FormEvent($form, $data));

        $config = $form->get('oro_locale___default_localization')
            ->get('use_parent_scope_value')
            ->getConfig();

        $this->assertInstanceOf($expectedType, $config->getType()->getInnerType());
        $this->assertEquals($expectValue, isset($config->getOptions()['value']));

        $childForm = $form->get('oro_locale___default_localization')
            ->get('value');

        $this->assertEquals($enabledLocalizations, $childForm->getConfig()->getOption('enabled_localizations', []));
    }

    /**
     * {@inheritdoc}
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
                'data' => 1,
                'scope' => 'test',
                'expectedType' => ParentScopeCheckbox::class,
                'expectValue' => true
            ],
            [
                'data' => 1,
                'scope' => 'app',
                'expectedType' => HiddenType::class,
                'expectValue' => false
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject $localizationManager */
        $localizationManager = $this->createMock(LocalizationManager::class);

        /** @var LocalizationChoicesProvider|\PHPUnit\Framework\MockObject\MockObject $localizationChoicesProvider */
        $localizationChoicesProvider = $this->createMock(LocalizationChoicesProvider::class);
        $localizationChoicesProvider
            ->expects($this->any())
            ->method('getLocalizationChoices')
            ->willReturn([]);

        $localizationSelectionType = new LocalizationSelectionType($localizationManager, $localizationChoicesProvider);

        return [
            new PreloadedExtension(
                [LocalizationSelectionType::class => $localizationSelectionType],
                []
            )
        ];
    }
}
