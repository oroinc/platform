<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutDialogResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Layout\LayoutDialogDataStartTransitionProcessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class LayoutDialogDataStartTransitionProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutDialogDataStartTransitionProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new LayoutDialogDataStartTransitionProcessor();
    }

    public function testData()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');

        $transition = $this->createMock(Transition::class);

        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['entityId', 0, 42],
                ['entityClass', null, \stdClass::class]
            ]);

        $formView = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $context = new TransitionContext();
        $context->setResultType(new LayoutDialogResultType('route_name'));
        $context->setWorkflowItem($workflowItem);
        $context->setTransition($transition);
        $context->setRequest($request);
        $context->setForm($form);
        $context->setTransitionName('transitionName');
        $context->setWorkflowName('workflowName');

        $this->processor->process($context);

        $this->assertSame(
            [
                'data' => [
                    'workflowName' => 'workflowName',
                    'workflowItem' => $workflowItem,
                    'transition' => $transition,
                    'transitionName' => 'transitionName',
                    'transitionFormView' => $formView,
                    'entityId' => 42,
                    'entityClass' => \stdClass::class,
                    'formRouteName' => 'route_name',
                    'originalUrl' => null
                ]
            ],
            $context->getResult()
        );

        $this->assertTrue($context->isProcessed());
    }

    public function skipUnsupportedResultTypeContext()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())
            ->method('getWorkflowName');

        $this->processor->process($context);
    }
}
