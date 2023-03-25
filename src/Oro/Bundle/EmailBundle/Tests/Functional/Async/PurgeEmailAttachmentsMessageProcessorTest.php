<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsTopic;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

class PurgeEmailAttachmentsMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.purge_email_attachments');

        $this->assertInstanceOf(PurgeEmailAttachmentsMessageProcessor::class, $service);
    }

    public function testShouldPurgeAllEmailAttachments()
    {
        $this->createEmailAttachmentWithContent('a');
        $this->createEmailAttachmentWithContent('aa');
        $this->createEmailAttachmentWithContent('aaa');

        $allAttachments = $this->getEntityManager()->getRepository(EmailAttachment::class)->findAll();
        $this->assertCount(3, $allAttachments);

        $this->ajaxRequest('POST', $this->getUrl('oro_email_purge_emails_attachments'));

        self::assertEquals(200, $this->client->getResponse()->getStatusCode());

        self::consume();

        $processedMessages = self::getProcessedMessagesByTopic(PurgeEmailAttachmentsTopic::getName());

        self::assertNotEmpty($processedMessages);

        foreach ($processedMessages as $processedMessage) {
            $this->assertEquals(MessageProcessorInterface::ACK, $processedMessage['context']->getStatus());
        }
    }

    private function createEmailAttachmentWithContent(string $content): EmailAttachment
    {
        $attachmentContent = new EmailAttachmentContent();
        $attachmentContent->setContent($content);
        $attachmentContent->setContentTransferEncoding('encoding');

        $attachment = new EmailAttachment();
        $attachment->setContent($attachmentContent);
        $attachment->setFileName('filename');
        $attachment->setContentType('content-type');

        $this->getEntityManager()->persist($attachment);
        $this->getEntityManager()->flush();

        return $attachment;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(EmailAttachment::class);
    }
}
