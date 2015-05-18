<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\LDAPBundle\EventListener\UserChangeListener;
use Oro\Bundle\UserBundle\Entity\User;

class UserChangeListenerTest extends \PHPUnit_Framework_TestCase
{
    private $uow;
    private $em;
    private $ldapManager;
    private $userChangeListener;

    public function setUp()
    {
        $this->uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));
        $this->ldapManager = $this->getMockBuilder('Oro\Bundle\LDAPBundle\LDAP\LdapManager')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->ldapManager));

        $this->userChangeListener = new UserChangeListener($serviceLink);
    }

    public function testUserShouldNotBeUpdatedIfHeHasChangedOtherThanSynchronizedFields()
    {
        $synchronizedFields = ['password', 'email'];
        $changeSet = [
            'username' => ['oldUsername', 'newUsername'],
            'salt'     => ['oldSalt', 'newSalt'],
        ];

        $user = new User();

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$user]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($user)
            ->will($this->returnValue($changeSet));
        $this->ldapManager->expects($this->once())
            ->method('getSynchronizedFields')
            ->will($this->returnValue($synchronizedFields));

        $this->ldapManager->expects($this->never())
            ->method('save');

        $this->userChangeListener->onFlush(new OnFlushEventArgs($this->em));
        $this->userChangeListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testUserShouldBeUpdatedIfHeHasChangedSynchronizedFields()
    {
        $synchronizedFields = ['username', 'email'];
        $changeSet = [
            'username' => ['oldUsername', 'newUsername'],
            'salt'     => ['oldSalt', 'newSalt'],
        ];

        $user = new User();

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$user]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($user)
            ->will($this->returnValue($changeSet));
        $this->ldapManager->expects($this->once())
            ->method('getSynchronizedFields')
            ->will($this->returnValue($synchronizedFields));

        $this->ldapManager->expects($this->once())
            ->method('save')
            ->with($user);

        $this->userChangeListener->onFlush(new OnFlushEventArgs($this->em));
        $this->userChangeListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testNewUserShouldBeUpdatedIfHeHasChangedSynchronizedFields()
    {
        $synchronizedFields = ['username', 'email'];
        $changeSet = [
            'username' => [null, 'newUsername'],
            'salt'     => [null, 'newSalt'],
        ];

        $user = new User();

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$user]));
        $this->uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($user)
            ->will($this->returnValue($changeSet));
        $this->ldapManager->expects($this->once())
            ->method('getSynchronizedFields')
            ->will($this->returnValue($synchronizedFields));

        $this->ldapManager->expects($this->once())
            ->method('save')
            ->with($user);

        $this->userChangeListener->onFlush(new OnFlushEventArgs($this->em));
        $this->userChangeListener->postFlush(new PostFlushEventArgs($this->em));
    }
}
