<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\Action\Action\FlushEntity;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class FlushEntityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var FlushEntity
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action = new FlushEntity($this->contextAccessor, $this->registry);
        /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @dataProvider executeDataProvider
     * @param array $data
     * @param array $options
     * @param mixed $entity
     * @param bool $flushException
     */
    public function testExecute(array $data, array $options, $entity, $flushException = false)
    {
        $context = new ActionData($data);
        $this->assertEntityManagerCalled($entity, $flushException);

        if ($flushException) {
            $this->expectException('\Exception');
            $this->expectExceptionMessage('Flush exception');
        }

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
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

    /**
     * @param mixed $entity
     * @param bool $throwException
     */
    protected function assertEntityManagerCalled($entity, $throwException = false)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager $entityManager */
        $entityManager = $this->createMock('Doctrine\ORM\EntityManagerInterface');
        $entityManager->expects($this->once())->method('beginTransaction');

        if ($throwException) {
            $entityManager->expects($this->once())
                ->method('flush')
                ->willThrowException(new \Exception('Flush exception'));
            $entityManager->expects($this->once())->method('rollback');
        } else {
            $entityManager->expects($this->once())->method('persist');
            $entityManager->expects($this->once())->method('flush');
            $entityManager->expects($this->once())->method('refresh');
            $entityManager->expects($this->once())->method('commit');
        }

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($entity))
            ->willReturn($entityManager);
    }
}
