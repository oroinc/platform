<?php

namespace Oro\Bundle\ActionBundle\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\ActionBundle\Exception\OperationNotFoundException;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

class OperationFormHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ContextHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $contextHelper;

    /** @var OperationRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $operationRegistry;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var OperationFormHandler */
    private $handler;

    /** @var FlashBagInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $flashBag;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->contextHelper = $this->createMock(ContextHelper::class);
        $this->contextHelper->expects($this->any())->method('getActionData')->willReturn(new ActionData());

        $this->operationRegistry = $this->createMock(OperationRegistry::class);

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->handler = new OperationFormHandler(
            $this->formFactory,
            $this->contextHelper,
            $this->operationRegistry,
            $this->translator
        );

        $this->flashBag = $this->createMock(FlashBagInterface::class);
    }

    public function testProcessSimple()
    {
        $actionData = $this->contextHelper->getActionData();
        $errors = new ArrayCollection();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue'], false);
        $operation->expects($this->once())->method('execute')->with($actionData, $errors);

        $request = new Request(['_wid' => 'widValue', 'fromUrl' => 'fromUrlValue']);

        $form = $this->formProcessing($request, $actionData, $operation);
        $formView = $this->formViewRetrieval($form);

        $this->flashBag->expects($this->once())->method('all')->willReturn(['flash bag message']);

        $this->assertEquals(
            [
                '_wid' => 'widValue',
                'fromUrl' => 'fromUrlValue',
                'operation' => $operation,
                'actionData' => $actionData,
                'errors' => $errors,
                'messages' => [],
                'form' => $formView,
                'context' => [
                    'form' => $form
                ],
                'response' => [
                    'success' => true,
                    'pageReload' => false,
                    'flashMessages' => ['flash bag message']
                ]
            ],
            $this->handler->process('operation', $request, $this->flashBag)
        );
    }

    public function testProcessRedirect()
    {
        $actionData = $this->contextHelper->getActionData();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);
        $operation->expects($this->once())->method('execute')->with($this->callback(
            function (ActionData $actionData) {
                $actionData->set('redirectUrl', 'http://redirect.url/');

                return true;
            }
        ));

        $request = new Request(['_wid' => null, 'fromUrl' => 'fromUrlValue']);

        $this->formProcessing($request, $actionData, $operation);

        $response = $this->handler->process('operation', $request, $this->flashBag);

        $this->assertEquals('http://redirect.url/', $response->getTargetUrl());
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testProcessRefreshDatagrid()
    {
        $actionData = $this->contextHelper->getActionData();
        $errors = new ArrayCollection();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);
        $operation->expects($this->once())->method('execute')->with($this->callback(
            function (ActionData $actionData) {
                $actionData->set('refreshGrid', ['refreshed-grid']);

                return true;
            }
        ));

        $request = new Request(['_wid' => 'widValue', 'fromUrl' => 'fromUrlValue']);

        $form = $this->formProcessing($request, $actionData, $operation);
        $formView = $this->formViewRetrieval($form);

        $this->flashBag->expects($this->once())->method('all')->willReturn(['message1']);

        $this->assertEquals(
            [
                '_wid' => 'widValue',
                'fromUrl' => 'fromUrlValue',
                'operation' => $operation,
                'actionData' => $actionData,
                'errors' => $errors,
                'messages' => [],
                'form' => $formView,
                'context' => [
                    'form' => $form,
                    'refreshGrid' => ['refreshed-grid']
                ],
                'response' => [
                    'success' => true,
                    'refreshGrid' => ['refreshed-grid'],
                    'flashMessages' => ['message1'],
                    'pageReload' => true
                ]
            ],
            $this->handler->process('operation', $request, $this->flashBag)
        );
    }

    public function testProcessOperationNotFoundException()
    {
        $this->operationRegistry->expects($this->once())
            ->method('findByName')
            ->with('operation')
            ->willReturn(null);

        $this->expectException(OperationNotFoundException::class);
        $this->expectExceptionMessage('Operation with name "operation" not found');

        $this->handler->process('operation', new Request(), $this->flashBag);
    }

    public function testProcessForbiddenOperationException()
    {
        $operation = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $operation->expects($this->once())->method('isAvailable')->willReturn(false);

        $this->operationRegistry->expects($this->once())
            ->method('findByName')
            ->with('operation')
            ->willReturn($operation);

        $this->expectException(ForbiddenOperationException::class);

        $this->handler->process('operation', new Request(), $this->flashBag);
    }

    public function testProcessErrorHandlingForWidget()
    {
        $actionData = $this->contextHelper->getActionData();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);
        $operation->expects($this->once())->method('execute')->willThrowException(new \Exception('err msg'));

        $request = new Request(['_wid' => 'widValue', 'fromUrl' => 'fromUrlValue']);

        $form = $this->formProcessing($request, $actionData, $operation);
        $formView = $this->formViewRetrieval($form);

        $this->flashBag->expects($this->once())->method('all')->willReturn(['flash bag message']);

        $this->assertEquals(
            [
                '_wid' => 'widValue',
                'fromUrl' => 'fromUrlValue',
                'operation' => $operation,
                'actionData' => $actionData,
                'errors' => new ArrayCollection([['message' => 'err msg', 'parameters' => []]]),
                'messages' => ['flash bag message'],
                'form' => $formView,
                'context' => [
                    'form' => $form
                ]
            ],
            $this->handler->process('operation', $request, $this->flashBag)
        );
    }

    public function testProcessErrorHandlingForWidgetWithManyErrors()
    {
        $actionData = $this->contextHelper->getActionData();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);
        $operation->expects($this->once())->method('execute')->with(
            $this->callback(function (ActionData $actionData) {
                $actionData->set('refreshGrid', ['grid']);

                return true;
            }),
            $this->callback(function (ArrayCollection $collection) {
                $collection->add(['message' => 'message', 'parameters' => []]);

                return true;
            })
        );

        $request = new Request(['_wid' => null, 'fromUrl' => 'fromUrlValue']);

        $form = $this->formProcessing($request, $actionData, $operation);
        $formView = $this->formViewRetrieval($form);

        $expected = [
            '_wid' => null,
            'fromUrl' => 'fromUrlValue',
            'operation' => $operation,
            'actionData' => $actionData,
            'errors' => new ArrayCollection([['message' => 'message', 'parameters' => []]]),
            'messages' => [],
            'form' => $formView,
            'context' => [
                'form' => $form,
                'refreshGrid' => ['grid']

            ]
        ];

        //will throw custom exception in getResponseData
        $this->flashBag->expects($this->once())->method('all')->willThrowException(new \Exception('exception message'));

        $this->translator->expects($this->any())->method('trans')->willReturnArgument(0);
        $this->flashBag->expects($this->at(1))->method('add')->with('error', 'exception message: message');

        $this->assertEquals($expected, $this->handler->process('operation', $request, $this->flashBag));
    }

    public function testProcessErrorHandlingForNotWidget()
    {
        $actionData = $this->contextHelper->getActionData();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);
        $operation->expects($this->once())->method('execute')->willThrowException(new \Exception('err msg'));

        $request = new Request(['_wid' => null, 'fromUrl' => 'fromUrlValue']);

        $form = $this->formProcessing($request, $actionData, $operation);
        $formView = $this->formViewRetrieval($form);

        $this->translator->expects($this->once())->method('trans')->with('err msg')->willReturnArgument(0);
        $this->flashBag->expects($this->once())->method('add')->with('error', 'err msg');

        $this->assertEquals(
            [
                '_wid' => null,
                'fromUrl' => 'fromUrlValue',
                'operation' => $operation,
                'actionData' => $actionData,
                'errors' => new ArrayCollection([]),
                'messages' => [],
                'form' => $formView,
                'context' => [
                    'form' => $form
                ]
            ],
            $this->handler->process('operation', $request, $this->flashBag)
        );
    }

    /**
     * @param string $formType
     * @param ActionData $actionData
     * @param array $formOptions
     * @param bool $pageReload
     * @return Operation|\PHPUnit\Framework\MockObject\MockObject
     */
    private function operationRetrieval($formType, ActionData $actionData, array $formOptions, bool $pageReload = true)
    {
        $definition = $this->createMock(OperationDefinition::class);
        $definition->expects($this->once())->method('getFormType')->willReturn($formType);
        $definition->expects($this->any())->method('isPageReload')->willReturn($pageReload);

        $operation = $this->createMock(Operation::class);
        $operation->expects($this->once())->method('isAvailable')->with($actionData)->willReturn(true);
        $operation->expects($this->any())->method('getDefinition')->willReturn($definition);
        $operation->expects($this->once())->method('getFormOptions')->with($actionData)->willReturn($formOptions);

        $this->operationRegistry->expects($this->once())
            ->method('findByName')
            ->with('operation')
            ->willReturn($operation);

        return $operation;
    }

    /**
     * @param Request $request
     * @param ActionData $actionData
     * @param Operation $operation
     * @return  FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function formProcessing($request, $actionData, $operation)
    {
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                'form_type',
                $actionData,
                [
                    'operation' => $operation,
                    'formOption' => 'formOptionValue'
                ]
            )
            ->willReturn($form);

        $form->expects($this->once())->method('handleRequest')->with($request);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);

        return $form;
    }

    /**
     * @param FormInterface|\PHPUnit\Framework\MockObject\MockObject $form
     * @return FormView|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function formViewRetrieval($form)
    {
        $formView = $this->getMockBuilder(FormView::class)->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('createView')->willReturn($formView);

        return $formView;
    }
}
