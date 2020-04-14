<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\RemoveEntity;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class RemoveEntityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var ActionInterface
     */
    protected $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();

        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->getMock();

        $this->action = new RemoveEntity($this->contextAccessor, $this->registry);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     * @param array $options
     */
    public function testInitializeException(array $options)
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
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
        $this->assertAttributeEquals($target, 'target', $this->action);
    }

    public function testExecuteNotObjectException()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
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
        $this->expectException(\Oro\Component\Action\Exception\NotManageableEntityException::class);
        $this->expectExceptionMessage('Entity class "stdClass" is not manageable.');

        $context = new \stdClass();
        $context->test = new \stdClass();
        $target = new PropertyPath('test');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(get_class($context->test))
            ->will($this->returnValue(null));

        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    public function testExecute()
    {
        $context = new \stdClass();
        $context->test = new \stdClass();
        $target = new PropertyPath('test');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('remove')
            ->with($context->test);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(get_class($context->test))
            ->will($this->returnValue($em));

        $this->action->initialize([$target]);
        $this->action->execute($context);
    }
}
