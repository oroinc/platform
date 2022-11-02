<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Async\PurgeEmailAttachmentsByIdsMessageProcessor;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class PurgeEmailAttachmentsByIdsMessageProcessorTest extends WebTestCase
{
    protected function setUp(): void
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

        $rootJob = new Job();
        $rootJob->setName('Root Job');
        $rootJob->setStatus(Job::STATUS_NEW);
        $rootJob->setCreatedAt(new \DateTime());
        $subJob = new Job();
        $subJob->setName('Child Job');
        $subJob->setStatus(Job::STATUS_NEW);
        $subJob->setCreatedAt(new \DateTime());
        $subJob->setRootJob($rootJob);

        $jobEm = $this->getEntityManager(Job::class);
        $jobEm->persist($rootJob);
        $jobEm->persist($subJob);
        $jobEm->flush();

        $message = new Message();
        $message->setBody(['jobId' => $subJob->getId(), 'ids' => $ids]);

        $processor = $this->getContainer()->get('oro_email.async.purge_email_attachments_by_ids');

        $result = $processor->process($message, $this->createMock(SessionInterface::class));
        $this->assertEquals(MessageProcessorInterface::ACK, $result);

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

        $rootJob = new Job();
        $rootJob->setName('Root Job');
        $rootJob->setStatus(Job::STATUS_NEW);
        $rootJob->setCreatedAt(new \DateTime());
        $subJob = new Job();
        $subJob->setName('Child Job');
        $subJob->setStatus(Job::STATUS_NEW);
        $subJob->setCreatedAt(new \DateTime());
        $subJob->setRootJob($rootJob);

        $jobEm = $this->getEntityManager(Job::class);
        $jobEm->persist($rootJob);
        $jobEm->persist($subJob);
        $jobEm->flush();

        $message = new Message();
        $message->setBody(['jobId' => $subJob->getId(), 'ids' => $ids, 'size' => 3]);

        $processor = $this->getContainer()->get('oro_email.async.purge_email_attachments_by_ids');

        $result = $processor->process($message, $this->createMock(SessionInterface::class));
        $this->assertEquals(MessageProcessorInterface::ACK, $result);

        $allAttachments = $this->getEntityManager()->getRepository(EmailAttachment::class)->findAll();
        $this->assertCount(2, $allAttachments);
        $this->assertLessThan(3, $allAttachments[0]->getSize());
        $this->assertLessThan(3, $allAttachments[1]->getSize());
    }

    private function createEmailAttachmentWithContent(string $content): void
    {
        $attachmentContent = new EmailAttachmentContent();
        $attachmentContent->setContent($content);
        $attachmentContent->setContentTransferEncoding('encoding');

        $attachment = new EmailAttachment();
        $attachment->setContent($attachmentContent);
        $attachment->setFileName('filename');
        $attachment->setContentType('content-type');

        $em = $this->getEntityManager();
        $em->persist($attachment);
        $em->flush();
    }

    private function getEntityManager(string $entityClass = EmailAttachment::class): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($entityClass);
    }
}
