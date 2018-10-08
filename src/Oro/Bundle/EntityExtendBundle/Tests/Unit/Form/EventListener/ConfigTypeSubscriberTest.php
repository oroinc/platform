<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\EventListener\ConfigTypeSubscriber;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

class ConfigTypeSubscriberTest extends \PHPUnit\Framework\TestCase
{
    const FORM_NAME = 'oro_config_type';
    const SCOPE = 'extend';
    const CLASS_NAME = 'stdClass';

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManger;

    /**
     * @var EntityConfigId
     */
    protected $entityConfigId;

    /**
     * @var FormConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $formConfig;

    /**
     * @var FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $form;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configProvider;

    /**
     * @var PropertyConfigContainer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $propertyConfigContainer;

    public function setUp()
    {
        $this->entityConfigId = new EntityConfigId(self::SCOPE, self::CLASS_NAME);

        $this->formConfig = $this->createMock(FormConfigInterface::class);
        $this->formConfig->expects($this->any())->method('getOptions')->willReturn([
            'config_id' => $this->entityConfigId,
        ]);

        $this->form = $this->createMock(FormInterface::class);
        $this->form->expects($this->any())->method('getConfig')->willReturn($this->formConfig);
        $this->form->expects($this->any())->method('getName')->willReturn(self::FORM_NAME);

        $this->configManger = $this->createMock(ConfigManager::class);

        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->propertyConfigContainer = $this->createMock(PropertyConfigContainer::class);
        $this->configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($this->propertyConfigContainer);
    }

    public function testPostSubmitWithInvalidForm()
    {
        $this->form->expects($this->once())->method('isValid')->willReturn(false);
        $this->configManger->expects($this->never())->method('getProvider');

        $subscriber = new ConfigTypeSubscriber($this->configManger, [$this, 'isSchemaUpdateRequiredCallable']);
        $event = new FormEvent($this->form, []);
        $subscriber->postSubmit($event);
    }

    public function testPostSubmitWhenSchemaUpdateIsntRequired()
    {
        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $this->configManger->expects($this->once())->method('getProvider')->willReturn($this->configProvider);
        $this->propertyConfigContainer->expects($this->once())->method('isSchemaUpdateRequired')
            ->with(self::FORM_NAME, $this->entityConfigId)
            ->willReturn(false);

        $this->configManger->expects($this->never())->method('persist');
        $subscriber = new ConfigTypeSubscriber($this->configManger, [$this, 'isSchemaUpdateRequiredCallable']);
        $event = new FormEvent($this->form, []);
        $subscriber->postSubmit($event);
    }

    public function testPostSubmitWithFalseSchemaUpdateRequiredCallback()
    {
        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $this->configManger->expects($this->at(0))->method('getProvider')->willReturn($this->configProvider);
        $this->propertyConfigContainer->expects($this->once())->method('isSchemaUpdateRequired')
            ->with(self::FORM_NAME, $this->entityConfigId)
            ->willReturn(true);

        $newVal = 'new_value';
        $oldVal = 'old_value';

        $this->form->expects($this->once())->method('getData')->willReturn($newVal);
        $config = new Config($this->entityConfigId, [self::FORM_NAME => $oldVal]);
        $this->configManger->expects($this->at(1))->method('getConfig')
            ->with($this->entityConfigId)
            ->willReturn($config);

        $this->configManger->expects($this->never())->method('persist');
        $subscriber = new ConfigTypeSubscriber($this->configManger, function ($newValue, $oldValue) {
            $this->assertEquals('old_value', $oldValue);
            $this->assertEquals('new_value', $newValue);
            return false;
        });

        $this->configManger->expects($this->never())->method('persist');

        $event = new FormEvent($this->form, []);
        $subscriber->postSubmit($event);
    }

    public function testPostSubmitWithEntityConfig()
    {
        $newVal = 'new_value';
        $oldVal = 'old_value';

        $this->form->expects($this->once())->method('isValid')->willReturn(true);

        $config = new Config($this->entityConfigId, [self::FORM_NAME => $oldVal]);

        $this->configManger->expects($this->at(0))->method('getProvider')->willReturn($this->configProvider);
        $this->configManger->expects($this->at(1))->method('getConfig')
            ->with($this->entityConfigId)
            ->willReturn($config);

        $this->propertyConfigContainer->expects($this->once())->method('isSchemaUpdateRequired')
            ->with(self::FORM_NAME, $this->entityConfigId)
            ->willReturn(true);

        $subscriber = new ConfigTypeSubscriber($this->configManger, [$this, 'isSchemaUpdateRequiredCallable']);

        $this->form->expects($this->once())->method('getData')->willReturn($newVal);

        $extendConfig = new Config($this->entityConfigId, ['state' => ExtendScope::STATE_ACTIVE]);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider
            ->expects($this->once())
            ->method('getConfigById')
            ->with($this->entityConfigId)
            ->willReturn($extendConfig);

        $this->configManger->expects($this->at(2))->method('getProvider')
            ->with(self::SCOPE)
            ->willReturn($extendConfigProvider);

        $this->configManger->expects($this->once())->method('persist')->with($extendConfig);
        $event = new FormEvent($this->form, []);
        $subscriber->postSubmit($event);

        $this->assertArraySubset([
            'pending_changes' => [
                self::SCOPE => [self::FORM_NAME => [$oldVal, $newVal]],
            ],
            'state' => ExtendScope::STATE_UPDATE
        ], $extendConfig->getValues());
    }

    public function testPostSubmitWithFieldConfig()
    {
        $fieldConfigId = new FieldConfigId(self::SCOPE, self::CLASS_NAME, 'fieldName');

        $newVal = 'new_value';
        $oldVal = 'old_value';

        $config = new Config($fieldConfigId, [
            'owner' => ExtendScope::OWNER_CUSTOM,
            'state' => ExtendScope::STATE_ACTIVE,
            self::FORM_NAME => $oldVal,
        ]);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())->method('getOptions')->willReturn([
            'config_id' => $fieldConfigId,
        ]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getConfig')->willReturn($formConfig);
        $form->expects($this->atLeastOnce())->method('getName')->willReturn(self::FORM_NAME);

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManger */
        $configManger = $this->createMock(ConfigManager::class);

        $configProvider = $this->createMock(ConfigProvider::class);

        $propertyConfigContainer = $this->createMock(PropertyConfigContainer::class);
        $propertyConfigContainer->expects($this->once())->method('isSchemaUpdateRequired')->willReturn(true);
        $configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);

        $form->expects($this->once())->method('isValid')->willReturn(true);
        $configManger->expects($this->at(0))->method('getProvider')->willReturn($configProvider);
        $configManger->expects($this->at(1))->method('getConfig')
            ->with($fieldConfigId)
            ->willReturn($config);

        $subscriber = new ConfigTypeSubscriber($configManger, [$this, 'isSchemaUpdateRequiredCallable']);

        $form->expects($this->once())->method('getData')->willReturn($newVal);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);

        $extendConfig = new Config($this->entityConfigId, [
            'owner' => ExtendScope::OWNER_CUSTOM,
            'state' => ExtendScope::STATE_ACTIVE
        ]);
        $extendConfigProvider->expects($this->once())->method('getConfigById')
            ->with($fieldConfigId)
            ->willReturn($extendConfig);

        $configManger->expects($this->at(2))->method('getProvider')
            ->with(self::SCOPE)
            ->willReturn($extendConfigProvider);

        $configManger->expects($this->once())->method('persist')->with($extendConfig);
        $event = new FormEvent($form, []);
        $subscriber->postSubmit($event);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals([
            FormEvents::POST_SUBMIT => 'postSubmit',
        ], ConfigTypeSubscriber::getSubscribedEvents());
    }

    /**
     * @param mixed $newValue
     * @param mixed $oldValue
     * @return bool
     */
    public function isSchemaUpdateRequiredCallable($newValue, $oldValue)
    {
        return true;
    }
}
