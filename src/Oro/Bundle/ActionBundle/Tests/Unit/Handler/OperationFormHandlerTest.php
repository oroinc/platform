<?php

namespace Oro\Bundle\ActionBundle\Handler;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Component\Layout\ArrayCollection;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

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

    /**
     * @dataProvider processDataProvider
     *
     * @param Request $request
     * @param FlashBagInterface $flashBag
     * @param array $expected
     */
    public function testProcess(Request $request, FlashBagInterface $flashBag, array $expected)
    {
        $this->assertEquals($expected, $this->handler->process('operation', $request, $flashBag));
    }

    /**
     * @return \Generator
     */
    public function processDataProvider()
    {
        return [$this->caseSimpleProcess()];
    }

    private function caseSimpleProcess()
    {
        $actionData = new ActionData();
        $this->contextHelper->expects($this->once())
            ->method('getActionData')->willReturn($actionData);

        $operation = $this->getOperation('form_type', $actionData, ['formOption' => 'formOptionValue']);

        $request = new Request(['_wid' => 'widValue', 'formUrl' => 'formUrlValue']);

        $form = $this->getMock(FormInterface::class);

        $form->expects($this->once())->method('handleRequest')->with($request);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $operation->expects($this->once())->method('execute')->with($actionData, new ArrayCollection());

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

        $flashBug = $this->getMock(FlashBagInterface::class);
        $flashBug->expects($this->once())->method('all')->willReturn([]);

        $expected = [
            'data' => 'blah'
        ];

        return [$request, $flashBug, $expected];
    }

    /**
     * @param string $formType
     * @param ActionData $actionData
     * @param array $formOptions
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getOperation($formType, ActionData $actionData, array $formOptions)
    {
        $operation = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $definition = $this->getMockBuilder(OperationDefinition::class)->disableOriginalConstructor()->getMock();

        $operation->expects($this->once())->method('getDefinition')->willReturn($definition);

        $definition->expects($this->once())->method('getFormType')->willReturn($formType);
        $definition->expects($this->once())->method('getFormOptions')->with($actionData)->willReturn($formOptions);

        $this->operationRegistry->expects($this->once())
            ->method('findByName')->with('operation')->willReturn($operation);

        return $operation;
    }
}
