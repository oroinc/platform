<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ConfigHandlerTest extends TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    private ConfigManager&MockObject $configManager;
    private FormInterface&MockObject $form;
    private Request $request;
    private ConfigHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();

        $this->handler = new ConfigHandler($this->configManager);
    }

    public function testGetConfigManager(): void
    {
        $this->assertSame($this->configManager, $this->handler->getConfigManager());
    }

    public function testProcessWithoutAdditionalHandler(): void
    {
        $settings = [];

        $this->configManager->expects($this->once())
            ->method('getSettingsByForm')
            ->with($this->isInstanceOf(FormInterface::class))
            ->willReturn($settings);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($settings);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->equalTo(self::FORM_DATA));

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn($settings);

        $this->configManager->expects($this->once())
            ->method('save');

        $formConfig = $this->createMock(FormConfigInterface::class);
        $this->form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $this->assertTrue($this->handler->process($this->form, $this->request));
    }

    public function testProcessWithAdditionalHandler(): void
    {
        $settings = [];
        $changeSet = new ConfigChangeSet([]);

        $this->configManager->expects($this->once())
            ->method('getSettingsByForm')
            ->with($this->isInstanceOf(FormInterface::class))
            ->willReturn($settings);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($settings);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->equalTo(self::FORM_DATA));

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn($settings);

        $this->configManager->expects($this->once())
            ->method('save')
            ->willReturn($changeSet);

        $isAdditionalHandlerCalled = false;
        $formConfig = $this->createMock(FormConfigInterface::class);
        $this->form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects($this->once())
            ->method('getAttribute')
            ->with('handler')
            ->willReturn(
                function ($manager, $changes) use ($changeSet, &$isAdditionalHandlerCalled) {
                    self::assertSame($this->configManager, $manager);
                    self::assertSame($changeSet, $changes);
                    $isAdditionalHandlerCalled = true;
                }
            );

        $this->assertTrue($this->handler->process($this->form, $this->request));
        $this->assertTrue($isAdditionalHandlerCalled, 'isAdditionalHandlerCalled');
    }

    public function testBadRequest(): void
    {
        $settings = [];

        $this->configManager->expects($this->once())
            ->method('getSettingsByForm')
            ->with($this->isInstanceOf(FormInterface::class))
            ->willReturn($settings);

        $this->form->expects($this->once())
            ->method('setData');

        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');
        $this->form->expects($this->never())
            ->method('isValid')
            ->willReturn(true);

        $this->configManager->expects($this->never())
            ->method('save');

        $this->assertFalse($this->handler->process($this->form, $this->request));
    }

    public function testFormNotValid(): void
    {
        $settings = [];

        $this->configManager->expects($this->once())
            ->method('getSettingsByForm')
            ->with($this->isInstanceOf(FormInterface::class))
            ->willReturn($settings);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($settings);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->equalTo(self::FORM_DATA));

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->configManager->expects($this->never())
            ->method('save');

        $this->assertFalse($this->handler->process($this->form, $this->request));
    }
}
