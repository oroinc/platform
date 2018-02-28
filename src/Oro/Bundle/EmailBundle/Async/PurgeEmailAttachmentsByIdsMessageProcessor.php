<?php
namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PurgeEmailAttachmentsByIdsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const LIMIT = 100;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RegistryInterface $doctrine
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     */
    public function __construct(RegistryInterface $doctrine, JobRunner $jobRunner, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (! isset($body['jobId'], $body['ids']) || ! is_array($body['ids'])) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

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
        return [Topics::PURGE_EMAIL_ATTACHMENTS_BY_IDS];
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
