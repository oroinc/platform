<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Context;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Bundle\WorkflowBundle\Serializer\Handler\WorkflowResultHandler;

class WorkflowResultHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WorkflowResultHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->handler = new WorkflowResultHandler($this->doctrineHelper);
    }

    public function testWorkflowResultToJsonForScalar()
    {
        $workflowResult = new WorkflowResult(['foo' => 'bar']);
        $expectedResult = (object)['foo' => 'bar'];

        $visitor = $this->createMock(SerializationVisitorInterface::class);
        $visitor->expects($this->never())
            ->method($this->anything());

        $context = $this->createMock(Context::class);
        $context->expects($this->never())
            ->method($this->anything());

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            $expectedResult,
            $this->handler->workflowResultToJson($visitor, $workflowResult, [], $context)
        );
    }

    public function testWorkflowResultToJsonForCollection()
    {
        $collection = new ArrayCollection(['bar' => 'baz']);
        $workflowResult = new WorkflowResult(['foo' => $collection]);
        $expectedResult = (object)['foo' => ['bar' => 'baz']];

        $visitor = $this->createMock(SerializationVisitorInterface::class);
        $visitor->expects($this->never())
            ->method($this->anything());

        $context = $this->createMock(Context::class);
        $context->expects($this->never())
            ->method($this->anything());

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($this->identicalTo($collection))
            ->willReturn(false);

        $this->assertEquals(
            $expectedResult,
            $this->handler->workflowResultToJson($visitor, $workflowResult, [], $context)
        );
    }

    public function testWorkflowResultToJsonForObject()
    {
        $object = $this->createMock(\stdClass::class);
        $workflowResult = new WorkflowResult(['foo' => $object]);
        $expectedResult = (object)['foo' => $object];

        $visitor = $this->createMock(SerializationVisitorInterface::class);
        $visitor->expects($this->never())
            ->method($this->anything());

        $context = $this->createMock(Context::class);
        $context->expects($this->never())
            ->method($this->anything());

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($this->identicalTo($object))
            ->willReturn(false);

        $this->assertEquals(
            $expectedResult,
            $this->handler->workflowResultToJson($visitor, $workflowResult, [], $context)
        );
    }

    public function testWorkflowResultToJsonForEntity()
    {
        $entity = $this->createMock(\stdClass::class);
        $workflowResult = new WorkflowResult(['foo' => $entity]);
        $expectedResult = (object)['foo' => ['id' => 100]];

        $visitor = $this->createMock(SerializationVisitorInterface::class);
        $visitor->expects($this->never())
            ->method($this->anything());

        $context = $this->createMock(Context::class);
        $context->expects($this->never())
            ->method($this->anything());

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($this->identicalTo($entity))
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityIdentifier')
            ->with($this->identicalTo($entity))
            ->willReturn(['id' => 100]);

        $this->assertEquals(
            $expectedResult,
            $this->handler->workflowResultToJson($visitor, $workflowResult, [], $context)
        );
    }
}
