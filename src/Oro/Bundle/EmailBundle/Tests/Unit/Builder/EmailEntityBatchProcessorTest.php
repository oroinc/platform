<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBatchProcessor;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailAddressProxy;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EmailEntityBatchProcessorTest extends TestCase
{
    private EmailAddressManager $addressManager;
    private EmailOwnerProvider&MockObject $ownerProvider;
    private EventDispatcher&MockObject $eventDispatcher;
    private EmailEntityBatchProcessor $batch;

    #[\Override]
    protected function setUp(): void
    {
        $this->ownerProvider = $this->createMock(EmailOwnerProvider::class);
        $this->addressManager = new EmailAddressManager(
            'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures',
            'Test%sProxy',
            $this->createMock(ManagerRegistry::class)
        );
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);

        $this->batch = new EmailEntityBatchProcessor(
            $this->addressManager,
            $this->ownerProvider,
            $this->eventDispatcher
        );
    }

    private function addEmailRecipient(Email $email, EmailAddress $address)
    {
        $recipient = new EmailRecipient();
        $recipient->setEmailAddress($address);

        $email->addRecipient($recipient);
    }

    public function testAddEmail(): void
    {
        $this->batch->addEmailUser(new EmailUser());
        self::assertCount(1, ReflectionUtil::getPropertyValue($this->batch, 'emailUsers'));
    }

    public function testAddAddress(): void
    {
        $this->batch->addAddress($this->addressManager->newEmailAddress()->setEmail('Test@example.com'));
        self::assertCount(1, ReflectionUtil::getPropertyValue($this->batch, 'addresses'));

        self::assertEquals('Test@example.com', $this->batch->getAddress('TeST@example.com')->getEmail());
        self::assertNull($this->batch->getAddress('Another@example.com'));

        $this->expectException(\LogicException::class);
        $this->batch->addAddress($this->addressManager->newEmailAddress()->setEmail('TEST@example.com'));
    }

    public function testAddFolder(): void
    {
        $folder = new EmailFolder();
        $folder->setType('sent');
        $folder->setName('Test');
        $folder->setFullName('Test');
        $this->batch->addFolder($folder);
        self::assertCount(1, ReflectionUtil::getPropertyValue($this->batch, 'folders'));

        self::assertEquals('Test', $this->batch->getFolder('sent', 'TeST')->getFullName());
        self::assertNull($this->batch->getFolder('sent', 'Another'));

        $folder1 = new EmailFolder();
        $folder1->setType('trash');
        $folder1->setName('Test');
        $folder1->setFullName('Test');
        $this->batch->addFolder($folder1);
        self::assertCount(2, ReflectionUtil::getPropertyValue($this->batch, 'folders'));

        self::assertEquals('Test', $this->batch->getFolder('trash', 'TeST')->getFullName());
        self::assertNull($this->batch->getFolder('trash', 'Another'));

        self::assertSame([$folder, $folder1], $this->batch->getFolders());
    }

    public function testAddFolderWhenItAlreadyExists(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The folder "TEST" (type: sent) already exists in the batch.');

        $folder = new EmailFolder();
        $folder->setType('sent');
        $folder->setName('Test');
        $folder->setFullName('Test');
        $this->batch->addFolder($folder);

        $folder2 = new EmailFolder();
        $folder2->setType('sent');
        $folder2->setName('TEST');
        $folder2->setFullName('TEST');
        $this->batch->addFolder($folder2);
    }

    /**
     * @dataProvider persistDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPersist(bool $dryRun): void
    {
        $origin = $this->createMock(EmailOrigin::class);
        $origin->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $folder = new EmailFolder();
        $folder->setName('Exist');
        $folder->setFullName('Exist');
        $folder->setOrigin($origin);
        $this->batch->addFolder($folder);

        $newFolder = new EmailFolder();
        $newFolder->setName('New');
        $newFolder->setFullName('New');
        $newFolder->setOrigin($origin);
        $this->batch->addFolder($newFolder);

        $dbFolder = new EmailFolder();
        $dbFolder->setName('DbExist');
        $dbFolder->setFullName('DbExist');
        $dbFolder->setOrigin($origin);

        $address = $this->addressManager->newEmailAddress()->setEmail('Exist');
        $this->batch->addAddress($address);
        $newAddress = $this->addressManager->newEmailAddress()->setEmail('New');
        $this->batch->addAddress($newAddress);

        $dbAddress = $this->addressManager->newEmailAddress()->setEmail('DbExist');

        $email1 = new Email();
        $email1->setXMessageId('email1');
        $email1->setMessageId('email1');
        $email1->setFromEmailAddress($address);
        $emailUser1 = new EmailUser();
        $emailUser1->addFolder($folder);
        $emailUser1->setOrigin($origin);
        $email1->addEmailUser($emailUser1);
        $this->addEmailRecipient($email1, $address);
        $this->addEmailRecipient($email1, $newAddress);
        $this->batch->addEmailUser($emailUser1);

        $email2 = new Email();
        $email2->setXMessageId('email2');
        $email2->setMessageId('email2');
        $email2->setFromEmailAddress($newAddress);
        $emailUser2 = new EmailUser();
        $emailUser2->addFolder($newFolder);
        $emailUser2->setOrigin($origin);
        $email2->addEmailUser($emailUser2);
        $this->addEmailRecipient($email2, $address);
        $this->addEmailRecipient($email2, $newAddress);
        $this->batch->addEmailUser($emailUser2);

        $email3 = new Email();
        $email3->setXMessageId('email3');
        $email3->setMessageId('some_email');
        $email3->setFromEmailAddress($newAddress);
        $emailUser3 = new EmailUser();
        $emailUser3->addFolder($folder);
        $email3->addEmailUser($emailUser3);
        $this->addEmailRecipient($email3, $address);
        $this->addEmailRecipient($email3, $newAddress);
        $this->batch->addEmailUser($emailUser3);

        $email4 = new Email();
        $email4->setXMessageId('email4');
        $email4->setMessageId('some_email');
        $email4->setFromEmailAddress($newAddress);
        $emailUser4 = new EmailUser();
        $emailUser4->addFolder($folder);
        $email4->addEmailUser($emailUser4);
        $this->addEmailRecipient($email4, $address);
        $this->addEmailRecipient($email4, $newAddress);
        $this->batch->addEmailUser($emailUser4);

        $existingEmail = new Email();
        $existingEmail->setXMessageId('existing_email');
        $existingEmail->setMessageId('some_email');
        $existingEmail->setFromEmailAddress($newAddress);
        $emailUser5 = new EmailUser();
        $emailUser5->addFolder($dbFolder);
        $existingEmail->addEmailUser($emailUser5);
        $this->addEmailRecipient($existingEmail, $address);
        $this->addEmailRecipient($existingEmail, $newAddress);

        $em = $this->createMock(EntityManagerInterface::class);
        $folderRepo = $this->createMock(EntityRepository::class);
        $addressRepo = $this->createMock(EntityRepository::class);
        $emailRepo = $this->createMock(EntityRepository::class);
        $em->expects(self::exactly(3))
            ->method('getRepository')
            ->willReturnMap([
                [EmailFolder::class, $folderRepo],
                [TestEmailAddressProxy::class, $addressRepo],
                [Email::class, $emailRepo],
            ]);

        $folderRepo->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(function ($c) use (&$dbFolder) {
                return $c['fullName'] === 'Exist' ? $dbFolder : null;
            });
        $addressRepo->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(function ($c) use (&$dbAddress) {
                return $c['email'] === 'Exist' ? $dbAddress : null;
            });
        $emailRepo->expects(self::once())
            ->method('findBy')
            ->with(['messageId' => ['email1', 'email2', 'some_email']])
            ->willReturn([$existingEmail]);

        $owner = $this->createMock(EmailOwnerInterface::class);

        $this->ownerProvider->expects(self::any())
            ->method('findEmailOwner')
            ->willReturn($owner);

        if ($dryRun) {
            $em->expects(self::never())
                ->method('persist');
        } else {
            $em->expects(self::exactly(6))
                ->method('persist')
                ->withConsecutive(
                    [$this->identicalTo($newFolder)],
                    [$this->identicalTo($newAddress)],
                    [$this->identicalTo($emailUser1)],
                    [$this->identicalTo($emailUser2)],
                    [$this->identicalTo($emailUser3)],
                    [$this->identicalTo($emailUser4)]
                );
        }

        $persistedEntities = $this->batch->persist($em, $dryRun);

        self::assertSame(
            [$newFolder, $newAddress, $emailUser1, $emailUser2, $emailUser3, $emailUser4],
            $persistedEntities
        );

        self::assertCount(1, $email1->getEmailUsers());
        self::assertCount(1, $email2->getEmailUsers());
        self::assertSame($origin, $emailUser1->getOrigin());
        self::assertSame($origin, $emailUser2->getOrigin());
        self::assertSame($newFolder, $emailUser2->getFolders()->first());
        self::assertSame($dbFolder, $emailUser1->getFolders()->first());
        self::assertSame($dbAddress, $email1->getFromEmailAddress());
        self::assertNull($email1->getFromEmailAddress()->getOwner());
        self::assertSame($newAddress, $email2->getFromEmailAddress());
        self::assertSame($owner, $email2->getFromEmailAddress()->getOwner());
        $email1Recipients = $email1->getRecipients();
        self::assertSame($dbAddress, $email1Recipients[0]->getEmailAddress());
        self::assertNull($email1Recipients[0]->getEmailAddress()->getOwner());
        self::assertSame($newAddress, $email1Recipients[1]->getEmailAddress());
        self::assertSame($owner, $email1Recipients[1]->getEmailAddress()->getOwner());
        $email2Recipients = $email2->getRecipients();
        self::assertSame($dbAddress, $email2Recipients[0]->getEmailAddress());
        self::assertNull($email2Recipients[0]->getEmailAddress()->getOwner());
        self::assertSame($newAddress, $email2Recipients[1]->getEmailAddress());
        self::assertSame($owner, $email2Recipients[1]->getEmailAddress()->getOwner());

        $changes = $this->batch->getChanges();
        self::assertCount(3, $changes);
        self::assertSame($folder, $changes[0]['old']);
        self::assertSame($dbFolder, $changes[0]['new']);
        self::assertSame($email3, $changes[1]['old']);
        self::assertSame($existingEmail, $changes[1]['new']);
        self::assertSame($email4, $changes[2]['old']);
        self::assertSame($existingEmail, $changes[2]['new']);
    }

    public function persistDataProvider(): array
    {
        return [[false], [true]];
    }
}
