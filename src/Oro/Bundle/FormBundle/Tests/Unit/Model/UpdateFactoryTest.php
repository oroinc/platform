<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry;
use Oro\Bundle\FormBundle\Model\UpdateFactory;
use Oro\Bundle\FormBundle\Model\UpdateInterface;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UpdateFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var FormHandlerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $handlerRegistry;

    /** @var FormTemplateDataProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $dataProviderRegistry;

    /** @var UpdateFactory */
    private $updateFactory;

    protected function setUp()
    {
        $this->handlerRegistry = $this->createMock(FormHandlerRegistry::class);
        $this->dataProviderRegistry = $this->createMock(FormTemplateDataProviderRegistry::class);
        $this->formFactory = $this->createMock(FormFactory::class);

        $this->updateFactory = new UpdateFactory(
            $this->formFactory,
            $this->handlerRegistry,
            $this->dataProviderRegistry
        );
    }

    public function testCreateAutoDefaults()
    {
        $argData = (object)[];
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $argForm */
        $argForm = $this->createMock(FormInterface::class);
        $argHandler = null;
        $argResultProvider = null;

        $handler = $this->createMock(FormHandlerInterface::class);
        $this->handlerRegistry->expects($this->once())
            ->method('get')->with(FormHandlerRegistry::DEFAULT_HANDLER_NAME)->willReturn($handler);

        $provider = $this->createMock(FormTemplateDataProviderInterface::class);
        $this->dataProviderRegistry->expects($this->once())
            ->method('get')->with(FormTemplateDataProviderRegistry::DEFAULT_PROVIDER_NAME)->willReturn($provider);

        $update = $this->updateFactory->createUpdate($argData, $argForm, $argHandler, $argResultProvider);
        $this->assertExpectedConstruction($update, $argData, $argForm, $handler, $provider);
    }

    public function testCreateCustomAliases()
    {
        $argData = (object)[];
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $argForm */
        $argForm = 'form_type';
        $argHandler = 'handler_alias';
        $argResultProvider = 'provider_alias';

        $handler = $this->createMock(FormHandlerInterface::class);
        $this->handlerRegistry->expects($this->once())
            ->method('get')->with($argHandler)->willReturn($handler);

        $provider = $this->createMock(FormTemplateDataProviderInterface::class);
        $this->dataProviderRegistry->expects($this->once())
            ->method('get')->with($argResultProvider)->willReturn($provider);

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects($this->once())
            ->method('create')->with($argForm, $argData)->willReturn($form);

        $this->updateFactory->createUpdate($argData, $argForm, $argHandler, $argResultProvider);
    }

    public function testCreateArgCallbacks()
    {
        $argData = (object)[];
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $argForm */
        $argForm = $this->createMock(FormInterface::class);
        $argHandler = function () {
            return true;
        };
        $argResultProvider = function () {
            return ['provider result'];
        };

        $update = $this->updateFactory->createUpdate($argData, $argForm, $argHandler, $argResultProvider);

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $this->assertTrue($update->handle($request));
        $this->assertSame(['provider result'], $update->getTemplateData($request));
    }

    /**
     * @param UpdateInterface $update
     * @param object $formData
     * @param FormInterface|\PHPUnit\Framework\MockObject\MockObject $form
     * @param FormHandlerInterface|\PHPUnit\Framework\MockObject\MockObject $handler
     * @param FormTemplateDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider
     */
    protected function assertExpectedConstruction(
        UpdateInterface $update,
        $formData,
        FormInterface $form,
        FormHandlerInterface $handler,
        FormTemplateDataProviderInterface $provider
    ) {
        $this->assertSame($formData, $update->getFormData());
        $this->assertSame($form, $update->getForm());
        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);
        $handler->expects($this->once())->method('process')->with($formData, $form, $request);
        $provider->expects($this->once())->method('getData')->with($formData, $form, $request);

        $update->handle($request);
        $update->getTemplateData($request);
    }
}
