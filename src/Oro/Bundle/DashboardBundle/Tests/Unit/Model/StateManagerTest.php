<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DashboardBundle\Model\StateManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class StateManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $widget;

    /** @var StateManager */
    protected $stateManager;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->stateManager = new StateManager(
            $this->entityManager,
            $this->tokenAccessor
        );
    }

    public function testGetWidgetStateNotLoggedUser()
    {
        $widget = $this->createMock('Oro\Bundle\DashboardBundle\Entity\Widget');

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue(null));

        $this->repository->expects($this->never())->method($this->anything());
        $this->entityManager->expects($this->never())->method($this->anything());


        $state = $this->stateManager->getWidgetState($widget);

        $this->assertInstanceOf('Oro\Bundle\DashboardBundle\Entity\WidgetStateNullObject', $state);
        $this->assertEquals($widget, $state->getWidget());
    }

    public function testGetWidgetStateExist()
    {
        $widgetState = $this->createMock('Oro\Bundle\DashboardBundle\Entity\WidgetState');
        $widget = $this->createMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $user = $this->createMock('Oro\Bundle\UserBundle\Entity\User');

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->entityManager->expects($this->once())
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
        $widget = $this->createMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $user = $this->createMock('Oro\Bundle\UserBundle\Entity\User');

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->entityManager->expects($this->once())
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
