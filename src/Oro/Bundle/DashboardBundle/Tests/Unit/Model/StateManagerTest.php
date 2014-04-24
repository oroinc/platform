<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\StateManager;

class StateManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $widget;

    /**
     * @var StateManager
     */
    protected $stateManager;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateManager = new StateManager(
            $this->entityManager,
            $this->securityFacade
        );
    }

    public function testGetWidgetStateNotLoggedUser()
    {
        $widget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');

        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue(null));

        $this->repository->expects($this->never())->method($this->anything());
        $this->entityManager->expects($this->never())->method($this->anything());


        $state = $this->stateManager->getWidgetState($widget);

        $this->assertInstanceOf('Oro\Bundle\DashboardBundle\Entity\WidgetStateNullObject', $state);
        $this->assertEquals($widget, $state->getWidget());
    }

    public function testGetWidgetStateExist()
    {
        $widgetState = $this->getMock('Oro\Bundle\DashboardBundle\Entity\WidgetState');
        $widget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(
                array(
                    'owner'  => $user,
                    'widget' => $widget
                )
            )
            ->will($this->returnValue($widgetState));

        $this->assertEquals($widgetState, $this->stateManager->getWidgetState($widget));
    }

    public function testGetWidgetStateNew()
    {
        $widget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(
                array(
                    'owner'  => $user,
                    'widget' => $widget
                )
            )
            ->will($this->returnValue(null));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function ($entity) use ($widget, $user) {
                        $this->assertInstanceOf('Oro\Bundle\DashboardBundle\Entity\WidgetState', $entity);
                        $this->assertEquals($widget, $entity->getWidget());
                        $this->assertEquals($user, $entity->getOwner());
                        return true;
                    }
                )
            );

        $this->assertInstanceOf(
            'Oro\Bundle\DashboardBundle\Entity\WidgetState',
            $this->stateManager->getWidgetState($widget)
        );
    }
}
