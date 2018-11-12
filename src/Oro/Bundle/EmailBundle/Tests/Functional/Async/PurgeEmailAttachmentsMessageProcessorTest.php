<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class PurgeEmailAttachmentsMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient();
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
        $this->assertEquals(3, count($allAttachments));

        $message = new NullMessage();
        $message->setBody(json_encode(['size' => null, 'all' => true]));
        $message->setMessageId('SomeId');

        $processor = $this->getContainer()->get('oro_email.async.purge_email_attachments');

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PurgeEmailAttachmentsMessageProcessor::ACK, $result);
        $this->assertMessageSent(Topics::PURGE_EMAIL_ATTACHMENTS_BY_IDS, [
            'ids' => [
                $allAttachments[0]->getId(),
                $allAttachments[1]->getId(),
                $allAttachments[2]->getId(),
            ]
        ], true);
    }

    /**
     * @param string $content
     * @return EmailAttachment
     */
    private function createEmailAttachmentWithContent($content)
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

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(EmailAttachment::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
