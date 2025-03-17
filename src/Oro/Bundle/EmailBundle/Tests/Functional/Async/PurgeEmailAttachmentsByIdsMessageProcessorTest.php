<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Async;

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
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
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

        $em = self::getContainer()->get('doctrine')->getManagerForClass(EmailAttachment::class);
        $em->persist($attachment);
        $em->flush();
    }

    public function testCouldBeConstructedByContainer(): void
    {
        $service = self::getContainer()->get('oro_email.async.purge_email_attachments_by_ids');

        self::assertInstanceOf(PurgeEmailAttachmentsByIdsMessageProcessor::class, $service);
    }

    public function testShouldPurgeAttachmentsByIds(): void
    {
        $this->createEmailAttachmentWithContent('a');
        $this->createEmailAttachmentWithContent('aa');
        $this->createEmailAttachmentWithContent('aaa');

        $allAttachments = self::getContainer()->get('doctrine')->getRepository(EmailAttachment::class)->findAll();
        self::assertCount(3, $allAttachments);

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

        $jobEm = self::getContainer()->get('doctrine')->getManagerForClass(Job::class);
        $jobEm->persist($rootJob);
        $jobEm->persist($subJob);
        $jobEm->flush();

        $message = new Message();
        $message->setBody(['jobId' => $subJob->getId(), 'ids' => $ids]);

        $processor = $this->getContainer()->get('oro_email.async.purge_email_attachments_by_ids');

        $result = $processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $result);

        $allAttachments = self::getContainer()->get('doctrine')->getRepository(EmailAttachment::class)->findAll();
        self::assertCount(1, $allAttachments);
        self::assertEquals($expectedId, $allAttachments[0]->getId());
    }

    public function testShouldPurgeAttachmentsByIdsAndSize(): void
    {
        $this->createEmailAttachmentWithContent('a');
        $this->createEmailAttachmentWithContent('aa');
        $this->createEmailAttachmentWithContent('aaa');

        $allAttachments = self::getContainer()->get('doctrine')->getRepository(EmailAttachment::class)->findAll();
        self::assertCount(3, $allAttachments);

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

        $jobEm = self::getContainer()->get('doctrine')->getManagerForClass(Job::class);
        $jobEm->persist($rootJob);
        $jobEm->persist($subJob);
        $jobEm->flush();

        $message = new Message();
        $message->setBody(['jobId' => $subJob->getId(), 'ids' => $ids, 'size' => 3]);

        $processor = $this->getContainer()->get('oro_email.async.purge_email_attachments_by_ids');

        $result = $processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $result);

        $allAttachments = self::getContainer()->get('doctrine')->getRepository(EmailAttachment::class)->findAll();
        self::assertCount(2, $allAttachments);
        self::assertLessThan(3, $allAttachments[0]->getSize());
        self::assertLessThan(3, $allAttachments[1]->getSize());
    }
}
