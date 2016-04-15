<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Extension\ConfigExtension;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class AbstractConfigTypeTestCase extends TypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $testConfigProvider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->testConfigProvider = $this->getConfigProviderMock();

        parent::setUp();
    }

    protected function getExtensions()
    {
        $validator = new Validator(
            new ClassMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory(),
            new DefaultTranslator()
        );

        return [
            new PreloadedExtension(
                [],
                [
                    'form' => [
                        new FormTypeValidatorExtension($validator),
                        new ConfigExtension()
                    ]
                ]
            )
        ];
    }

    /**
     * @param string                                     $formName
     * @param AbstractType                               $formType
     * @param array                                      $options
     * @param \PHPUnit_Framework_MockObject_MockObject[] $configProviders
     * @param mixed                                      $newVal
     * @param mixed                                      $oldVal
     * @param string                                     $state
     * @param bool                                       $isSetStateExpected
     *
     * @return mixed The form data
     */
    protected function doTestSubmit(
        $formName,
        AbstractType $formType,
        array $options,
        array $configProviders,
        $newVal,
        $oldVal,
        $state,
        $isSetStateExpected
    ) {
        $config = new Config(new EntityConfigId('test', 'Test\Entity'));
        $config->set($formName, $oldVal);

        $propertyConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $propertyConfig->expects($this->once())
            ->method('isSchemaUpdateRequired')
            ->with($formName, $config->getId())
            ->will($this->returnValue(true));
        $this->testConfigProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($propertyConfig));

        $extendConfigId = new EntityConfigId('extend', 'Test\Entity');
        $extendConfig   = new Config($extendConfigId);
        $extendConfig->set('state', $state);
        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($extendConfig));
        $this->configManager->expects($this->any())
            ->method('getConfig')
            ->with($config->getId())
            ->will($this->returnValue($config));

        $this->setConfigProvidersForSubmitTest($configProviders);
        $configProviders['extend'] = $extendConfigProvider;

        $configProvidersMap = [];
        foreach ($configProviders as $configProviderScope => $configProvider) {
            $configProvidersMap[] = [$configProviderScope, $configProvider];
        }
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValueMap($configProvidersMap));

        $form = $this->factory->createNamed($formName, $formType, $oldVal, $options);

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
        foreach ($configProviders as $configProvider) {
            $configProvider->expects($this->never())
                ->method('flush');
        }
        $this->configManager->expects($this->never())
            ->method('flush');

        $form->submit($newVal);

        $this->assertTrue($form->isSynchronized(), 'Expected that a form is synchronized');
        $this->assertEquals($expectedExtendConfig, $extendConfig);

        return $form->getData();
    }

    protected function setConfigProvidersForSubmitTest(array &$configProviders)
    {
        $configProviders['test'] = $this->testConfigProvider;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
