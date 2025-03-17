<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\EventListener\ProcessDataSerializeListener;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class ProcessDataSerializeListenerTest extends TestCase
{
    private const TEST_CLASS = 'Test\Class';

    private SerializerInterface&MockObject $serializer;
    private ProcessDataSerializeListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->serializer = $this->getMockForAbstractClass(SerializerInterface::class);

        $container = TestContainerBuilder::create()
            ->add('oro_workflow.serializer.process.serializer', $this->serializer)
            ->getContainer($this);

        $this->listener = new ProcessDataSerializeListener($container);
    }

    /**
     * @dataProvider onFlushProvider
     */
    public function testOnFlush(array $entities, array $expected): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($entities);
        $unitOfWork->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($entities);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));

        self::assertEquals($expected, ReflectionUtil::getPropertyValue($this->listener, 'scheduledEntities'));
    }

    public function onFlushProvider(): array
    {
        $processJob = new ProcessJob();
        $processJob->getData()->set('key', 'value');

        return [
            'invalid class' => [
                'entities' => [new \stdClass()],
                'expected' => []
            ],
            'valid class' => [
                'entities' => [$processJob],
                'expected' => [$processJob, $processJob]
            ],
            'several' => [
                'entities' => [$processJob, new \stdClass(), new \stdClass()],
                'expected' => [$processJob, $processJob]
            ],
        ];
    }

    public function testPostFlush(): void
    {
        $serializedData = 'serializedData';
        $processDefinition = new ProcessDefinition();
        $processDefinition->setRelatedEntity(self::TEST_CLASS);

        $processTrigger = new ProcessTrigger();
        $processTrigger->setDefinition($processDefinition);

        $processData = new ProcessData();
        $processData->set('test', 'value');

        $processJob = new ProcessJob();
        $processJob->setProcessTrigger($processTrigger)
            ->setData($processData);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$processJob]);
        $unitOfWork->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$processJob]);

        $entityId = 1;
        $entityHash = ProcessJob::generateEntityHash(self::TEST_CLASS, $entityId);

        $this->serializer->expects(self::exactly(2))
            ->method('serialize')
            ->with($processJob->getData(), 'json', ['processJob' => $processJob])
            ->willReturnCallback(function () use ($processJob, $entityId, $serializedData) {
                $processJob->setEntityId($entityId);

                return $serializedData;
            });

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $entityManager->expects(self::once())
            ->method('flush');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
        $this->listener->postFlush(new PostFlushEventArgs($entityManager));

        self::assertEquals($serializedData, $processJob->getSerializedData());
        self::assertEquals($entityId, $processJob->getEntityId());
        self::assertEquals($entityHash, $processJob->getEntityHash());
        self::assertFalse($processJob->getData()->isModified());
    }

    public function testPostLoad(): void
    {
        $entity = $this->createMock(ProcessJob::class);

        $entity->expects(self::once())
            ->method('setSerializer')
            ->with(self::identicalTo($this->serializer), 'json');

        $this->listener->postLoad($entity);
    }
}
