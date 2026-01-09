<?php

declare(strict_types=1);

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\Action\Action\FlushEntity;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class FlushEntityTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private FlushEntity $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->action = new FlushEntity(new ContextAccessor(), $this->registry);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    public function testExecuteWithoutEntity(): void
    {
        $context = new ActionData(['data' => null]);
        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->action->initialize([]);
        $this->action->execute($context);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $data, array $options, object $entity, bool $flushException = false): void
    {
        $context = new ActionData($data);
        $this->assertEntityManagerCalled($entity, $flushException);

        if ($flushException) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Flush exception');
        }

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function executeDataProvider(): array
    {
        $entity = new \stdClass();

        return [
            [
                ['data' => $entity],
                [],
                $entity
            ],
            [
                ['attribute' => $entity],
                [new PropertyPath('attribute')],
                $entity
            ],
            [
                ['attribute' => $entity],
                ['entity' => new PropertyPath('attribute')],
                $entity
            ],
            [
                ['attribute' => $entity],
                ['entity' => new PropertyPath('attribute')],
                $entity,
                true
            ],
        ];
    }

    private function assertEntityManagerCalled(object $entity, bool $throwException)
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('beginTransaction');

        if ($throwException) {
            $entityManager->expects($this->once())
                ->method('flush')
                ->willThrowException(new \Exception('Flush exception'));
            $entityManager->expects($this->once())
                ->method('rollback');
        } else {
            $entityManager->expects($this->once())
                ->method('persist');
            $entityManager->expects($this->once())
                ->method('flush');
            $entityManager->expects($this->once())
                ->method('commit');

            $classMetadata = $this->createMock(ClassMetadata::class);
            $classMetadata->expects($this->once())
                ->method('getIdentifierFieldNames')
                ->willReturn(['id']);

            $entityPersister = $this->createMock(EntityPersister::class);
            $entityPersister->expects($this->once())
                ->method('refresh')
                ->with($this->isType('array'), $entity);

            $uow = $this->createMock(UnitOfWork::class);
            $uow->expects($this->once())
                ->method('getEntityPersister')
                ->with($classMetadata->name)
                ->willReturn($entityPersister);
            $uow->expects($this->once())
                ->method('getEntityIdentifier')
                ->with($entity)
                ->willReturn([123]);

            $entityManager->expects($this->once())
                ->method('getClassMetadata')
                ->with(ClassUtils::getClass($entity))
                ->willReturn($classMetadata);

            $entityManager->expects($this->once())
                ->method('getUnitOfWork')
                ->willReturn($uow);
        }

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($entity))
            ->willReturn($entityManager);
    }
}
