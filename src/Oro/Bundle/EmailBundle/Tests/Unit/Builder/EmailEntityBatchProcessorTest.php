<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Doctrine\ORM\EntityManager;
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
use Symfony\Component\EventDispatcher\EventDispatcher;

class EmailEntityBatchProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailAddressManager|\PHPUnit\Framework\MockObject\MockObject */
    private $addressManager;

    /** @var EmailOwnerProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $ownerProvider;

    /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var EmailEntityBatchProcessor */
    private $batch;

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

    public function testAddEmail()
    {
        $this->batch->addEmailUser(new EmailUser());
        $this->assertCount(1, ReflectionUtil::getPropertyValue($this->batch, 'emailUsers'));
    }

    public function testAddAddress()
    {
        $this->batch->addAddress($this->addressManager->newEmailAddress()->setEmail('Test@example.com'));
        $this->assertCount(1, ReflectionUtil::getPropertyValue($this->batch, 'addresses'));

        $this->assertEquals('Test@example.com', $this->batch->getAddress('TeST@example.com')->getEmail());
        $this->assertNull($this->batch->getAddress('Another@example.com'));

        $this->expectException(\LogicException::class);
        $this->batch->addAddress($this->addressManager->newEmailAddress()->setEmail('TEST@example.com'));
    }

    public function testAddFolder()
    {
        $folder = new EmailFolder();
        $folder->setType('sent');
        $folder->setName('Test');
        $folder->setFullName('Test');
        $this->batch->addFolder($folder);
        $this->assertCount(1, ReflectionUtil::getPropertyValue($this->batch, 'folders'));

        $this->assertEquals('Test', $this->batch->getFolder('sent', 'TeST')->getFullName());
        $this->assertNull($this->batch->getFolder('sent', 'Another'));

        $folder1 = new EmailFolder();
        $folder1->setType('trash');
        $folder1->setName('Test');
        $folder1->setFullName('Test');
        $this->batch->addFolder($folder1);
        $this->assertCount(2, ReflectionUtil::getPropertyValue($this->batch, 'folders'));

        $this->assertEquals('Test', $this->batch->getFolder('trash', 'TeST')->getFullName());
        $this->assertNull($this->batch->getFolder('trash', 'Another'));

        $this->expectException(\LogicException::class);
        $folder2 = new EmailFolder();
        $folder2->setType('sent');
        $folder2->setName('TEST');
        $folder2->setFullName('TEST');
        $this->batch->addFolder($folder2);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPersist()
    {
        $origin = $this->createMock(EmailOrigin::class);
        $origin->expects($this->any())
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

        $em = $this->createMock(EntityManager::class);
        $folderRepo = $this->createMock(EntityRepository::class);
        $addressRepo = $this->createMock(EntityRepository::class);
        $emailRepo = $this->createMock(EntityRepository::class);
        $em->expects($this->exactly(3))
            ->method('getRepository')
            ->willReturnMap([
                ['OroEmailBundle:EmailFolder', $folderRepo],
                [TestEmailAddressProxy::class, $addressRepo],
                ['OroEmailBundle:Email', $emailRepo],
            ]);

        $folderRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(function ($c) use (&$dbFolder) {
                return $c['fullName'] === 'Exist' ? $dbFolder : null;
            });
        $addressRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(function ($c) use (&$dbAddress) {
                return $c['email'] === 'Exist' ? $dbAddress : null;
            });
        $emailRepo->expects($this->once())
            ->method('findBy')
            ->with(['messageId' => ['email1', 'email2', 'some_email']])
            ->willReturn([$existingEmail]);

        $owner = $this->createMock(EmailOwnerInterface::class);

        $this->ownerProvider->expects($this->any())
            ->method('findEmailOwner')
            ->willReturn($owner);

        $this->batch->persist($em);

        $this->assertCount(1, $email1->getEmailUsers());
        $this->assertCount(1, $email2->getEmailUsers());
        $this->assertSame($origin, $emailUser1->getOrigin());
        $this->assertSame($origin, $emailUser2->getOrigin());
        $this->assertSame($newFolder, $emailUser2->getFolders()->first());
        $this->assertSame($dbFolder, $emailUser1->getFolders()->first());
        $this->assertSame($dbAddress, $email1->getFromEmailAddress());
        $this->assertNull($email1->getFromEmailAddress()->getOwner());
        $this->assertSame($newAddress, $email2->getFromEmailAddress());
        $this->assertSame($owner, $email2->getFromEmailAddress()->getOwner());
        $email1Recipients = $email1->getRecipients();
        $this->assertSame($dbAddress, $email1Recipients[0]->getEmailAddress());
        $this->assertNull($email1Recipients[0]->getEmailAddress()->getOwner());
        $this->assertSame($newAddress, $email1Recipients[1]->getEmailAddress());
        $this->assertSame($owner, $email1Recipients[1]->getEmailAddress()->getOwner());
        $email2Recipients = $email2->getRecipients();
        $this->assertSame($dbAddress, $email2Recipients[0]->getEmailAddress());
        $this->assertNull($email2Recipients[0]->getEmailAddress()->getOwner());
        $this->assertSame($newAddress, $email2Recipients[1]->getEmailAddress());
        $this->assertSame($owner, $email2Recipients[1]->getEmailAddress()->getOwner());

        $changes = $this->batch->getChanges();
        $this->assertCount(3, $changes);
        $this->assertSame($folder, $changes[0]['old']);
        $this->assertSame($dbFolder, $changes[0]['new']);
        $this->assertSame($email3, $changes[1]['old']);
        $this->assertSame($existingEmail, $changes[1]['new']);
        $this->assertSame($email4, $changes[2]['old']);
        $this->assertSame($existingEmail, $changes[2]['new']);
    }
}
