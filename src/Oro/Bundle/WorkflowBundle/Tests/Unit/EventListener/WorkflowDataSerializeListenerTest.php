<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDataSerializeListener;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Component\TestUtils\Mocks\ServiceLink;
use PHPUnit\Framework\MockObject\MockObject;

class WorkflowDataSerializeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowDataSerializeListener */
    protected $listener;

    /** @var WorkflowAwareSerializer|MockObject */
    protected $serializer;

    /** @var DoctrineHelper|MockObject */
    protected $doctrineHelper;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(WorkflowAwareSerializer::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->listener = new WorkflowDataSerializeListener(new ServiceLink($this->serializer), $this->doctrineHelper);
    }

    public function testPostLoad()
    {
        /** @var EntityManager|MockObject $em */
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        /** @var WorkflowDefinition|MockObject $definition */
        $definition = $this->getMockBuilder(WorkflowDefinition::class)->disableOriginalConstructor()->getMock();
        $definition->expects(static::once())->method('getRelatedEntity')->willReturn('\stdClass');

        $entity = new class() extends WorkflowItem {
            public function xgetSerializer(): WorkflowAwareSerializer
            {
                return $this->serializer;
            }
        };

        $entity->setDefinition($definition);

        $args = new LifecycleEventArgs($entity, $em);

        $this->serializer->expects(static::never())->method('serialize');
        $this->serializer->expects(static::never())->method('deserialize');

        $this->listener->postLoad($entity, $args);

        static::assertSame($this->serializer, $entity->xgetSerializer());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnFlushAndPostFlush()
    {
        /** @var WorkflowDefinition|MockObject $definition */
        $definition = $this->getMockBuilder(WorkflowDefinition::class)->disableOriginalConstructor()->getMock();
        $definition->method('getEntityAttributeName')->willReturn('entity');
        $definition->method('getVirtualAttributes')->willReturn([]);

        $entity1 = new class() extends WorkflowItem {
            public function xgetSerializedData(): ?string
            {
                return $this->serializedData;
            }
        };
        $entity2 = clone $entity1;
        $entity4 = clone $entity1;
        $entity5 = clone $entity1;

        $entity1->setDefinition($definition);
        $entity1->setWorkflowName('workflow_1');
        $entity1->setSerializedData('_old_serialized_data');
        $data1 = new WorkflowData();
        $data1->foo = 'foo';
        $entity1->setData($data1);

        $entity2->setDefinition($definition);
        $entity2->setWorkflowName('workflow_2');
        $data2 = new WorkflowData();
        $data2->bar = 'bar';
        $entity2->setData($data2);

        $entity3 = new \stdClass();

        $entity4->setDefinition($definition);
        $entity4->setWorkflowName('workflow_4');
        $data4 = new WorkflowData();
        $data4->foo = 'baz';
        $entity4->setData($data4);

        $entity5->setDefinition($definition);
        $data5 = new WorkflowData(); // Leave this data not modified
        $entity5->setData($data5);

        $entity6 = new \stdClass();

        $expectedSerializedData1 = 'serialized_data_1';
        $expectedSerializedData2 = 'serialized_data_2';
        $expectedSerializedData4 = 'serialized_data_4';

        $this->serializer->expects($this->never())->method('deserialize');

        $this->serializer->expects(static::at(0))->method('setWorkflowName')
            ->with($entity1->getWorkflowName());
        $this->serializer->expects(static::at(1))->method('serialize')
            ->with(static::isInstanceOf(WorkflowData::class), 'json')
            ->willReturnCallback(
                function ($data) use ($data1, $expectedSerializedData1) {
                    static::assertEquals($data1, $data);
                    return $expectedSerializedData1;
                }
            );

        $this->serializer->expects(static::at(2))->method('setWorkflowName')
            ->with($entity2->getWorkflowName());
        $this->serializer->expects(static::at(3))->method('serialize')
            ->with(static::isInstanceOf(WorkflowData::class), 'json')
            ->willReturnCallback(
                function ($data) use ($data2, $expectedSerializedData2) {
                    static::assertEquals($data2, $data);
                    return $expectedSerializedData2;
                }
            );

        $this->serializer->expects(static::at(4))->method('setWorkflowName')
            ->with($entity4->getWorkflowName());
        $this->serializer->expects(static::at(5))->method('serialize')
            ->with(static::isInstanceOf(WorkflowData::class), 'json')
            ->willReturnCallback(
                function ($data) use ($data4, $expectedSerializedData4) {
                    static::assertEquals($data4, $data);
                    return $expectedSerializedData4;
                }
            );

        $entityManager = $this->getPostFlushEntityManagerMock(
            [
                [
                    'getScheduledEntityInsertions',
                    [],
                    static::returnValue([$entity1, $entity2, $entity3])
                ],
                [
                    'getScheduledEntityUpdates',
                    [],
                    static::returnValue([$entity4, $entity5, $entity6])
                ],
            ]
        );

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
        $this->listener->postFlush(new PostFlushEventArgs($entityManager));

        static::assertEquals($expectedSerializedData1, $entity1->xgetSerializedData());
        static::assertEquals($expectedSerializedData2, $entity2->xgetSerializedData());
        static::assertEquals($expectedSerializedData4, $entity4->xgetSerializedData());
        static::assertNull($entity5->xgetSerializedData());

        static::assertFalse($entity1->getData()->isModified());
        static::assertFalse($entity2->getData()->isModified());
        static::assertFalse($entity4->getData()->isModified());
        static::assertFalse($entity5->getData()->isModified());
    }

    public function testOnFlushAndPostFlushWithAttributesthatShouldBeRemoved()
    {
        $virtualAttributes = [
            'virtual_attr' => [],
        ];
        $configuration = [
            'variable_definitions' => [
                'variables' => [
                    'var1' => [],
                    'var2' => []
                ]
            ]
        ];

        /** @var WorkflowDefinition|MockObject $definition */
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects(static::once())->method('getEntityAttributeName')->willReturn('entity_attr');
        $definition->expects(static::once())->method('getVirtualAttributes')->willReturn($virtualAttributes);
        $definition->expects(static::once())->method('getConfiguration')->willReturn($configuration);

        $data = new WorkflowData(
            ['virtual_attr' => 'value1', 'var1' => 'data1', 'var2' => 'data2', 'entity_attr' => 'value2']
        );
        $data->set('normal_attr', 'value3');

        $item = new WorkflowItem();
        $item->setData($data)->setDefinition($definition);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects(static::once())->method('getScheduledEntityInsertions')->willReturn([$item]);
        $uow->expects(static::once())->method('getScheduledEntityUpdates')->willReturn([]);

        $em = $this->createMock(EntityManager::class);
        $em->expects(static::once())->method('getUnitOfWork')->willReturn($uow);

        $expectedData = (new WorkflowData())->set('normal_attr', 'value3');
        $this->serializer->expects(static::once())->method('serialize')->with($expectedData);

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush(new PostFlushEventArgs($em));
    }

    /**
     * @param array $uowExpectedCalls
     * @return MockObject|EntityManager
     */
    protected function getPostFlushEntityManagerMock(array $uowExpectedCalls)
    {
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $uow = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();

        $em->expects(static::any())->method('getUnitOfWork')->willReturn($uow);
        $em->expects(static::once())->method('flush');

        $index = 0;
        foreach ($uowExpectedCalls as $expectedCall) {
            $expectedCall = array_pad($expectedCall, 3, null);
            list($method, $with, $stub) = $expectedCall;
            $methodExpectation = $uow->expects(static::at($index++))->method($method);
            $methodExpectation = call_user_func_array([$methodExpectation, 'with'], $with);
            if ($stub) {
                $methodExpectation->will($stub);
            }
        }

        return $em;
    }
}
