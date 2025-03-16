<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsByIdsTopic;
use Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsTopic;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Message queue processor that purges emails attachments.
 */
class PurgeEmailAttachmentsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const int LIMIT = 1000;

    public function __construct(
        private ManagerRegistry $doctrine,
        private MessageProducerInterface $producer,
        private JobRunner $jobRunner,
        private ConfigManager $configManager
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $size = $this->getSize($message->getBody());
        $emailAttachments = $this->getEmailAttachments($size);

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner) use ($emailAttachments, $size) {
                $ids = [];
                $count = 0;
                $lastIndex = count($emailAttachments) - 1;
                $newMessageData = ($size > 0) ? ['size' => $size] : [];

                foreach ($emailAttachments as $index => $attachment) {
                    $ids[] = $attachment['id'];

                    if (++$count == self::LIMIT || $lastIndex == $index) {
                        $newMessageData['ids'] = $ids;
                        $jobRunner->createDelayed(
                            \sprintf('%s:%s', 'oro.email.purge_email_attachments_by_ids', md5(implode(',', $ids))),
                            function (JobRunner $jobRunner, Job $child) use ($newMessageData) {
                                $newMessageData['jobId'] = $child->getId();
                                $this->producer->send(PurgeEmailAttachmentsByIdsTopic::getName(), $newMessageData);
                            }
                        );
                        $count = 0;
                        $ids = [];
                    }
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [PurgeEmailAttachmentsTopic::getName()];
    }

    private function getSize(array $payload): int
    {
        if ($payload['all']) {
            return 0;
        }

        $size = $payload['size'];
        if (null === $size) {
            $size = $this->configManager->get('oro_email.attachment_sync_max_size');
        }

        /** Convert Megabytes to Bytes */
        return (int)$size * (10 ** 6);
    }

    private function getEmailAttachments(int $size): BufferedIdentityQueryResultIterator
    {
        $qb = $this->createEmailAttachmentQb($size);
        $em = $this->doctrine->getManagerForClass(EmailAttachment::class);

        $emailAttachments = new BufferedIdentityQueryResultIterator($qb);
        $emailAttachments->setPageCallback(function () use ($em) {
            $em->flush();
            $em->clear();
        });

        return $emailAttachments;
    }

    private function createEmailAttachmentQb(int $size): QueryBuilder
    {
        $qb = $this->doctrine->getRepository(EmailAttachment::class)
            ->createQueryBuilder('a')
            ->select('a.id')
            ->join('a.attachmentContent', 'attachment_content');

        if ($size > 0) {
            $qb
                ->andWhere(
                    'CASE WHEN attachment_content.contentTransferEncoding = \'base64\''
                    . ' THEN (LENGTH(attachment_content.content) - LENGTH(attachment_content.content)/77) * 3 / 4 - 2'
                    . ' ELSE LENGTH(attachment_content.content) END >= :size'
                )
                ->setParameter('size', $size);
        }

        return $qb;
    }
}
