<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationsTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\EventListener\EntityListener;
use Oro\Bundle\EmailBundle\Model\EmailActivityUpdates;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\UserBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\ORM\Mocks\UnitOfWorkMock;
use Oro\Component\Testing\Unit\TestContainerBuilder;

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
    private $userEmailOwnerProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $emailActivityUpdates;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface */
    private $producer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailAddressManager */
    private $emailAddressManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository */
    private $entityRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailAddressVisibilityManager */
    private $emailAddressVisibilityManager;

    protected function setUp(): void
    {
        $this->emailOwnerManager = $this->createMock(EmailOwnerManager::class);
        $this->emailActivityManager = $this->createMock(EmailActivityManager::class);
        $this->emailThreadManager = $this->createMock(EmailThreadManager::class);
        $this->userEmailOwnerProvider = $this->createMock(EmailOwnerProvider::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $this->emailOwnerStorage = new EmailOwnerProviderStorage();
        $this->emailOwnerStorage->addProvider($this->userEmailOwnerProvider);

        $this->emailActivityUpdates = $this->createMock(EmailActivityUpdates::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->emailAddressManager = $this->createMock(EmailAddressManager::class);

        $this->emailAddressVisibilityManager = $this->createMock(EmailAddressVisibilityManager::class);

        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressRepository')
            ->willReturn($this->entityRepository);

        $container = TestContainerBuilder::create()
            ->add('oro_email.email.owner.manager', $this->emailOwnerManager)
            ->add('oro_email.email.thread.manager', $this->emailThreadManager)
            ->add('oro_email.email.activity.manager', $this->emailActivityManager)
            ->add('oro_email.model.email_activity_updates', $this->emailActivityUpdates)
            ->add('oro_email.email.address.manager', $this->emailAddressManager)
            ->add('oro_email.email_address_visibility.manager', $this->emailAddressVisibilityManager)
            ->getContainer($this);

        $this->listener = new EntityListener(
            $this->producer,
            $container
        );
    }

    public function testOnFlush()
    {
        $emailOwner = new TestEmailOwner(123);
        $contactsArray = [new User(), new User(), new User()];
        $updatedEmailAddresses = [new EmailAddress(1), new EmailAddress(2)];
        $createdEmailAddresses = [new EmailAddress(3)];

        $uow = $this->getMockBuilder(UnitOfWorkMock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['computeChangeSet'])
            ->getMock();

        array_map([$uow, 'addInsertion'], $contactsArray);

        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->exactly(2))
            ->method('computeChangeSet')
            ->withConsecutive(
                [$metadata, $updatedEmailAddresses[0]],
                [$metadata, $updatedEmailAddresses[1]]
            );
        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $onFlushEventArgs->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->emailOwnerManager->expects($this->once())
            ->method('createEmailAddressData')
            ->with($this->identicalTo($uow))
            ->willReturn([]);
        $this->emailOwnerManager->expects($this->once())
            ->method('handleChangedAddresses')
            ->with([])
            ->willReturn([$updatedEmailAddresses, $createdEmailAddresses, ['test@test.com']]);

        $postFlushEventArgs = $this->createMock(PostFlushEventArgs::class);
        $postFlushEventArgs->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->emailActivityUpdates->expects($this->once())
            ->method('getFilteredOwnerEntitiesToUpdate')
            ->willReturn([$emailOwner]);
        $this->emailActivityUpdates->expects($this->once())
            ->method('clearPendingEntities');

        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                UpdateEmailOwnerAssociationsTopic::getName(),
                [
                    'ownerClass' => TestEmailOwner::class,
                    'ownerIds' => [123],
                ]
            );

        $this->entityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $this->emailAddressVisibilityManager->expects(self::once())
            ->method('collectEmailAddresses')
            ->with(['test@test.com']);

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

        $uow = new UnitOfWorkMock();
        array_map([$uow, 'addInsertion'], array_merge($contactsArray, $createdEmailsArray));
        array_map([$uow, 'addUpdate'], $updatedEmailsArray);

        $metadata = $this->createMock(ClassMetadataInfo::class);
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $onFlushEventArgs->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->emailOwnerManager->expects($this->once())
            ->method('createEmailAddressData')
            ->willReturn([]);
        $this->emailOwnerManager->expects($this->once())
            ->method('handleChangedAddresses')
            ->willReturn([[],[], []]);
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

        $postFlushEventArgs = $this->createMock(PostFlushEventArgs::class);
        $postFlushEventArgs->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->emailActivityUpdates->expects($this->once())
            ->method('processUpdatedEmailAddresses')
            ->with([]);
        $this->emailActivityUpdates->expects($this->once())
            ->method('getFilteredOwnerEntitiesToUpdate')
            ->willReturn([$emailOwner]);
        $this->emailActivityUpdates->expects($this->once())
            ->method('clearPendingEntities');
        $this->userEmailOwnerProvider->expects($this->never())
            ->method('getEmailOwnerClass')
            ->willReturn(User::class);

        $this->emailActivityManager->expects($this->never())
            ->method('addAssociation');

        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                UpdateEmailOwnerAssociationsTopic::getName(),
                [
                    'ownerClass' => TestEmailOwner::class,
                    'ownerIds' => [123],
                ]
            );

        $this->emailAddressVisibilityManager->expects(self::never())
            ->method('collectEmailAddresses');

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }

    public function testOnFlushWhenEmailAddressDoesNotHaveOwner()
    {
        $contactsArray = [new User(), new User(), new User()];
        $updatedEmailAddresses = [new EmailAddress(1), new EmailAddress(2)];
        $createdEmailAddresses = [new EmailAddress(3)];

        $uow = $this->getMockBuilder(UnitOfWorkMock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['computeChangeSet'])
            ->getMock();

        array_map([$uow, 'addInsertion'], $contactsArray);

        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->exactly(2))
            ->method('computeChangeSet')
            ->withConsecutive(
                [$metadata, $updatedEmailAddresses[0]],
                [$metadata, $updatedEmailAddresses[1]]
            );
        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $onFlushEventArgs->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->emailOwnerManager->expects($this->once())
            ->method('createEmailAddressData')
            ->with($this->identicalTo($uow))
            ->willReturn([]);
        $this->emailOwnerManager->expects($this->once())
            ->method('handleChangedAddresses')
            ->with([])
            ->willReturn([$updatedEmailAddresses, $createdEmailAddresses, ['test@test.com']]);

        $postFlushEventArgs = $this->createMock(PostFlushEventArgs::class);
        $postFlushEventArgs->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->emailActivityUpdates->expects($this->once())
            ->method('getFilteredOwnerEntitiesToUpdate')
            ->willReturn([]);
        $this->emailActivityUpdates->expects($this->once())
            ->method('clearPendingEntities');

        $this->producer->expects($this->never())
            ->method('send');

        $this->emailAddressVisibilityManager->expects(self::once())
            ->method('collectEmailAddresses')
            ->with(['test@test.com']);

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }
}
