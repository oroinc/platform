<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Extension\ConfigExtension;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\TranslationBundle\Translation\IdentityTranslator;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator\RecursiveValidator;

abstract class AbstractConfigTypeTestCase extends TypeTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $testConfigProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->testConfigProvider = $this->createMock(ConfigProvider::class);

        parent::setUp();
    }

    abstract protected function getFormType(): AbstractType;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $validator = new RecursiveValidator(
            new ExecutionContextFactory(new IdentityTranslator()),
            new LazyLoadingMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory()
        );

        return [
            new PreloadedExtension(
                [
                    $this->getFormType()
                ],
                [
                    FormType::class => [
                        new FormTypeValidatorExtension($validator),
                        new ConfigExtension()
                    ]
                ]
            )
        ];
    }

    protected function doTestSubmit(
        string $formName,
        string $formTypeClass,
        array $options,
        array $configProviders,
        mixed $newVal,
        mixed $oldVal,
        string $state,
        bool $isSetStateExpected
    ): mixed {
        $config = new Config(new EntityConfigId('test', 'Test\Entity'));
        $config->set($formName, $oldVal);

        $propertyConfig = $this->createMock(PropertyConfigContainer::class);
        $propertyConfig->expects($this->once())
            ->method('isSchemaUpdateRequired')
            ->with($formName, $config->getId())
            ->willReturn(true);
        $this->testConfigProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfig);

        $extendConfigId = new EntityConfigId('extend', 'Test\Entity');
        $extendConfig   = new Config($extendConfigId);
        $extendConfig->set('state', $state);
        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects($this->any())
            ->method('getConfigById')
            ->with($config->getId())
            ->willReturn($extendConfig);
        $this->configManager->expects($this->any())
            ->method('getConfig')
            ->with($config->getId())
            ->willReturn($config);

        $this->setConfigProvidersForSubmitTest($configProviders);
        $configProviders['extend'] = $extendConfigProvider;

        $configProvidersMap = [];
        foreach ($configProviders as $configProviderScope => $configProvider) {
            $configProvidersMap[] = [$configProviderScope, $configProvider];
        }
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap($configProvidersMap);

        $form = $this->factory->createNamed($formName, $formTypeClass, $oldVal, $options);

        $expectedExtendConfig = new Config($extendConfigId);
        $schemaUpdateRequired = call_user_func(
            $form->getConfig()->getOption('schema_update_required'),
            $newVal,
            $oldVal
        );
        if ($schemaUpdateRequired) {
            $expectedExtendConfig->set('pending_changes', [
                'test' => [
                    $formName => [
                        $oldVal,
                        is_array($newVal) && $oldVal !== null
                            ? array_merge(
                                $this->testConfigProvider->getConfig('Test\Entity')->get('immutable', false, []),
                                $newVal
                            )
                            : $newVal
                    ],
                ],
            ]);
        }
        if ($isSetStateExpected) {
            $expectedExtendConfig->set('state', ExtendScope::STATE_UPDATE);
            $this->configManager->expects($this->once())
                ->method('persist')
                ->with($expectedExtendConfig);
        } else {
            $expectedExtendConfig->set('state', $state);
            $this->configManager->expects($this->exactly($schemaUpdateRequired ? 1 : 0))
                ->method('persist');
        }

        // flush should be never called
        $this->configManager->expects($this->never())
            ->method('flush');

        $form->submit($newVal);

        $this->assertTrue($form->isSynchronized(), 'Expected that a form is synchronized');
        $this->assertEquals($expectedExtendConfig, $extendConfig);

        return $form->getData();
    }

    protected function setConfigProvidersForSubmitTest(array &$configProviders): void
    {
        $configProviders['test'] = $this->testConfigProvider;
    }
}
