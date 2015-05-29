<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\LDAPBundle\EventListener\UserChangeListener;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;

class UserChangeListenerTest extends \PHPUnit_Framework_TestCase
{
    private $uow;
    private $em;
    private $managerProvider;
    private $userChangeListener;

    private function setUpChannel($id, $name)
    {
        $channel = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        $channel->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $channel->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        return $channel;
    }

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
        $this->managerProvider = $this->getMockBuilder('Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->managerProvider->expects($this->any())
            ->method('getChannels')
            ->will($this->returnValue([
                1 => $this->setUpChannel(1, 'First LDAP'),
                40 => $this->setUpChannel(40, 'Second LDAP'),
            ]));
        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->managerProvider));

        $this->userChangeListener = new UserChangeListener($serviceLink);
    }

    public function testUserShouldNotBeUpdatedIfHeIsNotMappedToAnyChannel()
    {
        $user = new TestingUser();

        $user->setLdapMappings([]);

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$user]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->never())
            ->method('getEntityChangeSet');
        $this->managerProvider->expects($this->never())
            ->method('channel');

        $this->userChangeListener->onFlush(new OnFlushEventArgs($this->em));
        $this->userChangeListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testUserShouldBeAlwaysInserted()
    {
        $user = new TestingUser();

        $user->setLdapMappings([
            1 => 'some_dn'
        ]);

        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$user]));
        $this->managerProvider->expects($this->once())
            ->method('save')
            ->with($this->equalTo($user));

        $this->userChangeListener->onFlush(new OnFlushEventArgs($this->em));
        $this->userChangeListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testUserShouldNotBeUpdatedIfHeHasChangedOtherThanSynchronizedFields()
    {
        $synchronizedFields = ['password', 'email'];
        $changeSet = [
            'username' => ['oldUsername', 'newUsername'],
            'salt'     => ['oldSalt', 'newSalt'],
        ];

        $user = new TestingUser();

        $user->setLdapMappings([
            1 => 'some_dn'
        ]);

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
        $manager = $this->getMockBuilder('Oro\Bundle\LDAPBundle\LDAP\LdapManager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->once())
            ->method('getSynchronizedFields')
            ->will($this->returnValue($synchronizedFields));
        $this->managerProvider->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($manager));
        $manager->expects($this->never())
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

        $user = new TestingUser();

        $user->setLdapMappings([
            1 => 'some_dn'
        ]);

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
        $manager = $this->getMockBuilder('Oro\Bundle\LDAPBundle\LDAP\LdapManager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->once())
            ->method('getSynchronizedFields')
            ->will($this->returnValue($synchronizedFields));
        $this->managerProvider->expects($this->any())
            ->method('channel')
            ->will($this->returnValue($manager));
        $manager->expects($this->once())
            ->method('save')
            ->with($user);

        $this->userChangeListener->onFlush(new OnFlushEventArgs($this->em));
        $this->userChangeListener->postFlush(new PostFlushEventArgs($this->em));
    }
}
