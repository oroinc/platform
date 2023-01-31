<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDataSerializeListener;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class WorkflowDataSerializeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowAwareSerializer|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WorkflowDataSerializeListener */
    private $listener;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(WorkflowAwareSerializer::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $container = TestContainerBuilder::create()
            ->add('oro_workflow.serializer.data.serializer', $this->serializer)
            ->getContainer($this);

        $this->listener = new WorkflowDataSerializeListener($container, $this->doctrineHelper);
    }

    private function getEntitySerializer(WorkflowItem $entity): mixed
    {
        return ReflectionUtil::getPropertyValue($entity, 'serializer');
    }

    private function getEntitySerializedData(WorkflowItem $entity): mixed
    {
        return ReflectionUtil::getPropertyValue($entity, 'serializedData');
    }

    public function testPostLoad()
    {
        $em = $this->createMock(EntityManager::class);

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects(self::once())
            ->method('getRelatedEntity')
            ->willReturn(\stdClass::class);

        $entity = new WorkflowItem();
        $entity->setDefinition($definition);

        $args = new LifecycleEventArgs($entity, $em);

        $this->serializer->expects(self::never())
            ->method('serialize');
        $this->serializer->expects(self::never())
            ->method('deserialize');

        $this->listener->postLoad($entity, $args);

        self::assertSame($this->serializer, $this->getEntitySerializer($entity));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnFlushAndPostFlush()
    {
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects(self::any())
            ->method('getEntityAttributeName')
            ->willReturn('entity');
        $definition->expects(self::any())
            ->method('getVirtualAttributes')
            ->willReturn([]);

        $entity1 = new WorkflowItem();
        $entity2 = new WorkflowItem();
        $entity4 = new WorkflowItem();
        $entity5 = new WorkflowItem();

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

        $this->serializer->expects($this->never())
            ->method('deserialize');

        $this->serializer->expects(self::exactly(3))
            ->method('setWorkflowName')
            ->withConsecutive(
                [$entity1->getWorkflowName()],
                [$entity2->getWorkflowName()],
                [$entity4->getWorkflowName()]
            );

        $this->serializer->expects(self::exactly(3))
            ->method('serialize')
            ->with(self::isInstanceOf(WorkflowData::class), 'json')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function ($data) use ($data1, $expectedSerializedData1) {
                    self::assertEquals($data1, $data);

                    return $expectedSerializedData1;
                }),
                new ReturnCallback(function ($data) use ($data2, $expectedSerializedData2) {
                    self::assertEquals($data2, $data);

                    return $expectedSerializedData2;
                }),
                new ReturnCallback(function ($data) use ($data4, $expectedSerializedData4) {
                    self::assertEquals($data4, $data);

                    return $expectedSerializedData4;
                })
            );

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$entity1, $entity2, $entity3]);
        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entity4, $entity5, $entity6]);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $em->expects(self::once())
            ->method('flush');

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush(new PostFlushEventArgs($em));

        self::assertEquals($expectedSerializedData1, $this->getEntitySerializedData($entity1));
        self::assertEquals($expectedSerializedData2, $this->getEntitySerializedData($entity2));
        self::assertEquals($expectedSerializedData4, $this->getEntitySerializedData($entity4));
        self::assertNull($this->getEntitySerializedData($entity5));

        self::assertFalse($entity1->getData()->isModified());
        self::assertFalse($entity2->getData()->isModified());
        self::assertFalse($entity4->getData()->isModified());
        self::assertFalse($entity5->getData()->isModified());
    }

    public function testOnFlushAndPostFlushWithAttributesThatShouldBeRemoved()
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

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects(self::once())
            ->method('getEntityAttributeName')
            ->willReturn('entity_attr');
        $definition->expects(self::once())
            ->method('getVirtualAttributes')
            ->willReturn($virtualAttributes);
        $definition->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $data = new WorkflowData(
            ['virtual_attr' => 'value1', 'var1' => 'data1', 'var2' => 'data2', 'entity_attr' => 'value2']
        );
        $data->set('normal_attr', 'value3');

        $item = new WorkflowItem();
        $item->setData($data)->setDefinition($definition);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$item]);
        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $expectedData = (new WorkflowData())->set('normal_attr', 'value3');
        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with($expectedData);

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush(new PostFlushEventArgs($em));
    }
}
