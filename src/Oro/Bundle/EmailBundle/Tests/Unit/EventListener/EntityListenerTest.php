<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\Common\Util\ClassUtils;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Component\TestUtils\Mocks\ServiceLink;
use Oro\Component\TestUtils\ORM\Mocks\UnitOfWork;
use Oro\Bundle\EmailBundle\EventListener\EntityListener;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;

class EntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailOwnerManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailActivityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailThreadManager;

    /** @var EmailOwnerProviderStorage */
    private $emailOwnerStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $userEmailOwnerProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailActivityUpdates;

    /** @var ActivityListChainProvider */
    private $chainProvider;

    protected function setUp()
    {
        $this->emailOwnerManager    =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->emailActivityManager =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->emailThreadManager =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
                ->setMethods(['getRepository', 'getEmailsByOwnerEntity'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->userEmailOwnerProvider = $this
            ->getMockBuilder('Oro\Bundle\UserBundle\Entity\Provider\EmailOwnerProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->chainProvider =
            $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
                ->disableOriginalConstructor()->getMock();

        $this->emailOwnerStorage = new EmailOwnerProviderStorage();
        $this->emailOwnerStorage->addProvider($this->userEmailOwnerProvider);

        $this->emailActivityUpdates = $this->getMockBuilder('Oro\Bundle\EmailBundle\Model\EmailActivityUpdates')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new EntityListener(
            $this->emailOwnerManager,
            new ServiceLink($this->emailActivityManager),
            new ServiceLink($this->emailThreadManager),
            $this->emailActivityUpdates
        );
    }

    public function testOnFlush()
    {
        $contactsArray = [new User(), new User(), new User()];
        $updatedEmailAddresses = [new EmailAddress(1), new EmailAddress(2)];

        $uow = $this->getMockBuilder('Oro\Component\TestUtils\ORM\Mocks\UnitOfWork')
            ->disableOriginalConstructor()
            ->setMethods(['computeChangeSet'])
            ->getMock();

        array_map([$uow, 'addInsertion'], $contactsArray);

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $em
            ->expects($this->once())
            ->method('flush');
        $uow->expects($this->exactly(2))
            ->method('computeChangeSet')
            ->withConsecutive(
                [$metadata, $updatedEmailAddresses[0]],
                [$metadata, $updatedEmailAddresses[1]]
            );
        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->emailOwnerManager->expects($this->once())
            ->method('createEmailAddressData')
            ->with($this->identicalTo($uow))
            ->will($this->returnValue([]));
        $this->emailOwnerManager->expects($this->once())
            ->method('handleChangedAddresses')
            ->with([])
            ->will($this->returnValue($updatedEmailAddresses));

        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $postFlushEventArgs
            ->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->emailActivityUpdates
            ->expects($this->once())
            ->method('createJobs')
            ->will($this->returnValue([new Job('command')]));

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }

    public function testOnFlushNotSupported()
    {
        $contactsArray = [new User(), new User(), new User()];
        $createdEmailsArray = [new Email(), new Email(), new Email()];
        $updatedEmailsArray = [new Email()];
        $createdEmails = [
            spl_object_hash($createdEmailsArray[0]) => $createdEmailsArray[0],
            spl_object_hash($createdEmailsArray[1]) => $createdEmailsArray[1],
            spl_object_hash($createdEmailsArray[2]) => $createdEmailsArray[2],
        ];
        $updatedEmails = [
            spl_object_hash($updatedEmailsArray[0]) => $updatedEmailsArray[0],
        ];

        $uow = new UnitOfWork();
        array_map([$uow, 'addInsertion'], array_merge($contactsArray, $createdEmailsArray));
        array_map([$uow, 'addUpdate'], $updatedEmailsArray);

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $onFlushEventArgs
            ->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->emailOwnerManager->expects($this->once())
            ->method('createEmailAddressData')
            ->will($this->returnValue([]));
        $this->emailOwnerManager->expects($this->once())
            ->method('handleChangedAddresses')
            ->will($this->returnValue([]));
        $this->emailActivityManager->expects($this->once())
            ->method('updateActivities')
            ->with($createdEmails);
        $this->emailThreadManager->expects($this->once())
            ->method('updateThreads')
            ->with($createdEmails);
        $this->emailThreadManager->expects($this->once())
            ->method('updateHeads')
            ->with($updatedEmails);
        $this->emailActivityManager->expects($this->once())
            ->method('updateActivities')
            ->with($createdEmails);

        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $postFlushEventArgs
            ->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->registry
            ->expects($this->never())
            ->method('getRepository')
            ->will($this->returnValue($this->registry));
        $this->registry
            ->expects($this->never())
            ->method('getEmailsByOwnerEntity')
            ->will($this->returnValue($createdEmailsArray));
        $this->emailActivityUpdates
                ->expects($this->once())
                ->method('processUpdatedEmailAddresses')
                ->with([]);
        $this->emailActivityUpdates->expects($this->once())
            ->method('createJobs')
            ->will($this->returnValue([new Job('command')]));
        $this->userEmailOwnerProvider
            ->expects($this->never())
            ->method('getEmailOwnerClass')
            ->will($this->returnValue(ClassUtils::getClass(new User)));

        $this->emailActivityManager
            ->expects($this->never())
            ->method('addAssociation');

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }
}
