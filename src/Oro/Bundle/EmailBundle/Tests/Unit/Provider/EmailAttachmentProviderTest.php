<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailAttachmentProvider;
use Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer;

class EmailAttachmentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailThreadProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailThreadProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var AttachmentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentProvider;

    /** @var EmailAttachmentTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAttachmentTransformer;

    /** @var EmailAttachmentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAttachmentProvider;

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
    public function testGetThreadAttachments(array $threadEmails, int $transformationCalls)
    {
        $emailEntity = $this->createMock(Email::class);

        $em = $this->createMock(EntityManager::class);
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->willReturn($em);

        $this->emailThreadProvider->expects($this->once())
            ->method('getThreadEmails')
            ->with($em, $emailEntity)
            ->willReturn($threadEmails);

        $this->emailAttachmentTransformer->expects($this->exactly($transformationCalls))
            ->method('entityToModel');

        $result = $this->emailAttachmentProvider->getThreadAttachments($emailEntity);
        $this->assertCount($transformationCalls, $result);
    }

    public function threadEmailsProvider(): array
    {
        $threadEmails = [];
        $transformationCalls = 0;
        $threadEmailCount = 3;
        for ($i = 0; $i < $threadEmailCount; $i++) {
            $emailBody = $this->createMock(EmailBody::class);
            $emailBody->expects($this->once())
                ->method('getHasAttachments')
                ->willReturn(true);

            $attachments = $this->getAttachments($i + 1);
            $transformationCalls += $i + 1;

            $emailBody->expects($this->once())
                ->method('getAttachments')
                ->willReturn($attachments);

            $threadEmail = $this->createMock(Email::class);
            $threadEmail->expects($this->exactly($threadEmailCount))
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

    public function testGetScopeEntityAttachments()
    {
        $entity = $this->createMock(\stdClass::class);

        $attachments = [];
        for ($i = 0; $i < 3; $i++) {
            $attachments[] = $this->createMock(Attachment::class);
        }

        $this->attachmentProvider->expects($this->once())
            ->method('getEntityAttachments')
            ->with($entity)
            ->willReturn($attachments);

        $this->emailAttachmentTransformer->expects($this->exactly(count($attachments)))
            ->method('attachmentEntityToModel');

        $result = $this->emailAttachmentProvider->getScopeEntityAttachments($entity);
        $this->assertCount(count($attachments), $result);
    }
}
