<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationsTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\EventListener\EntityListener;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\ORM\Mocks\UnitOfWorkMock;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class EntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var EmailOwnerManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOwnerManager;

    /** @var EmailThreadManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailThreadManager;

    /** @var EmailActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailActivityManager;

    /** @var EmailOwnersProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOwnersProvider;

    /** @var EmailAddressVisibilityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressVisibilityManager;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var EntityListener */
    private $listener;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->emailOwnerManager = $this->createMock(EmailOwnerManager::class);
        $this->emailThreadManager = $this->createMock(EmailThreadManager::class);
        $this->emailActivityManager = $this->createMock(EmailActivityManager::class);
        $this->emailOwnersProvider = $this->createMock(EmailOwnersProvider::class);
        $this->emailAddressVisibilityManager = $this->createMock(EmailAddressVisibilityManager::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);

        $emailAddressManager = $this->createMock(EmailAddressManager::class);
        $emailAddressManager->expects(self::any())
            ->method('getEmailAddressRepository')
            ->willReturn($this->entityRepository);

        $container = TestContainerBuilder::create()
            ->add(EmailOwnerManager::class, $this->emailOwnerManager)
            ->add(EmailThreadManager::class, $this->emailThreadManager)
            ->add(EmailActivityManager::class, $this->emailActivityManager)
            ->add(EmailOwnersProvider::class, $this->emailOwnersProvider)
            ->add(EmailAddressVisibilityManager::class, $this->emailAddressVisibilityManager)
            ->add(EmailAddressManager::class, $emailAddressManager)
            ->getContainer($this);

        $this->listener = new EntityListener($this->producer, $container);
    }

    private function getEmail(?int $id = null): Email
    {
        $email = new Email();
        if (null !== $id) {
            ReflectionUtil::setId($email, $id);
        }

        return $email;
    }

    private function getEmailAddress(?int $id = null, ?EmailOwnerInterface $owner = null): EmailAddress
    {
        $emailAddress = new EmailAddress($id);
        if (null !== $owner) {
            $emailAddress->setOwner($owner);
        }

        return $emailAddress;
    }

    private function getHashedArray(array $entities): array
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[spl_object_hash($entity)] = $entity;
        }

        return $result;
    }

    private function addInsertions(UnitOfWorkMock $uow, array $entities): void
    {
        foreach ($entities as $entity) {
            $uow->addInsertion($entity);
        }
    }

    private function addUpdates(UnitOfWorkMock $uow, array $entities): void
    {
        foreach ($entities as $entity) {
            $uow->addUpdate($entity);
        }
    }

    public function testOnFlush(): void
    {
        $createdEmails = [$this->getEmail()];
        $updatedEmails = [$this->getEmail(1)];
        $createdEmailAddresses = [$this->getEmailAddress()];
        $updatedEmailAddresses = [$this->getEmailAddress(1), $this->getEmailAddress(2)];
        $processedAddresses = ['test@test.com'];
        $emailAddressData = ['updates' => [], 'deletions' => []];

        $uow = $this->getMockBuilder(UnitOfWorkMock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['computeChangeSet'])
            ->getMock();
        $this->addInsertions($uow, array_merge(array_values($createdEmails), [new User()]));
        $this->addUpdates($uow, $updatedEmails);

        $this->emailOwnerManager->expects(self::once())
            ->method('createEmailAddressData')
            ->with(self::identicalTo($uow))
            ->willReturn($emailAddressData);
        $this->emailOwnerManager->expects(self::once())
            ->method('handleChangedAddresses')
            ->with($emailAddressData)
            ->willReturn([$updatedEmailAddresses, $createdEmailAddresses, $processedAddresses]);

        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManager::class);
        $em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::exactly(2))
            ->method('computeChangeSet')
            ->withConsecutive(
                [self::identicalTo($metadata), self::identicalTo($updatedEmailAddresses[0])],
                [self::identicalTo($metadata), self::identicalTo($updatedEmailAddresses[1])]
            );

        $this->listener->onFlush(new OnFlushEventArgs($em));

        self::assertSame(
            $this->getHashedArray($createdEmails),
            ReflectionUtil::getPropertyValue($this->listener, 'newEmails')
        );
        self::assertSame(
            $this->getHashedArray($updatedEmails),
            ReflectionUtil::getPropertyValue($this->listener, 'updatedEmails')
        );
        self::assertSame(
            $this->getHashedArray($createdEmails),
            ReflectionUtil::getPropertyValue($this->listener, 'emailsToUpdateActivities')
        );
        self::assertSame(
            $createdEmailAddresses,
            ReflectionUtil::getPropertyValue($this->listener, 'newEmailAddresses')
        );
        self::assertSame(
            $updatedEmailAddresses,
            ReflectionUtil::getPropertyValue($this->listener, 'updatedEmailAddresses')
        );
        self::assertSame(
            $processedAddresses,
            ReflectionUtil::getPropertyValue($this->listener, 'processedAddresses')
        );
    }

    public function testPostFlushWhenEmailAddressHasOwner(): void
    {
        $createdEmails = $this->getHashedArray([$this->getEmail(), $this->getEmail(), $this->getEmail()]);
        $updatedEmails = $this->getHashedArray([$this->getEmail(1)]);
        $createdEmailAddresses = [];
        $updatedEmailAddresses = [$this->getEmailAddress(1, new TestEmailOwner(123))];

        $uow = $this->getMockBuilder(UnitOfWorkMock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['computeChangeSet'])
            ->getMock();
        $this->addInsertions($uow, array_merge(array_values($createdEmails), [new User()]));
        $this->addUpdates($uow, $updatedEmails);

        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->emailOwnerManager->expects(self::once())
            ->method('createEmailAddressData')
            ->willReturn([]);
        $this->emailOwnerManager->expects(self::once())
            ->method('handleChangedAddresses')
            ->willReturn([$updatedEmailAddresses, $createdEmailAddresses, []]);
        $this->emailActivityManager->expects(self::once())
            ->method('updateActivities')
            ->with($createdEmails);
        $this->emailThreadManager->expects(self::once())
            ->method('updateThreads')
            ->with($createdEmails);
        $this->emailThreadManager->expects(self::once())
            ->method('updateHeads')
            ->with($updatedEmails);
        $this->emailActivityManager->expects(self::once())
            ->method('updateActivities')
            ->with($createdEmails);

        $this->emailOwnersProvider->expects(self::once())
            ->method('hasEmailsByOwnerEntity')
            ->willReturn(true);

        $this->emailActivityManager->expects(self::never())
            ->method('addAssociation');

        $this->producer->expects(self::once())
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

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush(new PostFlushEventArgs($em));
        // Test that second flush will not trigger second round of processing
        $this->listener->postFlush(new PostFlushEventArgs($em));
    }

    public function testPostFlushWhenEmailAddressDoesNotHaveOwner(): void
    {
        $createdEmailAddresses = [$this->getEmailAddress()];
        $updatedEmailAddresses = [$this->getEmailAddress(1), $this->getEmailAddress(2)];

        $uow = $this->getMockBuilder(UnitOfWorkMock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['computeChangeSet'])
            ->getMock();

        $metadata = $this->createMock(ClassMetadata::class);
        $em = $this->createMock(EntityManager::class);
        $em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::exactly(2))
            ->method('computeChangeSet')
            ->withConsecutive(
                [self::identicalTo($metadata), self::identicalTo($updatedEmailAddresses[0])],
                [self::identicalTo($metadata), self::identicalTo($updatedEmailAddresses[1])]
            );

        $this->emailOwnerManager->expects(self::once())
            ->method('createEmailAddressData')
            ->with(self::identicalTo($uow))
            ->willReturn([]);
        $this->emailOwnerManager->expects(self::once())
            ->method('handleChangedAddresses')
            ->with([])
            ->willReturn([$updatedEmailAddresses, $createdEmailAddresses, ['test@test.com']]);

        $this->emailOwnersProvider->expects(self::never())
            ->method('hasEmailsByOwnerEntity');

        $this->producer->expects(self::never())
            ->method('send');

        $this->emailAddressVisibilityManager->expects(self::once())
            ->method('collectEmailAddresses')
            ->with(['test@test.com']);

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush(new PostFlushEventArgs($em));
    }
}
