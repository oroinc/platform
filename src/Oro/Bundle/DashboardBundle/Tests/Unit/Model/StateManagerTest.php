<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Entity\WidgetStateNullObject;
use Oro\Bundle\DashboardBundle\Model\StateManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateManagerTest extends TestCase
{
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $repository;
    private StateManager $stateManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(WidgetState::class)
            ->willReturn($this->entityManager);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(WidgetState::class)
            ->willReturn($this->repository);

        $this->stateManager = new StateManager($doctrine, $this->tokenAccessor);
    }

    public function testGetWidgetStateNotLoggedUser(): void
    {
        $widget = $this->createMock(Widget::class);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $this->repository->expects(self::never())
            ->method(self::anything());
        $this->entityManager->expects(self::never())
            ->method(self::anything());

        $state = $this->stateManager->getWidgetState($widget);

        self::assertInstanceOf(WidgetStateNullObject::class, $state);
        self::assertEquals($widget, $state->getWidget());
    }

    public function testGetWidgetStateExist(): void
    {
        $widgetState = $this->createMock(WidgetState::class);
        $widget = $this->createMock(Widget::class);
        $user = $this->createMock(User::class);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with(['owner' => $user, 'widget' => $widget])
            ->willReturn($widgetState);

        self::assertEquals($widgetState, $this->stateManager->getWidgetState($widget));
    }

    public function testGetWidgetStateNew(): void
    {
        $widget = $this->createMock(Widget::class);
        $user = $this->createMock(User::class);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with(['owner' => $user, 'widget' => $widget])
            ->willReturn(null);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(
                self::callback(
                    function ($entity) use ($widget, $user) {
                        self::assertInstanceOf(WidgetState::class, $entity);
                        self::assertEquals($widget, $entity->getWidget());
                        self::assertEquals($user, $entity->getOwner());

                        return true;
                    }
                )
            );

        self::assertInstanceOf(WidgetState::class, $this->stateManager->getWidgetState($widget));
    }
}
