<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\RemoveEntity;

class RemoveEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var ActionInterface
     */
    protected $action;

    protected function setUp()
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
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @dataProvider invalidOptionsDataProvider
     * @param array $options
     */
    public function testInitializeException(array $options)
    {
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

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Action "remove_entity" expects reference to entity as parameter, string is given.
     */
    public function testExecuteNotObjectException()
    {
        $context = new \stdClass();
        $target = 'test';
        $this->action->initialize([$target]);
        $this->action->execute($context);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\NotManageableEntityException
     * @expectedExceptionMessage Entity class "stdClass" is not manageable.
     */
    public function testExecuteNotManageableException()
    {
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
