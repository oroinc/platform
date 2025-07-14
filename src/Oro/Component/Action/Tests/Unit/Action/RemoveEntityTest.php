<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\RemoveEntity;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class RemoveEntityTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private ActionInterface $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->action = new RemoveEntity(new ContextAccessor(), $this->registry);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInitializeException(array $options): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->action->initialize($options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            [[]],
            [[1, 2]]
        ];
    }

    public function testInitialize(): void
    {
        $target = new \stdClass();
        $this->action->initialize([$target]);
        self::assertEquals($target, ReflectionUtil::getPropertyValue($this->action, 'target'));
    }

    public function testExecuteNotObjectException(): void
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

    public function testExecuteNotManageableException(): void
    {
        $this->expectException(NotManageableEntityException::class);
        $this->expectExceptionMessage('Entity class "stdClass" is not manageable.');

        $context = new \stdClass();
        $context->test = new \stdClass();
        $target = new PropertyPath('test');

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(\get_class($context->test))
            ->willReturn(null);

        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    public function testExecute(): void
    {
        $context = new \stdClass();
        $context->test = new \stdClass();
        $target = new PropertyPath('test');

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('remove')
            ->with($context->test);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(\get_class($context->test))
            ->willReturn($em);

        $this->action->initialize([$target]);
        $this->action->execute($context);
    }
}
