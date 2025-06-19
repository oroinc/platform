<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderResolver;
use Oro\Bundle\FormBundle\Model\UpdateFactory;
use Oro\Bundle\FormBundle\Model\UpdateInterface;
use Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UpdateFactoryTest extends TestCase
{
    private FormFactory&MockObject $formFactory;
    private FormHandlerRegistry&MockObject $handlerRegistry;
    private FormTemplateDataProviderResolver&MockObject $formTemplateDataProviderResolver;
    private UpdateFactory $updateFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->handlerRegistry = $this->createMock(FormHandlerRegistry::class);
        $this->formTemplateDataProviderResolver = $this->createMock(FormTemplateDataProviderResolver::class);
        $this->formFactory = $this->createMock(FormFactory::class);

        $this->updateFactory = new UpdateFactory(
            $this->formFactory,
            $this->handlerRegistry,
            $this->formTemplateDataProviderResolver
        );
    }

    public function testCreateAutoDefaults(): void
    {
        $argData = (object)[];
        $argForm = $this->createMock(FormInterface::class);
        $argHandler = null;
        $argResultProvider = null;

        $handler = $this->createMock(FormHandlerInterface::class);
        $this->handlerRegistry->expects(self::once())
            ->method('get')
            ->with(FormHandlerRegistry::DEFAULT_HANDLER_NAME)
            ->willReturn($handler);

        $provider = $this->createMock(FormTemplateDataProviderInterface::class);
        $this->formTemplateDataProviderResolver->expects(self::once())
            ->method('resolve')
            ->with(null)
            ->willReturn($provider);

        $update = $this->updateFactory->createUpdate($argData, $argForm, $argHandler, $argResultProvider);

        $this->assertExpectedConstruction($update, $argData, $argForm, $handler, $provider);
    }

    public function testCreateCustomAliases(): void
    {
        $argData = (object)[];
        $argForm = 'form_type';
        $argHandler = 'handler_alias';
        $argResultProvider = 'provider_alias';

        $handler = $this->createMock(FormHandlerInterface::class);
        $this->handlerRegistry->expects(self::once())
            ->method('get')
            ->with($argHandler)
            ->willReturn($handler);

        $provider = $this->createMock(FormTemplateDataProviderInterface::class);
        $this->formTemplateDataProviderResolver->expects(self::once())
            ->method('resolve')
            ->with($argResultProvider)
            ->willReturn($provider);

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects(self::once())
            ->method('create')
            ->with($argForm, $argData)
            ->willReturn($form);

        $this->updateFactory->createUpdate($argData, $argForm, $argHandler, $argResultProvider);
    }

    public function testCreateArgCallbacks(): void
    {
        $argData = (object)[];
        $argForm = $this->createMock(FormInterface::class);
        $argHandler = function () {
            return true;
        };
        $argResultProvider = function () {
            return ['provider result'];
        };

        $this->formTemplateDataProviderResolver->expects(self::once())
            ->method('resolve')
            ->with($argResultProvider)
            ->willReturn(new CallbackFormTemplateDataProvider($argResultProvider));

        $update = $this->updateFactory->createUpdate($argData, $argForm, $argHandler, $argResultProvider);

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        self::assertTrue($update->handle($request));
        self::assertSame(['provider result'], $update->getTemplateData($request));
    }

    protected function assertExpectedConstruction(
        UpdateInterface $update,
        object $formData,
        FormInterface&MockObject $form,
        FormHandlerInterface&MockObject $handler,
        FormTemplateDataProviderInterface&MockObject $provider
    ): void {
        self::assertSame($formData, $update->getFormData());
        self::assertSame($form, $update->getForm());
        $request = $this->createMock(Request::class);
        $handler->expects(self::once())
            ->method('process')
            ->with($formData, $form, $request);
        $provider->expects(self::once())
            ->method('getData')
            ->with($formData, $form, $request);

        $update->handle($request);
        $update->getTemplateData($request);
    }
}
