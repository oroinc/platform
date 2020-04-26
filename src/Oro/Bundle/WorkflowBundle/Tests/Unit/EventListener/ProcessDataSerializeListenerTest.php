<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\EventListener\ProcessDataSerializeListener;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\TestUtils\Mocks\ServiceLink;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Serializer\SerializerInterface;

class ProcessDataSerializeListenerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_CLASS = 'Test\Class';

    /** @var MockObject|SerializerInterface */
    protected $serializer;

    /** @var ProcessDataSerializeListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->serializer = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->listener = new class(new ServiceLink($this->serializer)) extends ProcessDataSerializeListener {
            public function xgetScheduledEntities(): array
            {
                return $this->scheduledEntities;
            }
        };
    }

    /**
     * @dataProvider onFlushProvider
     */
    public function testOnFlush($entities, $expected)
    {
        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();
        $unitOfWork->expects(static::once())->method('getScheduledEntityInsertions')->willReturn($entities);
        $unitOfWork->expects(static::once())->method('getScheduledEntityUpdates')->willReturn($entities);

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->expects(static::once())->method('getUnitOfWork')->willReturn($unitOfWork);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));

        static::assertEquals($expected, $this->listener->xgetScheduledEntities());
    }

    public function onFlushProvider()
    {
        $stdClass   = new \stdClass();
        $processJob = new ProcessJob();
        $processJob->getData()->set('key', 'value');

        return [
            'string instead class' => [
                'entities' => ['some class'],
                'expected' => []
            ],
            'invalid class' => [
                'entities' => [$stdClass],
                'expected' => []
            ],
            'valid class' => [
                'entities' => [$processJob],
                'expected' => [$processJob, $processJob]
            ],
            'several' => [
                'entities' => [$processJob, $stdClass, 'str', $stdClass],
                'expected' => [$processJob, $processJob]
            ],
        ];
    }

    public function testPostFlush()
    {
        $serializedData    = 'serializedData';
        $processDefinition = new ProcessDefinition();
        $processDefinition->setRelatedEntity(self::TEST_CLASS);

        $processTrigger = new ProcessTrigger();
        $processTrigger->setDefinition($processDefinition);

        $processData = new ProcessData();
        $processData->set('test', 'value');

        $processJob = new ProcessJob();
        $processJob->setProcessTrigger($processTrigger)
            ->setData($processData);

        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();
        $unitOfWork->expects(static::at(0))->method('getScheduledEntityInsertions')->willReturn([$processJob]);
        $unitOfWork->expects(static::at(1))->method('getScheduledEntityUpdates')->willReturn([$processJob]);

        $entityId   = 1;
        $entityHash = ProcessJob::generateEntityHash(self::TEST_CLASS, $entityId);

        $this->serializer->expects(static::exactly(2))
            ->method('serialize')
            ->with($processJob->getData(), 'json', ['processJob' => $processJob])
            ->willReturnCallback(
                function () use ($processJob, $entityId, $serializedData) {
                    $processJob->setEntityId($entityId);
                    return $serializedData;
                }
            );

        /** @var EntityManager|MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $entityManager->expects(static::once())->method('flush');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
        $this->listener->postFlush(new PostFlushEventArgs($entityManager));

        static::assertEquals($serializedData, $processJob->getSerializedData());
        static::assertEquals($entityId, $processJob->getEntityId());
        static::assertEquals($entityHash, $processJob->getEntityHash());
        static::assertFalse($processJob->getData()->isModified());
    }

    public function testPostLoad()
    {
        $entity = $this->createMock(ProcessJob::class);

        $entity->expects(self::once())
            ->method('setSerializer')
            ->with(self::identicalTo($this->serializer), 'json');

        $lifecycleEventArgs = new LifecycleEventArgs($entity, $this->createMock(EntityManager::class));
        $this->listener->postLoad($entity, $lifecycleEventArgs);
    }
}
