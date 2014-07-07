<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Extension\ConfigExtension;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class AssociationChoiceTypeTestCase extends TypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $groupingConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $testConfigProvider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->testConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    protected function getExtensions()
    {
        $configExtension = new ConfigExtension();

        return [
            new PreloadedExtension(
                [],
                [$configExtension->getExtendedType() => [$configExtension]]
            )
        ];
    }

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
        $extendConfigId = new EntityConfigId('extend', 'Test\Entity');
        $extendConfig   = new Config($extendConfigId);
        $extendConfig->set('state', $state);
        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($extendConfig));
        $this->configManager->expects($this->once())
            ->method('getConfig')
            ->with($config->getId())
            ->will($this->returnValue($config));

        $configProviders['extend'] = $extendConfigProvider;

        $configProvidersMap = [];
        foreach ($configProviders as $configProviderScope => $configProvider) {
            $configProvidersMap[] = [$configProviderScope, $configProvider];
        }
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValueMap($configProvidersMap));

        $expectedExtendConfig = new Config($extendConfigId);
        if ($isSetStateExpected) {
            $expectedExtendConfig->set('state', ExtendScope::STATE_UPDATED);
            $extendConfigProvider->expects($this->once())
                ->method('persist')
                ->with($expectedExtendConfig);
            $extendConfigProvider->expects($this->once())
                ->method('flush');
        } else {
            $expectedExtendConfig->set('state', $state);
            $extendConfigProvider->expects($this->never())
                ->method('persist');
            $extendConfigProvider->expects($this->never())
                ->method('flush');
        }

        $form = $this->factory->createNamed($formName, $formType, $oldVal, $options);
        $form->submit($newVal);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    protected function prepareBuildViewTest()
    {
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['grouping', $this->groupingConfigProvider],
                        ['test', $this->testConfigProvider],
                    ]
                )
            );
    }
}
