<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsByIdsMessageProcessor;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class PurgeEmailAttachmentsByIdsMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_email.async.purge_email_attachments_by_ids');

        $this->assertInstanceOf(PurgeEmailAttachmentsByIdsMessageProcessor::class, $service);
    }

    public function testShouldPurgeAttachmentsByIds()
    {
        $this->createEmailAttachmentWithContent('a');
        $this->createEmailAttachmentWithContent('aa');
        $this->createEmailAttachmentWithContent('aaa');

        $allAttachments = $this->getEntityManager()->getRepository(EmailAttachment::class)->findAll();
        $this->assertCount(3, $allAttachments);

        $ids = array_map(function ($attachment) {
            return $attachment->getId();
        }, $allAttachments);
        $expectedId = array_pop($ids);

        $message = new NullMessage();
        $message->setBody(json_encode(['ids' => $ids]));

        $processor = $this->getContainer()->get('oro_email.async.purge_email_attachments_by_ids');

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(PurgeEmailAttachmentsByIdsMessageProcessor::ACK, $result);

        $allAttachments = $this->getEntityManager()->getRepository(EmailAttachment::class)->findAll();
        $this->assertCount(1, $allAttachments);
        $this->assertEquals($expectedId, $allAttachments[0]->getId());
    }

    public function testShouldPurgeAttachmentsByIdsAndSize()
    {
        $this->createEmailAttachmentWithContent('a');
        $this->createEmailAttachmentWithContent('aa');
        $this->createEmailAttachmentWithContent('aaa');

        $allAttachments = $this->getEntityManager()->getRepository(EmailAttachment::class)->findAll();
        $this->assertCount(3, $allAttachments);

        $ids = array_map(function ($attachment) {
            return $attachment->getId();
        }, $allAttachments);

        $message = new NullMessage();
        $message->setBody(json_encode(['ids' => $ids, 'size' => 3]));

        $processor = $this->getContainer()->get('oro_email.async.purge_email_attachments_by_ids');

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(PurgeEmailAttachmentsByIdsMessageProcessor::ACK, $result);

        $allAttachments = $this->getEntityManager()->getRepository(EmailAttachment::class)->findAll();
        $this->assertCount(2, $allAttachments);
        $this->assertLessThan(3, $allAttachments[0]->getSize());
        $this->assertLessThan(3, $allAttachments[1]->getSize());
    }

    /**
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
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }
}
