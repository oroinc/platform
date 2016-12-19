<?php

namespace Oro\Bundle\ActionBundle\Handler;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Exception\OperationNotFoundException;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

class OperationFormHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $formFactory;

    /** @var ContextHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $contextHelper;

    /** @var OperationRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $operationRegistry;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    /** @var OperationFormHandler */
    private $handler;

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder(FormFactoryInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->contextHelper = $this->getMockBuilder(ContextHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->operationRegistry = $this->getMockBuilder(OperationRegistry::class)
            ->disableOriginalConstructor()->getMock();
        $this->translator = $this->getMock(TranslatorInterface::class);

        $this->handler = new OperationFormHandler(
            $this->formFactory,
            $this->contextHelper,
            $this->operationRegistry,
            $this->translator
        );
    }

    public function testProcessSimple()
    {
        $actionData = $this->actionDataFromContext();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);

        $request = new Request(['_wid' => 'widValue', 'fromUrl' => 'fromUrlValue']);

        $form = $this->formProcessing($request, $actionData, $operation);

        $formView = $this->formViewRetrieval($form);

        $errors = new ArrayCollection();
        $operation->expects($this->once())->method('execute')->with($actionData, $errors);

        $expected = [
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
                'success' => true
            ]
        ];

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
        $flashBag = $this->getMock(FlashBagInterface::class);

        $this->assertEquals($expected, $this->handler->process('operation', $request, $flashBag));
    }

    public function testProcessRedirect()
    {
        $actionData = $this->actionDataFromContext();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);

        $request = new Request(['_wid' => null, 'fromUrl' => 'fromUrlValue']);

        $this->formProcessing($request, $actionData, $operation);

        $operation->expects($this->once())->method('execute')->with($this->callback(
            function (ActionData $actionData, ArrayCollection $errors = null) {
                $actionData['redirectUrl'] = 'http://redirect.url/';

                return true;
            }
        ));

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
        $flashBag = $this->getMock(FlashBagInterface::class);

        $this->assertEquals(
            new RedirectResponse('http://redirect.url/', 302),
            $this->handler->process('operation', $request, $flashBag)
        );
    }

    public function testProcessRefreshDatagrid()
    {
        $actionData = $this->actionDataFromContext();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);

        $request = new Request(['_wid' => 'widValue', 'fromUrl' => 'fromUrlValue']);

        $form = $this->formProcessing($request, $actionData, $operation);

        $formView = $this->formViewRetrieval($form);

        $errors = new ArrayCollection();
        $operation->expects($this->once())->method('execute')->with($this->callback(
            function (ActionData $actionData, ArrayCollection $errors = null) {
                $actionData['refreshGrid'] = ['refreshed-grid'];

                return true;
            }
        ));

        $expected = [
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
                'flashMessages' => ['message1']
            ]
        ];

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
        $flashBag = $this->getMock(FlashBagInterface::class);
        $flashBag->expects($this->once())->method('all')->willReturn(['message1']);

        $this->assertEquals($expected, $this->handler->process('operation', $request, $flashBag));
    }

    public function testProcessOperationNotFoundException()
    {
        $this->operationRegistry->expects($this->once())
            ->method('findByName')->with('operation')->willReturn(null);

        $this->actionDataFromContext();

        $this->setExpectedException(OperationNotFoundException::class, 'Operation with name "operation" not found');

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
        $flashBag = $this->getMock(FlashBagInterface::class);

        $this->handler->process('operation', new Request(), $flashBag);
    }

    public function testProcessErrorHandlingForWidget()
    {
        $actionData = $this->actionDataFromContext();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);

        $request = new Request(['_wid' => 'widValue', 'fromUrl' => 'fromUrlValue']);

        $form = $this->formProcessing($request, $actionData, $operation);

        $formView = $this->formViewRetrieval($form);

        $operation->expects($this->once())->method('execute')->willThrowException(new \Exception('err msg'));

        $expected = [
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
        ];

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
        $flashBag = $this->getMock(FlashBagInterface::class);
        $flashBag->expects($this->once())->method('all')->willReturn(['flash bag message']);

        $this->assertEquals($expected, $this->handler->process('operation', $request, $flashBag));
    }

    public function testProcessErrorHandlingForWidgetWithManyErrors()
    {
        $actionData = $this->actionDataFromContext();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);

        $request = new Request(['_wid' => null, 'fromUrl' => 'fromUrlValue']);

        $form = $this->formProcessing($request, $actionData, $operation);

        $formView = $this->formViewRetrieval($form);

        $operation->expects($this->once())->method('execute')->with(
            $this->callback(function (ActionData $actionData) {
                $actionData['refreshGrid'] = ['grid'];

                return true;
            }),
            $this->callback(function (ArrayCollection $collection) {
                $collection->add(['message' => 'message', 'parameters' => []]);

                return true;
            })
        );

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

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
        $flashBag = $this->getMock(FlashBagInterface::class);
        //will throw custom exception in getResponseData
        $flashBag->expects($this->once())->method('all')->willThrowException(new \Exception('exception message'));

        $this->translator->expects($this->any())->method('trans')->willReturnArgument(0);
        $flashBag->expects($this->at(1))->method('add')->with('error', 'exception message: message');

        $this->assertEquals($expected, $this->handler->process('operation', $request, $flashBag));
    }

    public function testProcessErrorHandlingForNotWidget()
    {
        $actionData = $this->actionDataFromContext();

        $operation = $this->operationRetrieval('form_type', $actionData, ['formOption' => 'formOptionValue']);

        $request = new Request(['_wid' => null, 'fromUrl' => 'fromUrlValue']);

        $form = $this->formProcessing($request, $actionData, $operation);

        $formView = $this->formViewRetrieval($form);

        $operation->expects($this->once())->method('execute')->willThrowException(new \Exception('err msg'));

        $expected = [
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
        ];

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
        $flashBag = $this->getMock(FlashBagInterface::class);
        $this->translator->expects($this->once())->method('trans')->with('err msg')->willReturnArgument(0);
        $flashBag->expects($this->once())->method('add')->with('error', 'err msg');

        $this->assertEquals($expected, $this->handler->process('operation', $request, $flashBag));
    }

    /**
     * @param string $formType
     * @param ActionData $actionData
     * @param array $formOptions
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    private function operationRetrieval($formType, ActionData $actionData, array $formOptions)
    {
        $operation = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();

        $this->operationRegistry->expects($this->once())
            ->method('findByName')->with('operation')->willReturn($operation);

        $definition = $this->getMockBuilder(OperationDefinition::class)->disableOriginalConstructor()->getMock();

        $operation->expects($this->once())->method('isAvailable')->with($actionData)->willReturn(true);
        $operation->expects($this->once())->method('getDefinition')->willReturn($definition);
        $definition->expects($this->once())->method('getFormType')->willReturn($formType);
        $operation->expects($this->once())->method('getFormOptions')->with($actionData)->willReturn($formOptions);

        return $operation;
    }

    /**
     * @param Request $request
     * @param ActionData $actionData
     * @param Operation $operation
     * @return  FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function formProcessing($request, $actionData, $operation)
    {
        $form = $this->getMock(FormInterface::class);

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
        $form->expects($this->once())->method('isValid')->willReturn(true);

        return $form;
    }

    /**
     * @param FormInterface|\PHPUnit_Framework_MockObject_MockObject $form
     * @return FormView|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function formViewRetrieval($form)
    {
        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('createView')->willReturn($formView);

        return $formView;
    }

    /**
     * @return ActionData
     */
    protected function actionDataFromContext()
    {
        $actionData = new ActionData();
        $this->contextHelper->expects($this->once())
            ->method('getActionData')->willReturn($actionData);

        return $actionData;
    }
}
