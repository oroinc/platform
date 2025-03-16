<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

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

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
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

        $em = self::getContainer()->get('doctrine')->getManagerForClass(EmailAttachment::class);
        $em->persist($attachment);
        $em->flush();

        return $attachment;
    }

    public function testCouldBeConstructedByContainer(): void
    {
        $service = self::getContainer()->get('oro_email.async.purge_email_attachments');

        self::assertInstanceOf(PurgeEmailAttachmentsMessageProcessor::class, $service);
    }

    public function testShouldPurgeAllEmailAttachments(): void
    {
        $this->createEmailAttachmentWithContent('a');
        $this->createEmailAttachmentWithContent('aa');
        $this->createEmailAttachmentWithContent('aaa');

        $allAttachments = self::getContainer()->get('doctrine')->getRepository(EmailAttachment::class)->findAll();
        self::assertCount(3, $allAttachments);

        $this->ajaxRequest('POST', $this->getUrl('oro_email_purge_emails_attachments'));

        self::assertEquals(200, $this->client->getResponse()->getStatusCode());

        self::consume();

        $processedMessages = self::getProcessedMessagesByTopic(PurgeEmailAttachmentsTopic::getName());

        self::assertNotEmpty($processedMessages);

        foreach ($processedMessages as $processedMessage) {
            self::assertEquals(MessageProcessorInterface::ACK, $processedMessage['context']->getStatus());
        }
    }
}
