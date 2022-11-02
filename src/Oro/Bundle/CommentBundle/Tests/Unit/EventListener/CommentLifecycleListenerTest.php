<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\EventListener\CommentLifecycleListener;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class CommentLifecycleListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CommentLifecycleListener */
    private $subscriber;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->subscriber = new CommentLifecycleListener($this->tokenAccessor);
    }

    /**
     * @dataProvider prePersistAndPreUpdateDataProvider
     */
    public function testPreUpdate(
        object $entity,
        bool $mockUser = false,
        bool $detachedUser = null,
        bool $reloadUser = null
    ) {
        $oldUser = new User();
        $oldUser->setFirstName('oldUser');

        $entity->setUpdatedBy($oldUser);

        $newUser = null;
        if ($mockUser) {
            $newUser = new User();
            $newUser->setFirstName('newUser');
        }

        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($newUser);

        $unitOfWork = $this->createMock(UnitOfWork::class);

        $entityManager = $this->getEntityManagerMock($reloadUser, $newUser);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        if (null !== $detachedUser) {
            $unitOfWork->expects($this->once())
                ->method('getEntityState')
                ->with($newUser)
                ->willReturn($detachedUser ? UnitOfWork::STATE_DETACHED : UnitOfWork::STATE_MANAGED);
        }
        $unitOfWork->expects($this->once())
            ->method('propertyChanged')
            ->with($entity, 'updatedBy', $oldUser, $newUser);

        $changeSet = [];
        $args = new PreUpdateEventArgs($entity, $entityManager, $changeSet);

        $this->subscriber->preUpdate($entity, $args);

        if ($mockUser) {
            $this->assertEquals($newUser, $entity->getUpdatedBy());
        } else {
            $this->assertNull($entity->getUpdatedBy());
        }
    }

    public function prePersistAndPreUpdateDataProvider(): array
    {
        return [
            'with a user'     => [
                'entity'       => new Comment(),
                'mockUser'     => true,
                'detachedUser' => false,
                'reloadUser'   => false,
            ],
            'with a detached' => [
                'entity'       => new Comment(),
                'mockUser'     => true,
                'detachedUser' => true,
                'reloadUser'   => true,
            ],
        ];
    }

    /**
     * @return EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEntityManagerMock(bool $reloadUser = false, User $newUser = null)
    {
        $result = $this->createMock(EntityManager::class);

        if ($reloadUser) {
            $result->expects($this->once())
                ->method('find')
                ->with('OroUserBundle:User')
                ->willReturn($newUser);
        } else {
            $result->expects($this->never())
                ->method('find');
        }

        return $result;
    }
}
