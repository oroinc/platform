<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\StartWorkflowItemProcessor;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StartWorkflowItemProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var StartWorkflowItemProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->processor = new StartWorkflowItemProcessor($this->doctrineHelper);
    }

    public function testSkipFailures()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('hasError')
            ->willReturn(true);

        $context->expects($this->never())
            ->method('getWorkflow');

        $this->processor->process($context);
    }

    public function testSkipContextWithWorkflowItem()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('hasWorkflowItem')
            ->willReturn(true);

        $context->expects($this->never())
            ->method('getWorkflow');

        $this->processor->process($context);
    }

    public function testGetEntityByIdAndWorkflowItem()
    {
        $entity = (object)['id' => 42];
        $initialData = ['initial_data'];

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->once())
            ->method('getRelatedEntity')
            ->willReturn(\stdClass::class);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        $workflow->expects($this->once())
            ->method('createWorkflowItem')
            ->with($entity, $initialData)
            ->willReturn($workflowItem);

        $context = new TransitionContext();
        $context->setWorkflow($workflow);
        $context->set(TransitionContext::ENTITY_ID, 42);
        $context->set(TransitionContext::INIT_DATA, $initialData);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(\stdClass::class, 42)
            ->willReturn($entity);

        $this->processor->process($context);

        $this->assertSame($workflowItem, $context->getWorkflowItem());
    }

    public function testCreateEntityAndWorkflowItem()
    {
        $initialData = ['initial_data'];
        $entity = (object)['id' => 1];

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->once())
            ->method('getRelatedEntity')
            ->willReturn(\stdClass::class);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        $workflow->expects($this->once())
            ->method('createWorkflowItem')
            ->with($entity, $initialData)
            ->willReturn($workflowItem);

        $context = new TransitionContext();
        $context->setWorkflow($workflow);
        $context->set(TransitionContext::ENTITY_ID, null);
        $context->set(TransitionContext::INIT_DATA, $initialData);

        $this->doctrineHelper->expects($this->once())
            ->method('createEntityInstance')
            ->with(\stdClass::class)
            ->willReturn($entity);

        $this->processor->process($context);

        $this->assertSame($workflowItem, $context->getWorkflowItem());
    }

    public function testNotManageableEntityExceptionProcessing()
    {
        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->once())
            ->method('getRelatedEntity')
            ->willReturn(\stdClass::class);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        $workflow->expects($this->never())
            ->method('createWorkflowItem');

        $context = new TransitionContext();
        $context->setWorkflow($workflow);
        $context->set(TransitionContext::ENTITY_ID, null);

        $emException = new NotManageableEntityException(\stdClass::class);

        $this->doctrineHelper->expects($this->once())
            ->method('createEntityInstance')
            ->with(\stdClass::class)
            ->willThrowException($emException);

        $this->processor->process($context);

        $this->assertFalse($context->hasWorkflowItem());
        $this->assertTrue($context->hasError());
        $this->assertEquals('normalize', $context->getFirstGroup());
        $this->assertEquals(
            new BadRequestHttpException($emException->getMessage(), $emException),
            $context->getError()
        );
    }
}
