<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\Action\Action\FlushEntity;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class FlushEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var FlushEntity */
    private $action;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->action = new FlushEntity(new ContextAccessor(), $this->registry);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    public function testExecuteWithoutEntity()
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
    public function testExecute(array $data, array $options, object $entity, bool $flushException = false)
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
                ->method('refresh');
            $entityManager->expects($this->once())
                ->method('commit');
        }

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($entity))
            ->willReturn($entityManager);
    }
}
