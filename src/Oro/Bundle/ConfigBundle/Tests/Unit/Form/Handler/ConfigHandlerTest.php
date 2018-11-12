<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ConfigHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_DATA = ['field' => 'value'];

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ConfigHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();

        $this->handler = new ConfigHandler($this->configManager);
    }

    public function testGetConfigManager()
    {
        $this->assertSame($this->configManager, $this->handler->getConfigManager());
    }

    public function testProcessWithoutAdditionalHandler()
    {
        $settings = [];

        $this->configManager->expects($this->once())
            ->method('getSettingsByForm')
            ->with($this->isInstanceOf('Symfony\Component\Form\Test\FormInterface'))
            ->will($this->returnValue($settings));

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
            ->will($this->returnValue(true));

        $this->form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($settings));

        $this->configManager->expects($this->once())
            ->method('save');

        $formConfig = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $this->form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $this->assertTrue($this->handler->process($this->form, $this->request));
    }

    public function testProcessWithAdditionalHandler()
    {
        $settings = [];
        $changeSet = new ConfigChangeSet([]);

        $this->configManager->expects($this->once())
            ->method('getSettingsByForm')
            ->with($this->isInstanceOf('Symfony\Component\Form\Test\FormInterface'))
            ->will($this->returnValue($settings));

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
            ->will($this->returnValue(true));

        $this->form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($settings));

        $this->configManager->expects($this->once())
            ->method('save')
            ->willReturn($changeSet);

        $isAdditionalHandlerCalled = false;
        $formConfig = $this->createMock('Symfony\Component\Form\FormConfigInterface');
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

    public function testBadRequest()
    {
        $settings = [];

        $this->configManager->expects($this->once())
            ->method('getSettingsByForm')
            ->with($this->isInstanceOf('Symfony\Component\Form\Test\FormInterface'))
            ->will($this->returnValue($settings));

        $this->form->expects($this->once())
            ->method('setData');

        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');
        $this->form->expects($this->never())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->configManager->expects($this->never())
            ->method('save');

        $this->assertFalse($this->handler->process($this->form, $this->request));
    }

    public function testFormNotValid()
    {
        $settings = [];

        $this->configManager->expects($this->once())
            ->method('getSettingsByForm')
            ->with($this->isInstanceOf('Symfony\Component\Form\Test\FormInterface'))
            ->will($this->returnValue($settings));

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
            ->will($this->returnValue(false));

        $this->configManager->expects($this->never())
            ->method('save');

        $this->assertFalse($this->handler->process($this->form, $this->request));
    }
}
