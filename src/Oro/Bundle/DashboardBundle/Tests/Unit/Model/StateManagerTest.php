<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\StateManager;

class StateManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $widget;

    protected function setUp()
    {
        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');

        $repository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue(null));

        $this->entityManager = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->widget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $this->widget
            ->expects($this->any())
            ->method('isExpanded')
            ->will($this->returnValue(true));

        $this->widget
            ->expects($this->any())
            ->method('getLayoutPosition')
            ->will($this->returnValue([0, 0]));
    }

    public function testGetWidgetState()
    {
        $user  = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $token
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->securityContext
            ->expects($this->atLeastOnce())
            ->method('getToken')
            ->will($this->returnValue($token));

        $stateManager = new StateManager(
            $this->entityManager,
            $this->securityContext
        );
        $stateManager->getWidgetState($this->widget);
    }

    /**
     * @expectedException \Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage User not logged
     */
    public function testGetWidgetStateNotLogged()
    {
        $this->securityContext
            ->expects($this->atLeastOnce())
            ->method('getToken')
            ->will($this->returnValue(null));

        $stateManager = new StateManager(
            $this->entityManager,
            $this->securityContext
        );
        $stateManager->getWidgetState($this->widget);
    }
}
