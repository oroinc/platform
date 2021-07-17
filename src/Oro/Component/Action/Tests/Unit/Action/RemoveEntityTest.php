<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\RemoveEntity;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class RemoveEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor */
    protected $contextAccessor;

    /** @var MockObject|ManagerRegistry */
    protected $registry;

    /** @var ActionInterface */
    protected $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();

        $this->action = new class($this->contextAccessor, $this->registry) extends RemoveEntity {
            public function xgetTarget()
            {
                return $this->target;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInitializeException(array $options)
    {
        $this->expectException(InvalidParameterException::class);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return [
            [[]],
            [[1, 2]]
        ];
    }

    public function testInitialize()
    {
        $target = new \stdClass();
        $this->action->initialize([$target]);
        static::assertEquals($target, $this->action->xgetTarget());
    }

    public function testExecuteNotObjectException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Action "remove_entity" expects reference to entity as parameter, string is given.'
        );

        $context = new \stdClass();
        $target = 'test';
        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    public function testExecuteNotManageableException()
    {
        $this->expectException(NotManageableEntityException::class);
        $this->expectExceptionMessage('Entity class "stdClass" is not manageable.');

        $context = new \stdClass();
        $context->test = new \stdClass();
        $target = new PropertyPath('test');

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with(\get_class($context->test))
            ->willReturn(null);

        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    public function testExecute()
    {
        $context = new \stdClass();
        $context->test = new \stdClass();
        $target = new PropertyPath('test');

        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $em->expects(static::once())
            ->method('remove')
            ->with($context->test);

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with(\get_class($context->test))
            ->willReturn($em);

        $this->action->initialize([$target]);
        $this->action->execute($context);
    }
}
