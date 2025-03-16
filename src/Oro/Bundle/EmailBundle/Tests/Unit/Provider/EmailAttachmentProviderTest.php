<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailAttachmentProvider;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailAttachmentProviderTest extends TestCase
{
    private EmailThreadProvider&MockObject $emailThreadProvider;
    private ManagerRegistry&MockObject $doctrine;
    private AttachmentProvider&MockObject $attachmentProvider;
    private EmailAttachmentTransformer&MockObject $emailAttachmentTransformer;
    private EmailAttachmentProvider $emailAttachmentProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailThreadProvider = $this->createMock(EmailThreadProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->attachmentProvider = $this->createMock(AttachmentProvider::class);
        $this->emailAttachmentTransformer = $this->createMock(EmailAttachmentTransformer::class);

        $this->emailAttachmentProvider = new EmailAttachmentProvider(
            $this->emailThreadProvider,
            $this->doctrine,
            $this->attachmentProvider,
            $this->emailAttachmentTransformer
        );
    }

    private function getAttachments(int $count): array
    {
        $attachments = [];
        for ($i = 0; $i < $count; $i++) {
            $attachments[] = $this->createMock(EmailAttachment::class);
        }

        return $attachments;
    }

    /**
     * @dataProvider threadEmailsProvider
     */
    public function testGetThreadAttachments(array $threadEmails, int $transformationCalls): void
    {
        $emailEntity = $this->createMock(Email::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManager')
            ->willReturn($em);

        $this->emailThreadProvider->expects(self::once())
            ->method('getThreadEmails')
            ->with($em, $emailEntity)
            ->willReturn($threadEmails);

        $this->emailAttachmentTransformer->expects(self::exactly($transformationCalls))
            ->method('entityToModel');

        $result = $this->emailAttachmentProvider->getThreadAttachments($emailEntity);
        self::assertCount($transformationCalls, $result);
    }

    public function threadEmailsProvider(): array
    {
        $threadEmails = [];
        $transformationCalls = 0;
        $threadEmailCount = 3;
        for ($i = 0; $i < $threadEmailCount; $i++) {
            $emailBody = $this->createMock(EmailBody::class);
            $emailBody->expects(self::once())
                ->method('getHasAttachments')
                ->willReturn(true);

            $attachments = $this->getAttachments($i + 1);
            $transformationCalls += $i + 1;

            $emailBody->expects(self::once())
                ->method('getAttachments')
                ->willReturn($attachments);

            $threadEmail = $this->createMock(Email::class);
            $threadEmail->expects(self::exactly($threadEmailCount))
                ->method('getEmailBody')
                ->willReturn($emailBody);

            $threadEmails[] = $threadEmail;
        }

        return [
            [
                'threadEmails' => $threadEmails,
                'transformationCalls' => $transformationCalls,
            ],
        ];
    }

    public function testGetScopeEntityAttachments(): void
    {
        $entity = $this->createMock(\stdClass::class);

        $attachments = [];
        for ($i = 0; $i < 3; $i++) {
            $attachments[] = $this->createMock(Attachment::class);
        }

        $this->attachmentProvider->expects(self::once())
            ->method('getEntityAttachments')
            ->with($entity)
            ->willReturn($attachments);

        $this->emailAttachmentTransformer->expects(self::exactly(count($attachments)))
            ->method('attachmentEntityToModel');

        $result = $this->emailAttachmentProvider->getScopeEntityAttachments($entity);
        self::assertCount(count($attachments), $result);
    }
}
