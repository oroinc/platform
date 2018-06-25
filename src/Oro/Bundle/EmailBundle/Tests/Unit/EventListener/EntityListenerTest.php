<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\EventListener\EntityListener;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\TestUtils\Mocks\ServiceLink;
use Oro\Component\TestUtils\ORM\Mocks\UnitOfWork;

class EntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityListener */
    private $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $emailOwnerManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $emailActivityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $emailThreadManager;

    /** @var EmailOwnerProviderStorage */
    private $emailOwnerStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $userEmailOwnerProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $emailActivityUpdates;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface */
    private $producer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailAddressManager */
    private $emailAddressManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository */
    private $entityRepository;

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

        $this->producer = $this->createMock(MessageProducerInterface::class);

        $this->emailOwnerStorage = new EmailOwnerProviderStorage();
        $this->emailOwnerStorage->addProvider($this->userEmailOwnerProvider);

        $this->emailActivityUpdates = $this->getMockBuilder('Oro\Bundle\EmailBundle\Model\EmailActivityUpdates')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAddressManager = $this->getMockBuilder(EmailAddressManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressRepository')
            ->willReturn($this->entityRepository);

        $this->listener = new EntityListener(
            $this->emailOwnerManager,
            new ServiceLink($this->emailActivityManager),
            new ServiceLink($this->emailThreadManager),
            $this->emailActivityUpdates,
            $this->producer,
            $this->emailAddressManager
        );
    }

    public function testOnFlush()
    {
        $emailOwner = new TestEmailOwner(123);
        $contactsArray = [new User(), new User(), new User()];
        $updatedEmailAddresses = [new EmailAddress(1), new EmailAddress(2)];
        $createdEmailAddresses = [new EmailAddress(3)];

        $uow = $this->getMockBuilder('Oro\Component\TestUtils\ORM\Mocks\UnitOfWork')
            ->disableOriginalConstructor()
            ->setMethods(['computeChangeSet'])
            ->getMock();

        array_map([$uow, 'addInsertion'], $contactsArray);

        $metadata = $this->createClassMetadataMock();
        $em = $this->createEntityManagerMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->exactly(2))
            ->method('computeChangeSet')
            ->withConsecutive(
                [$metadata, $updatedEmailAddresses[0]],
                [$metadata, $updatedEmailAddresses[1]]
            );
        $onFlushEventArgs = $this->createOnFlushEventArgsMock();
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
            ->willReturn([$updatedEmailAddresses, $createdEmailAddresses]);

        $postFlushEventArgs = $this->createPostFlushEventArgsMock();
        $postFlushEventArgs->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->emailActivityUpdates->expects($this->once())
            ->method('getFilteredOwnerEntitiesToUpdate')
            ->will($this->returnValue([$emailOwner]));
        $this->emailActivityUpdates->expects($this->once())
            ->method('clearPendingEntities');

        $this->producer->expects($this->once())
            ->method('send')
            ->with(Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS, [
                'ownerClass' => TestEmailOwner::class,
                'ownerIds' => [123],
            ]);

        $this->entityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }

    public function testOnFlushNotSupported()
    {
        $emailOwner = new TestEmailOwner(123);
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

        $metadata = $this->createClassMetadataInfoMock();
        $em = $this->createEntityManagerMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $onFlushEventArgs = $this->createOnFlushEventArgsMock();
        $onFlushEventArgs->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->emailOwnerManager->expects($this->once())
            ->method('createEmailAddressData')
            ->will($this->returnValue([]));
        $this->emailOwnerManager->expects($this->once())
            ->method('handleChangedAddresses')
            ->willReturn([[],[]]);
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

        $postFlushEventArgs = $this->createPostFlushEventArgsMock();
        $postFlushEventArgs->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->registry->expects($this->never())
            ->method('getRepository')
            ->will($this->returnValue($this->registry));
        $this->registry->expects($this->never())
            ->method('getEmailsByOwnerEntity')
            ->will($this->returnValue($createdEmailsArray));
        $this->emailActivityUpdates->expects($this->once())
            ->method('processUpdatedEmailAddresses')
            ->with([]);
        $this->emailActivityUpdates->expects($this->once())
            ->method('getFilteredOwnerEntitiesToUpdate')
            ->will($this->returnValue([$emailOwner]));
        $this->emailActivityUpdates->expects($this->once())
            ->method('clearPendingEntities');
        $this->userEmailOwnerProvider->expects($this->never())
            ->method('getEmailOwnerClass')
            ->will($this->returnValue(ClassUtils::getClass(new User)));

        $this->emailActivityManager->expects($this->never())
            ->method('addAssociation');

        $this->producer->expects($this->once())
            ->method('send')
            ->with(Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS, [
                'ownerClass' => TestEmailOwner::class,
                'ownerIds' => [123],
            ]);

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }

    public function testOnFlushWhenEmailAddressDoesNotHaveOwner()
    {
        $emailOwner = new TestEmailOwner(123);
        $contactsArray = [new User(), new User(), new User()];
        $updatedEmailAddresses = [new EmailAddress(1), new EmailAddress(2)];
        $createdEmailAddresses = [new EmailAddress(3)];

        $uow = $this->getMockBuilder('Oro\Component\TestUtils\ORM\Mocks\UnitOfWork')
            ->disableOriginalConstructor()
            ->setMethods(['computeChangeSet'])
            ->getMock();

        array_map([$uow, 'addInsertion'], $contactsArray);

        $metadata = $this->createClassMetadataMock();
        $em = $this->createEntityManagerMock();
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->exactly(2))
            ->method('computeChangeSet')
            ->withConsecutive(
                [$metadata, $updatedEmailAddresses[0]],
                [$metadata, $updatedEmailAddresses[1]]
            );
        $onFlushEventArgs = $this->createOnFlushEventArgsMock();
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
            ->willReturn([$updatedEmailAddresses, $createdEmailAddresses]);

        $postFlushEventArgs = $this->createPostFlushEventArgsMock();
        $postFlushEventArgs->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->emailActivityUpdates->expects($this->once())
            ->method('getFilteredOwnerEntitiesToUpdate')
            ->will($this->returnValue([]));
        $this->emailActivityUpdates->expects($this->once())
            ->method('clearPendingEntities');

        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ClassMetadata
     */
    private function createClassMetadataMock()
    {
        return $this->createMock(ClassMetadata::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ClassMetadataInfo
     */
    private function createClassMetadataInfoMock()
    {
        return $this->createMock(ClassMetadataInfo::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->createMock(EntityManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|OnFlushEventArgs
     */
    private function createOnFlushEventArgsMock()
    {
        return $this->createMock(OnFlushEventArgs::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|PostFlushEventArgs
     */
    private function createPostFlushEventArgsMock()
    {
        return $this->createMock(PostFlushEventArgs::class);
    }
}
