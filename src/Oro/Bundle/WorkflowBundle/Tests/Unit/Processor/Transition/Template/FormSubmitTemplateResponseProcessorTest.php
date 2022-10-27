<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Template;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Template\FormSubmitTemplateResponseProcessor;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem\WorkflowItemSerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class FormSubmitTemplateResponseProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowItemSerializerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowItemSerializer;

    /** @var ViewHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $viewHandler;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var FormSubmitTemplateResponseProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->workflowItemSerializer = $this->createMock(WorkflowItemSerializerInterface::class);
        $this->viewHandler = $this->createMock(ViewHandlerInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $this->processor = new FormSubmitTemplateResponseProcessor(
            $this->workflowItemSerializer,
            $this->viewHandler,
            $this->twig
        );
    }

    private function createContext(
        string $message,
        int $code = null,
        WorkflowItem $workflowItem = null
    ): TransitionContext {
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

    public function testCompleteResponseOk()
    {
        $context = $this->createContext('message1', 200);

        $this->workflowItemSerializer->expects($this->never())
            ->method('serialize');
        $this->viewHandler->expects($this->never())
            ->method('handle');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@OroWorkflow/Widget/widget/transitionComplete.html.twig',
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

        $this->workflowItemSerializer->expects($this->never())
            ->method('serialize');
        $this->viewHandler->expects($this->never())
            ->method('handle');

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@OroWorkflow/Widget/widget/transitionComplete.html.twig',
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
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $serializedWorkflowItem = ['name' => 'test_workflow'];

        $context = $this->createContext('message3', null, $workflowItem);

        $view = View::create(['workflowItem' => $serializedWorkflowItem])
            ->setFormat('json');
        $view->getContext()->setSerializeNull(true);

        $this->workflowItemSerializer->expects($this->once())
            ->method('serialize')
            ->with($this->identicalTo($workflowItem))
            ->willReturn($serializedWorkflowItem);
        $this->viewHandler->expects($this->once())
            ->method('handle')
            ->with($view)
            ->willReturn(new Response(json_encode('content3', JSON_THROW_ON_ERROR), 200));

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '@OroWorkflow/Widget/widget/transitionComplete.html.twig',
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

    public function testShouldSkipUnsupportedResponseTypes()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())
            ->method('getWorkflowItem');

        $this->processor->process($context);
    }
}
