<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\WorkflowItem;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem\EntityWorkflowResultObjectSerializer;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem\WorkflowItemSerializer;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\WorkflowItem\Stub\TestObject;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\WorkflowItem\Stub\TestObjectWorkflowResultObjectSerializer;

class WorkflowItemSerializerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WorkflowItemSerializer */
    private $serializer;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $workflowResultObjectSerializer = new TestObjectWorkflowResultObjectSerializer(
            new EntityWorkflowResultObjectSerializer($this->doctrineHelper)
        );

        $this->serializer = new WorkflowItemSerializer($workflowResultObjectSerializer);
    }

    private function getWorkflowItem(array $workflowResult): WorkflowItem
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setId(1);
        $workflowItem->setWorkflowName('test name');
        $workflowItem->setEntityId('test_entity_id');
        $workflowItem->setEntityClass('Test\Entity');
        foreach ($workflowResult as $name => $value) {
            $workflowItem->getResult()->{$name} = $value;
        }

        return $workflowItem;
    }

    public function testSerializeWhenWorkflowResultIsEmpty(): void
    {
        $workflowItem = $this->getWorkflowItem([]);

        $this->doctrineHelper->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            [
                'id'            => 1,
                'workflow_name' => 'test name',
                'entity_id'     => 'test_entity_id',
                'entity_class'  => 'Test\Entity',
                'result'        => null
            ],
            $this->serializer->serialize($workflowItem)
        );
    }

    public function testSerializeWhenWorkflowResultHasOnlyScalars(): void
    {
        $workflowItem = $this->getWorkflowItem(['foo' => 'bar']);

        $this->doctrineHelper->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            [
                'id'            => 1,
                'workflow_name' => 'test name',
                'entity_id'     => 'test_entity_id',
                'entity_class'  => 'Test\Entity',
                'result'        => ['foo' => 'bar']
            ],
            $this->serializer->serialize($workflowItem)
        );
    }

    public function testSerializeWhenWorkflowResultHasCollection(): void
    {
        $collection = new ArrayCollection(['bar' => 'baz']);
        $workflowItem = $this->getWorkflowItem(['foo' => $collection]);

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntity');

        self::assertSame(
            [
                'id'            => 1,
                'workflow_name' => 'test name',
                'entity_id'     => 'test_entity_id',
                'entity_class'  => 'Test\Entity',
                'result'        => ['foo' => ['bar' => 'baz']]
            ],
            $this->serializer->serialize($workflowItem)
        );
    }

    public function testSerializeWhenWorkflowResultHasObjectThanCanBeSerialized(): void
    {
        $object = new TestObject('test');
        $workflowItem = $this->getWorkflowItem(['foo' => $object]);

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntity');

        self::assertSame(
            [
                'id'            => 1,
                'workflow_name' => 'test name',
                'entity_id'     => 'test_entity_id',
                'entity_class'  => 'Test\Entity',
                'result'        => ['foo' => ['code' => 'test']]
            ],
            $this->serializer->serialize($workflowItem)
        );
    }

    public function testSerializeWhenWorkflowResultHasObjectThanCannotBeSerialized(): void
    {
        $object = new TestObject();
        $workflowItem = $this->getWorkflowItem(['foo' => $object]);

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntity');

        self::assertSame(
            [
                'id'            => 1,
                'workflow_name' => 'test name',
                'entity_id'     => 'test_entity_id',
                'entity_class'  => 'Test\Entity',
                'result'        => null
            ],
            $this->serializer->serialize($workflowItem)
        );
    }

    public function testSerializeWhenWorkflowResultHasObjectThanDoesNotHaveSerializerToSerializeId(): void
    {
        $object = $this->createMock(\stdClass::class);
        $workflowItem = $this->getWorkflowItem(['foo' => $object]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntity')
            ->with(self::identicalTo($object))
            ->willReturn(false);

        self::assertSame(
            [
                'id'            => 1,
                'workflow_name' => 'test name',
                'entity_id'     => 'test_entity_id',
                'entity_class'  => 'Test\Entity',
                'result'        => null
            ],
            $this->serializer->serialize($workflowItem)
        );
    }

    public function testSerializeWhenWorkflowResultHasEntity(): void
    {
        $entity = $this->createMock(\stdClass::class);
        $workflowItem = $this->getWorkflowItem(['foo' => $entity]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntity')
            ->with(self::identicalTo($entity))
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifier')
            ->with(self::identicalTo($entity))
            ->willReturn(['id' => 100]);

        self::assertSame(
            [
                'id'            => 1,
                'workflow_name' => 'test name',
                'entity_id'     => 'test_entity_id',
                'entity_class'  => 'Test\Entity',
                'result'        => ['foo' => ['id' => 100]]
            ],
            $this->serializer->serialize($workflowItem)
        );
    }
}
