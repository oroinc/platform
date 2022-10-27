<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
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
    const LIMIT = 100;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    public function __construct(ManagerRegistry $doctrine, JobRunner $jobRunner)
    {
        $this->doctrine = $doctrine;
        $this->jobRunner = $jobRunner;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();

        $result = $this->jobRunner->runDelayed($body['jobId'], function () use ($body) {
            $em = $this->getEntityManager();
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [PurgeEmailAttachmentsByIdsTopic::getName()];
    }

    /**
     * @param int[] $ids
     *
     * @return BufferedIdentityQueryResultIterator
     */
    private function getEmailAttachments($ids)
    {
        $qb = $this->getEmailAttachmentRepository()
            ->createQueryBuilder('a')
            ->join('a.attachmentContent', 'attachment_content')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
        ;

        $em = $this->getEntityManager();

        $emailAttachments = (new BufferedIdentityQueryResultIterator($qb))
            ->setBufferSize(static::LIMIT)
            ->setPageCallback(
                function () use ($em) {
                    $em->flush();
                    $em->clear();
                }
            );

        return $emailAttachments;
    }

    /**
     * @return EntityRepository
     */
    private function getEmailAttachmentRepository()
    {
        return $this->doctrine->getRepository(EmailAttachment::class);
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->doctrine->getManagerForClass(EmailAttachment::class);
    }
}
