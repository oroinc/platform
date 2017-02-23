<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\FormBundle\Form\Handler\CallbackFormHandler;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\FormBundle\Model\UpdateBuilder;
use Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;

class UpdateBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormHandlerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $handlerRegistry;

    /** @var FormTemplateDataProviderRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $dataProviderRegistry;

    /** @var UpdateBuilder */
    private $builder;

    protected function setUp()
    {
        $this->handlerRegistry = $this->createMock(FormHandlerRegistry::class);
        $this->dataProviderRegistry = $this->createMock(FormTemplateDataProviderRegistry::class);

        $this->builder = new UpdateBuilder(
            $this->handlerRegistry,
            $this->dataProviderRegistry
        );
    }

    public function testCreateAutoDefaults()
    {
        $argData = (object)[];
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $argForm */
        $argForm = $this->createMock(FormInterface::class);
        $argSaveMessage = 'save message';
        $argHandler = null;
        $argResultProvider = null;

        $handler = $this->createMock(FormHandlerInterface::class);
        $this->handlerRegistry->expects($this->once())
            ->method('get')->with(FormHandlerRegistry::DEFAULT_HANDLER_NAME)->willReturn($handler);

        $provider = $this->createMock(FormTemplateDataProviderInterface::class);
        $this->dataProviderRegistry->expects($this->once())
            ->method('get')->with(FormTemplateDataProviderRegistry::DEFAULT_PROVIDER_NAME)->willReturn($provider);

        $update = $this->builder->create($argData, $argForm, $argSaveMessage, $argHandler, $argResultProvider);

        $this->assertSame($argData, $update->data);
        $this->assertSame($argForm, $update->form);
        $this->assertSame($argSaveMessage, $update->saveMessage);
        $this->assertSame($handler, $update->handler);
        $this->assertSame($provider, $update->resultDataProvider);
    }

    public function testCreateCustomAliases()
    {
        $argData = (object)[];
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $argForm */
        $argForm = $this->createMock(FormInterface::class);
        $argSaveMessage = 'save message';
        $argHandler = 'handler_alias';
        $argResultProvider = 'provider_alias';

        $handler = $this->createMock(FormHandlerInterface::class);
        $this->handlerRegistry->expects($this->once())
            ->method('get')->with($argHandler)->willReturn($handler);

        $provider = $this->createMock(FormTemplateDataProviderInterface::class);
        $this->dataProviderRegistry->expects($this->once())
            ->method('get')->with($argResultProvider)->willReturn($provider);

        $this->builder->create($argData, $argForm, $argSaveMessage, $argHandler, $argResultProvider);
    }

    public function testCreateArgInstances()
    {
        $argData = (object)[];
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $argForm */
        $argForm = $this->createMock(FormInterface::class);
        $argSaveMessage = 'save message';
        $argHandler = $this->createMock(FormHandlerInterface::class);
        $argResultProvider = $this->createMock(FormTemplateDataProviderInterface::class);

        $update = $this->builder->create($argData, $argForm, $argSaveMessage, $argHandler, $argResultProvider);

        $this->assertSame($argHandler, $update->handler);
        $this->assertSame($argResultProvider, $update->resultDataProvider);
    }

    public function testCreateArgCallbacks()
    {
        $argData = (object)[];
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $argForm */
        $argForm = $this->createMock(FormInterface::class);
        $argSaveMessage = 'save message';
        $argHandler = function () {
            return true;
        };
        $argResultProvider = function () {
            return ['provider result'];
        };

        $update = $this->builder->create($argData, $argForm, $argSaveMessage, $argHandler, $argResultProvider);

        $this->assertInstanceOf(CallbackFormHandler::class, $update->handler);
        $this->assertInstanceOf(CallbackFormTemplateDataProvider::class, $update->resultDataProvider);

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $this->assertTrue($update->handler->process($argData, $argForm, $request));
        $this->assertSame(['provider result'], $update->resultDataProvider->getData($argData, $argForm, $request));
    }
}
