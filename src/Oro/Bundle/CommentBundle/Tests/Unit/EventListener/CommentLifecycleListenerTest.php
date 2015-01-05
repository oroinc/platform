<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\CommentBundle\EventListener\CommentLifecycleListener;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\UserBundle\Entity\User;

class CommentLifecycleListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CommentLifecycleListener */
    protected $subscriber;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->setMethods(['getLoggedUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacadeLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->setMethods(['getService'])
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacadeLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->securityFacade));

        $this->subscriber = new CommentLifecycleListener($securityFacadeLink);
    }

    protected function tearDown()
    {
        unset($this->securityFacade);
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

        if ($entity instanceof Comment) {
            $entity->setUpdatedBy($oldUser);
        }

        $initialEntity = clone $entity;
        $newUser       = null;

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

        if ($entity instanceof Comment) {
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
        } else {
            $unitOfWork->expects($this->never())->method($this->anything());
        }

        $changeSet = array();
        $args      = new PreUpdateEventArgs($entity, $entityManager, $changeSet);

        $this->subscriber->preUpdate($args);

        if (!$entity instanceof Comment) {
            $this->assertEquals($initialEntity, $entity);
            return;
        }

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
            'not applied'     => [
                'entity'   => new \DateTime('now'),
                'mockUser' => false,
            ],
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
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));
    }

    /**
     * @param bool   $reloadUser
     * @param object $newUser
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
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
