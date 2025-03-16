<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsByIdsTopic;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Message queue processor that purges emails attachments.
 */
class PurgeEmailAttachmentsByIdsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const int LIMIT = 100;

    public function __construct(
        private ManagerRegistry $doctrine,
        private JobRunner $jobRunner
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        $result = $this->jobRunner->runDelayed($body['jobId'], function () use ($body) {
            $em = $this->doctrine->getManagerForClass(EmailAttachment::class);
            $emailAttachments = $this->getEmailAttachments($body['ids']);

            foreach ($emailAttachments as $attachment) {
                if (isset($body['size']) && ($attachment->getSize() < $body['size'])) {
                    continue;
                }

                $em->remove($attachment);
            }
            $em->flush();

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [PurgeEmailAttachmentsByIdsTopic::getName()];
    }

    private function getEmailAttachments(array $ids): BufferedIdentityQueryResultIterator
    {
        $qb = $this->doctrine->getRepository(EmailAttachment::class)
            ->createQueryBuilder('a')
            ->join('a.attachmentContent', 'attachment_content')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids);

        $em = $this->doctrine->getManagerForClass(EmailAttachment::class);

        $emailAttachments = new BufferedIdentityQueryResultIterator($qb);
        $emailAttachments->setBufferSize(static::LIMIT);
        $emailAttachments->setPageCallback(function () use ($em) {
            $em->flush();
            $em->clear();
        });

        return $emailAttachments;
    }
}
