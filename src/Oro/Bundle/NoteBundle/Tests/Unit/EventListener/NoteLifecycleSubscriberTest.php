<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\EventListener;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\NoteBundle\EventListener\NoteLifecycleSubscriber;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\NoteBundle\Entity\Note;

class NoteLifecycleSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var NoteLifecycleSubscriber */
    protected $subscriber;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->setMethods(array('get'))
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->subscriber = new NoteLifecycleSubscriber($this->container);
    }

    protected function tearDown()
    {
        unset($this->container);
        unset($this->subscriber);
    }

    /**
     * @param object $entity
     * @param bool   $mockToken
     * @param bool   $mockUser
     *
     * @dataProvider prePersistAndPreUpdateDataProvider
     */
    public function testPrePersist($entity, $mockToken = false, $mockUser = false)
    {
        $initialEntity = clone $entity;

        $user = $mockUser ? new User() : null;
        $this->mockSecurityContext($mockToken, $mockUser, $user);

        $em = $this->getEntityManagerMock();

        if ($mockUser) {
            $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
                ->disableOriginalConstructor()
                ->getMock();

            $em->expects($this->any())
                ->method('getUnitOfWork')
                ->will($this->returnValue($uow));
        }

        $args = new LifecycleEventArgs($entity, $em);

        $this->subscriber->prePersist($args);

        if (!$entity instanceof Note) {
            $this->assertEquals($initialEntity, $entity);
            return;
        }

        $this->assertInstanceOf('\DateTime', $entity->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $entity->getUpdatedAt());

        if ($mockToken && $mockUser) {
            $this->assertEquals($user, $entity->getUpdatedBy());
        } else {
            $this->assertNull($entity->getUpdatedBy());
        }
    }

    /**
     * @param object $entity
     * @param bool $mockToken
     * @param bool $mockUser
     * @param bool $detachedUser
     * @param bool $reloadUser
     * @dataProvider prePersistAndPreUpdateDataProvider
     */
    public function testPreUpdate(
        $entity,
        $mockToken = false,
        $mockUser = false,
        $detachedUser = null,
        $reloadUser = null
    ) {
        $oldDate = new \DateTime('2012-12-12 12:12:12');
        $oldUser = new User();
        $oldUser->setFirstName('oldUser');
        if ($entity instanceof Note) {
            $entity->setUpdatedAt($oldDate);
            $entity->setUpdatedBy($oldUser);
        }

        $initialEntity = clone $entity;

        $newUser = null;
        if ($mockUser) {
            $newUser = new User();
            $newUser->setFirstName('newUser');
        }

        $this->mockSecurityContext($mockToken, $mockUser, $newUser);

        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->setMethods(['propertyChanged', 'getEntityState'])
            ->disableOriginalConstructor()
            ->getMock();


        $entityManager = $this->getEntityManagerMock($reloadUser, $newUser);
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));

        if ($entity instanceof Note) {
            $callIndex = 0;
            if (null !== $detachedUser) {
                $unitOfWork->expects($this->at($callIndex++))
                    ->method('getEntityState')
                    ->with($newUser)
                    ->will($this->returnValue($detachedUser ? UnitOfWork::STATE_DETACHED : UnitOfWork::STATE_MANAGED));
            }
            $unitOfWork->expects($this->at($callIndex++))
                ->method('propertyChanged')
                ->with($entity, 'updatedAt', $oldDate, $this->isInstanceOf('\DateTime'));
            $unitOfWork->expects($this->at($callIndex++))
                ->method('propertyChanged')
                ->with($entity, 'updatedBy', $oldUser, $newUser);
        } else {
            $unitOfWork->expects($this->never())->method($this->anything());
        }

        $changeSet = array();
        $args = new PreUpdateEventArgs($entity, $entityManager, $changeSet);

        $this->subscriber->preUpdate($args);

        if (!$entity instanceof Note) {
            $this->assertEquals($initialEntity, $entity);
            return;
        }

        $this->assertInstanceOf('\DateTime', $entity->getUpdatedAt());
        if ($mockToken && $mockUser) {
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
                'entity'    => new \DateTime('now'),
                'mockToken' => false,
                'mockUser'  => false,
            ],
            'no token'        => [
                'entity'    => new Note(),
                'mockToken' => false,
                'mockUser'  => false,
            ],
            'no user'         => [
                'entity'    => new Note(),
                'mockToken' => true,
                'mockUser'  => false,
            ],
            'with a user'     => [
                'entity'       => new Note(),
                'mockToken'    => true,
                'mockUser'     => true,
                'detachedUser' => false,
                'reloadUser'   => false,
            ],
            'with a detached' => [
                'entity'       => new Note(),
                'mockToken'    => true,
                'mockUser'     => true,
                'detachedUser' => true,
                'reloadUser'   => true,
            ],
        ];
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

    /**
     * @param bool      $mockToken
     * @param bool      $mockUser
     * @param User|null $user
     */
    protected function mockSecurityContext($mockToken = false, $mockUser = false, $user = null)
    {
        $securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContextInterface')
            ->setMethods(array('getToken'))
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        if ($mockToken) {
            $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
                ->setMethods(array('getUser'))
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

            if ($mockUser) {
                $token->expects($this->any())
                    ->method('getUser')
                    ->will($this->returnValue($user));
            }

            $securityContext->expects($this->any())
                ->method('getToken')
                ->will($this->returnValue($token));
        }

        $this->container->expects($this->any())
            ->method('get')
            ->with('security.context')
            ->will($this->returnValue($securityContext));
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(['prePersist', 'preUpdate'], $this->subscriber->getSubscribedEvents());
    }
}
