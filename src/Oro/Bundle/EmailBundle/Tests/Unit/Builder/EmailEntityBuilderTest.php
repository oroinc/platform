<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBatchProcessor;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Exception\EmailAddressParseException;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailAddressProxy;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\EmailBodyStub;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailEntityBuilderTest extends \PHPUnit\Framework\TestCase
{
    private EmailEntityBatchProcessor|\PHPUnit\Framework\MockObject\MockObject $batch;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private EmailEntityBuilder $builder;

    protected function setUp(): void
    {
        $this->batch = $this->createMock(EmailEntityBatchProcessor::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->builder = new EmailEntityBuilder(
            $this->batch,
            new EmailAddressManager(
                'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures',
                'Test%sProxy',
                $this->doctrine
            ),
            new EmailAddressHelper(),
            $this->doctrine,
            $this->logger
        );
    }

    private function initEmailStorage(): void
    {
        $storage = [];
        $this->batch->expects(self::any())
            ->method('getAddress')
            ->willReturnCallback(function ($email) use (&$storage) {
                return $storage[$email] ?? null;
            });
        $this->batch->expects(self::any())
            ->method('addAddress')
            ->willReturnCallback(function ($obj) use (&$storage) {
                /** @var EmailAddress $obj */
                $storage[$obj->getEmail()] = $obj;
            });
    }

    public function testEmailUser(): void
    {
        $this->mockMetadata();

        $this->initEmailStorage();

        $date = new \DateTime('now');
        $emailUser = $this->builder->emailUser(
            'testSubject',
            '"Test" <test@example.com>',
            '"Test1" <test1@example.com>',
            $date,
            $date,
            $date,
            Email::NORMAL_IMPORTANCE,
            ['"Test2" <test2@example.com>', 'test1@example.com'],
            ['"Test3" <test3@example.com>', 'test4@example.com']
        );

        self::assertEquals('testSubject', $emailUser->getEmail()->getSubject());
        self::assertEquals('"Test" <test@example.com>', $emailUser->getEmail()->getFromName());
        self::assertEquals('test@example.com', $emailUser->getEmail()->getFromEmailAddress()->getEmail());
        self::assertEquals($date, $emailUser->getEmail()->getSentAt());

        self::assertEquals($date, $emailUser->getEmail()->getInternalDate());
        self::assertEquals(Email::NORMAL_IMPORTANCE, $emailUser->getEmail()->getImportance());
        $to = $emailUser->getEmail()->getRecipients(EmailRecipient::TO);
        self::assertEquals('"Test1" <test1@example.com>', $to[0]->getName());
        self::assertEquals('test1@example.com', $to[0]->getEmailAddress()->getEmail());
        $cc = $emailUser->getEmail()->getRecipients(EmailRecipient::CC);
        self::assertEquals('"Test2" <test2@example.com>', $cc[1]->getName());
        self::assertEquals('test2@example.com', $cc[1]->getEmailAddress()->getEmail());
        self::assertEquals('test1@example.com', $cc[2]->getName());
        self::assertEquals('test1@example.com', $cc[2]->getEmailAddress()->getEmail());
        $bcc = $emailUser->getEmail()->getRecipients(EmailRecipient::BCC);
        self::assertEquals('"Test3" <test3@example.com>', $bcc[3]->getName());
        self::assertEquals('test3@example.com', $bcc[3]->getEmailAddress()->getEmail());
        self::assertEquals('test4@example.com', $bcc[4]->getName());
        self::assertEquals('test4@example.com', $bcc[4]->getEmailAddress()->getEmail());
        self::assertCount(2, $bcc);
        self::assertTrue($emailUser->getEmail()->getEmailUsers()->contains($emailUser));
    }

    /**
     * @dataProvider getTestToRecipientDataProvider
     */
    public function testToRecipient(string $recipient, string $expectedName, string $expectedEmail): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::once())
            ->method('getFieldMapping')
            ->with('name')
            ->willReturn(['length' => 100]);
        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(EmailRecipient::class)
            ->willReturn($metadata);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(EmailRecipient::class)
            ->willReturn($em);

        $this->initEmailStorage();
        $result = $this->builder->recipientTo($recipient);

        self::assertEquals(EmailRecipient::TO, $result->getType());
        self::assertEquals($expectedName, $result->getName());
        self::assertEquals($expectedEmail, $result->getEmailAddress()->getEmail());
    }

    public function getTestToRecipientDataProvider(): array
    {
        return [
            'full recipient' => [
                'recipient' => '"Test" <test@example.com>',
                'expectedName' => '"Test" <test@example.com>',
                'expectedEmail' => 'test@example.com',
            ],
            'email address' => [
                'recipient' => 'test@example.com',
                'expectedName' => 'test@example.com',
                'expectedEmail' => 'test@example.com',
            ],
        ];
    }

    public function testCcRecipient(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::once())
            ->method('getFieldMapping')
            ->with('name')
            ->willReturn(['length' => 100]);
        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(EmailRecipient::class)
            ->willReturn($metadata);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(EmailRecipient::class)
            ->willReturn($em);

        $this->initEmailStorage();
        $result = $this->builder->recipientCc('"Test" <test@example.com>');

        self::assertEquals(EmailRecipient::CC, $result->getType());
        self::assertEquals('"Test" <test@example.com>', $result->getName());
        self::assertEquals('test@example.com', $result->getEmailAddress()->getEmail());
    }

    public function testBccRecipient(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::once())
            ->method('getFieldMapping')
            ->with('name')
            ->willReturn(['length' => 100]);
        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(EmailRecipient::class)
            ->willReturn($metadata);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(EmailRecipient::class)
            ->willReturn($em);

        $this->initEmailStorage();
        $result = $this->builder->recipientBcc('"Test" <test@example.com>');

        self::assertEquals(EmailRecipient::BCC, $result->getType());
        self::assertEquals('"Test" <test@example.com>', $result->getName());
        self::assertEquals('test@example.com', $result->getEmailAddress()->getEmail());
    }

    public function testFolder(): void
    {
        $storage = [];
        $this->batch->expects(self::exactly(10))
            ->method('getFolder')
            ->willReturnCallback(function ($type, $name) use (&$storage) {
                return $storage[$type . $name] ?? null;
            });
        $this->batch->expects(self::exactly(5))
            ->method('addFolder')
            ->willReturnCallback(function ($obj) use (&$storage) {
                /** @var EmailFolder $obj */
                $storage[$obj->getType() . $obj->getFullName()] = $obj;
            });

        $inbox = $this->builder->folderInbox('test', 'test');
        $sent = $this->builder->folderSent('test', 'test');
        $drafts = $this->builder->folderDrafts('test', 'test');
        $trash = $this->builder->folderTrash('test', 'test');
        $other = $this->builder->folderOther('test', 'test');

        self::assertEquals('test', $inbox->getName());
        self::assertEquals('test', $inbox->getFullName());
        self::assertEquals('test', $sent->getName());
        self::assertEquals('test', $sent->getFullName());
        self::assertEquals('test', $drafts->getName());
        self::assertEquals('test', $drafts->getFullName());
        self::assertEquals('test', $trash->getName());
        self::assertEquals('test', $trash->getFullName());
        self::assertEquals('test', $other->getName());
        self::assertEquals('test', $other->getFullName());
        self::assertSame($inbox, $this->builder->folderInbox('test', 'test'));
        self::assertSame($sent, $this->builder->folderSent('test', 'test'));
        self::assertSame($drafts, $this->builder->folderDrafts('test', 'test'));
        self::assertSame($trash, $this->builder->folderTrash('test', 'test'));
        self::assertSame($other, $this->builder->folderOther('test', 'test'));
    }

    public function testBody(): void
    {
        $body = $this->builder->body('testContent', true, true);

        self::assertEquals('testContent', $body->getBodyContent());
        self::assertEquals('testContent', $body->getTextBody());
        self::assertFalse($body->getBodyIsText());
        self::assertTrue($body->getPersistent());
    }

    public function testAttachment(): void
    {
        $attachment = $this->builder->attachment('testFileName', 'testContentType');

        self::assertEquals('testFileName', $attachment->getFileName());
        self::assertEquals('testContentType', $attachment->getContentType());
    }

    public function testAttachmentContent(): void
    {
        $attachmentContent = $this->builder->attachmentContent('testContent', 'testEncoding');

        self::assertEquals('testContent', $attachmentContent->getContent());
        self::assertEquals('testEncoding', $attachmentContent->getContentTransferEncoding());
    }

    public function testGetBatch(): void
    {
        self::assertSame($this->batch, $this->builder->getBatch());
    }

    public function testGetEmailAddressEntityClass(): void
    {
        self::assertEquals(TestEmailAddressProxy::class, $this->builder->getEmailAddressEntityClass());
    }

    /**
     * @dataProvider validateEmailDataProvider
     */
    public function testValidateEmailAddress(string $email, ?string $expectedException, string $message): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
            $this->expectExceptionMessage($message);
        }
        $this->builder->address($email);
    }

    public function validateEmailDataProvider(): array
    {
        return [
            [
                'email' => 'testemail',
                'expectedException' => EmailAddressParseException::class,
                'expectedExceptionMessage' => 'Not valid email address',
            ],
            [
                'email' => str_repeat('domain', 50) . '@mail.com',
                'expectedException' => EmailAddressParseException::class,
                'expectedExceptionMessage' => 'Email address is too long',
            ],
            [
                'email' => 'test@example.com',
                'expectedException' => null,
                'expectedExceptionMessage' => '',
            ],
            [
                'email' => '',
                'expectedException' => EmailAddressParseException::class,
                'expectedExceptionMessage' => 'Not valid email address',
            ],
        ];
    }

    /**
     * @dataProvider getTestValidateRecipientEmailAddressDataProvider
     */
    public function testValidateRecipientEmailAddress(string $email, string $expectedContextMessage): void
    {
        $this->mockMetadata();

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'An invalid recipient address has been ignored',
                ['exception' => $expectedContextMessage]
            );

        $date = new \DateTime('now');
        $this->builder->emailUser(
            'testSubject',
            '"Test" <test@example.com>',
            $email,
            $date,
            $date,
            $date,
            Email::NORMAL_IMPORTANCE,
            ['test1@example.com']
        );
    }

    public function getTestValidateRecipientEmailAddressDataProvider(): array
    {
        $longEmailAddress = str_repeat('domain', 50) . '@mail.com';

        return [
            [
                'email' => ' ',
                'expectedContextMessage' => 'Not valid email address:  ',
            ],
            [
                'email' => 'test email',
                'expectedContextMessage' => 'Not valid email address: test email',
            ],
            [
                'email' => $longEmailAddress,
                'expectedContextMessage' => 'Email address is too long: ' . $longEmailAddress,
            ],
        ];
    }

    private function mockMetadata(): void
    {
        $emailMetadata = $this->createMock(ClassMetadata::class);
        $emailMetadata->expects(self::once())
            ->method('getFieldMapping')
            ->with('fromName')
            ->willReturn(['length' => 100]);
        $emailRecipientMetadata = $this->createMock(ClassMetadata::class);
        $emailRecipientMetadata->expects(self::once())
            ->method('getFieldMapping')
            ->with('name')
            ->willReturn(['length' => 100]);
        $em = $this->createMock(EntityManager::class);
        $em->expects(self::exactly(2))
            ->method('getClassMetadata')
            ->willReturnMap([
                [Email::class, $emailMetadata],
                [EmailRecipient::class, $emailRecipientMetadata],
            ]);
        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturn($em);
    }

    public function testAddEmailAttachmentEntityDoesNothingWhenAlreadyBelongs(): void
    {
        $emailBody = new EmailBodyStub(42);
        $emailAttachment = new EmailAttachment();
        $emailBody->addAttachment($emailAttachment);

        self::assertContains($emailAttachment, $emailBody->getAttachments());

        $this->builder->addEmailAttachmentEntity($emailBody, $emailAttachment);

        self::assertContains($emailAttachment, $emailBody->getAttachments());
    }

    public function testAddEmailAttachmentEntityAddsWhenNew(): void
    {
        $emailBody = new EmailBodyStub(42);
        $emailAttachment = new EmailAttachment();
        $emailAttachment->setContent(new EmailAttachmentContent());

        self::assertNotContains($emailAttachment, $emailBody->getAttachments());

        $this->builder->addEmailAttachmentEntity($emailBody, $emailAttachment);

        self::assertContains($emailAttachment, $emailBody->getAttachments());
    }

    public function testAddEmailAttachmentEntityClonesWhenBelongsToAnotherBody(): void
    {
        $emailBody = new EmailBodyStub(42);
        $emailBody2 = new EmailBodyStub(4242);
        $emailAttachment = (new EmailAttachment())
            ->setFileName('sample_file');
        $emailAttachmentContent = (new EmailAttachmentContent())
            ->setContent('sample_content');
        $emailAttachment->setContent($emailAttachmentContent);
        $emailBody2->addAttachment($emailAttachment);

        self::assertNotContains($emailAttachment, $emailBody->getAttachments());

        $this->builder->addEmailAttachmentEntity($emailBody, $emailAttachment);

        self::assertNotContains($emailAttachment, $emailBody->getAttachments());

        /** @var EmailAttachment $actualAttachment */
        $actualAttachment = $emailBody->getAttachments()->first();
        self::assertEquals($emailAttachmentContent->getContent(), $emailAttachmentContent->getContent());
        self::assertEquals($emailAttachment->getFileName(), $actualAttachment->getFileName());
    }

    public function testEmailUserWithEmptyFromHeader(): void
    {
        $this->expectException(EmailAddressParseException::class);
        $this->expectExceptionMessage('Missed FROM part in email message.');

        $date = new \DateTime('now');
        $this->builder->emailUser(
            'testSubject',
            '',
            'test@test.com',
            $date,
            $date,
            $date,
            Email::NORMAL_IMPORTANCE,
            ['test1@example.com']
        );
    }
}
