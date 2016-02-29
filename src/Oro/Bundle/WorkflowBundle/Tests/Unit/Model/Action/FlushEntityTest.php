<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Model\Action\FlushEntity;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class FlushEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
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
        /** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Options not supported
     */
    public function testInitializeException()
    {
        $options = ['test'];
        $this->action->initialize($options);
    }

    public function testExecute()
    {
        $entity = new \stdClass();
        $context = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionData')
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->action->initialize([]);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('persist')
            ->with($entity);

        $em->expects($this->once())
            ->method('flush')
            ->with($entity);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($entity))
            ->willReturn($em);

        $this->action->execute($context);
    }
}
