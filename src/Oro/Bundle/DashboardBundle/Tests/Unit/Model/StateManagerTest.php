<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Entity\WidgetStateNullObject;
use Oro\Bundle\DashboardBundle\Model\StateManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class StateManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var StateManager */
    private $stateManager;

    protected function setUp(): void
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
        $widget = $this->createMock(Widget::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method($this->anything());
        $this->entityManager->expects($this->never())
            ->method($this->anything());

        $state = $this->stateManager->getWidgetState($widget);

        $this->assertInstanceOf(WidgetStateNullObject::class, $state);
        $this->assertEquals($widget, $state->getWidget());
    }

    public function testGetWidgetStateExist()
    {
        $widgetState = $this->createMock(WidgetState::class);
        $widget = $this->createMock(Widget::class);
        $user = $this->createMock(User::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['owner' => $user, 'widget' => $widget])
            ->willReturn($widgetState);

        $this->assertEquals($widgetState, $this->stateManager->getWidgetState($widget));
    }

    public function testGetWidgetStateNew()
    {
        $widget = $this->createMock(Widget::class);
        $user = $this->createMock(User::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['owner' => $user, 'widget' => $widget])
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with(
                $this->callback(
                    function ($entity) use ($widget, $user) {
                        $this->assertInstanceOf(WidgetState::class, $entity);
                        $this->assertEquals($widget, $entity->getWidget());
                        $this->assertEquals($user, $entity->getOwner());

                        return true;
                    }
                )
            );

        $this->assertInstanceOf(
            WidgetState::class,
            $this->stateManager->getWidgetState($widget)
        );
    }
}
