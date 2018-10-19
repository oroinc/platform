<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Template;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Template\FormSubmitTemplateResponseProcessor;
use Symfony\Component\HttpFoundation\Response;

class FormSubmitTemplateResponseProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ViewHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $viewHandler;

    /** @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject */
    protected $twig;

    /** @var FormSubmitTemplateResponseProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->viewHandler = $this->createMock(ViewHandlerInterface::class);
        $this->twig = $this->createMock(\Twig_Environment::class);

        $this->processor = new FormSubmitTemplateResponseProcessor($this->viewHandler, $this->twig);
    }

    public function testCompleteResponseOk()
    {
        $context = $this->createContext('message1', 200);

        $this->viewHandler->expects($this->never())->method('handle');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'OroWorkflowBundle:Widget:widget/transitionComplete.html.twig',
                [
                    'response' => null,
                    'responseCode' => 200,
                    'responseMessage' => 'message1',
                    'transitionSuccess' => true,
                ]
            )
            ->willReturn('content1');

        $this->processor->process($context);

        $this->assertEquals(new Response('content1'), $context->getResult());
        $this->assertTrue($context->isProcessed());
    }

    public function testCompleteResponseWithErrorCode()
    {
        $context = $this->createContext('message2', 500);

        $this->viewHandler->expects($this->never())->method('handle');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'OroWorkflowBundle:Widget:widget/transitionComplete.html.twig',
                [
                    'response' => null,
                    'responseCode' => 500,
                    'responseMessage' => 'message2',
                    'transitionSuccess' => false,
                ]
            )
            ->willReturn('content2');

        $this->processor->process($context);

        $response = $context->getResult();

        $this->assertEquals(new Response('content2'), $response);
        $this->assertTrue($context->isProcessed());
    }

    public function testCompleteResponseWithoutCode()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');

        $context = $this->createContext('message3', null, $workflowItem);

        $view = View::create(['workflowItem' => $workflowItem])->setFormat('json');

        $this->viewHandler->expects($this->once())->method('handle')
            ->with($view)
            ->willReturn(new Response(json_encode('content3'), 200));

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'OroWorkflowBundle:Widget:widget/transitionComplete.html.twig',
                [
                    'response' => 'content3',
                    'responseCode' => 200,
                    'responseMessage' => 'message3',
                    'transitionSuccess' => true,
                ]
            )
            ->willReturn('content3');

        $this->processor->process($context);

        $response = $context->getResult();

        $this->assertEquals(new Response('content3'), $response);
        $this->assertTrue($context->isProcessed());
    }

    /**
     * @param string $message
     * @param integer|null $code
     * @param WorkflowItem|null $workflowItem
     * @return TransitionContext
     */
    protected function createContext($message, $code = null, WorkflowItem $workflowItem = null)
    {
        $context = new TransitionContext();
        $context->setResultType(new TemplateResultType());
        $context->set('responseMessage', $message);

        if ($code) {
            $context->set('responseCode', $code);
        }

        if ($workflowItem) {
            $context->setWorkflowItem($workflowItem);
        }

        return $context;
    }

    public function testShouldSkipUnsupportedResponseTypes()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())->method('getWorkflowItem');

        $this->processor->process($context);
    }
}
