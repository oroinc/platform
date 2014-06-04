<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\EventListener;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\NoteBundle\EventListener\NoteLifecycleListener;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class NoteLifecycleListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var NoteLifecycleListener */
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

        $this->subscriber = new NoteLifecycleListener($securityFacadeLink);
    }

    protected function tearDown()
    {
        unset($this->securityFacade);
        unset($this->subscriber);
    }

    /**
     * @param object $entity
     * @param bool   $mockUser
     *
     * @dataProvider prePersistAndPreUpdateDataProvider
     */
    public function testPrePersist($entity, $mockUser = false)
    {
        $initialEntity = clone $entity;

        $user = $mockUser ? new User() : null;
        $this->mockSecurityContext($user);

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

        if ($mockUser) {
            $this->assertEquals($user, $entity->getUpdatedBy());
        } else {
            $this->assertNull($entity->getUpdatedBy());
        }
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

        $this->mockSecurityContext($newUser);

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
                'entity'    => new \DateTime('now'),
                'mockUser'  => false,
            ],
            'with a user'     => [
                'entity'       => new Note(),
                'mockUser'     => true,
                'detachedUser' => false,
                'reloadUser'   => false,
            ],
            'with a detached' => [
                'entity'       => new Note(),
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
     * @param User|null $user
     */
    protected function mockSecurityContext($user = null)
    {
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));
    }
}
