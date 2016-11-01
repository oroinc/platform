<?php
namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsByIdsMessageProcessor;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolation
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
        $this->createEmailAttachmentWithContent();
        $this->createEmailAttachmentWithContent();
        $this->createEmailAttachmentWithContent();

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

    /**
     * @return EmailAttachment
     */
    private function createEmailAttachmentWithContent()
    {
        $attachment = new EmailAttachment();
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
