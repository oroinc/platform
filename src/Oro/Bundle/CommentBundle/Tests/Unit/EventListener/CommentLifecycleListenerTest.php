<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\EventListener\CommentLifecycleListener;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class CommentLifecycleListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CommentLifecycleListener */
    protected $subscriber;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->subscriber = new CommentLifecycleListener($this->tokenAccessor);
    }

    protected function tearDown()
    {
        unset($this->tokenAccessor);
        unset($this->subscriber);
    }

    /**
     * @param object $entity
     * @param bool   $mockUser
     * @param bool   $detachedUser
     * @param bool   $reloadUser
     *
     * @dataProvider prePersistAndPreUpdateDataProvider
     */
    public function testPreUpdate(
        $entity,
        $mockUser = false,
        $detachedUser = null,
        $reloadUser = null
    ) {
        $oldUser = new User();
        $oldUser->setFirstName('oldUser');

        $entity->setUpdatedBy($oldUser);

        $newUser = null;
        if ($mockUser) {
            $newUser = new User();
            $newUser->setFirstName('newUser');
        }

        $this->mockSecurityContext($newUser);

        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->setMethods(['propertyChanged', 'getEntityState'])
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getEntityManagerMock($reloadUser, $newUser);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));

        $callIndex = 0;
        if (null !== $detachedUser) {
            $unitOfWork->expects($this->once(++$callIndex))
                ->method('getEntityState')
                ->with($newUser)
                ->will($this->returnValue($detachedUser ? UnitOfWork::STATE_DETACHED : UnitOfWork::STATE_MANAGED));
        }
        $unitOfWork->expects($this->once(++$callIndex))
            ->method('propertyChanged')
            ->with($entity, 'updatedBy', $oldUser, $newUser);

        $changeSet = array();
        $args      = new PreUpdateEventArgs($entity, $entityManager, $changeSet);

        $this->subscriber->preUpdate($entity, $args);

        if ($mockUser) {
            $this->assertEquals($newUser, $entity->getUpdatedBy());
        } else {
            $this->assertNull($entity->getUpdatedBy());
        }
    }

    /**
     * @return array
     */
    public function prePersistAndPreUpdateDataProvider()
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
     * @param User|null $user
     */
    protected function mockSecurityContext($user = null)
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));
    }

    /**
     * @param bool   $reloadUser
     * @param object $newUser
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getEntityManagerMock($reloadUser = false, $newUser = null)
    {
        $result = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(array('getUnitOfWork', 'find'))
            ->disableOriginalConstructor()
            ->getMock();

        if ($reloadUser) {
            $result->expects($this->once())->method('find')
                ->with('OroUserBundle:User')
                ->will($this->returnValue($newUser));
        } else {
            $result->expects($this->never())->method('find');
        }

        return $result;
    }
}
