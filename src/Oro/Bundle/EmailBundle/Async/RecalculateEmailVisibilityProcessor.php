<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * MQ processor that splits the email users ids for that
 * the visibility of email users should be recalculated where the email address is used.
 */
class RecalculateEmailVisibilityProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const CHUNK_SIZE = 500;

    private ManagerRegistry $doctrine;
    private JobRunner $jobRunner;
    private MessageProducerInterface $producer;
    private LoggerInterface $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        EmailAddressVisibilityManager $emailAddressVisibilityManager,
        ActivityListChainProvider $activityListProvider,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * @deprecated
     */
    public function setJobRunner(JobRunner $jobRunner): void
    {
        $this->jobRunner = $jobRunner;
    }

    /**
     * @deprecated
     */
    public function setProducer(MessageProducerInterface $producer): void
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RECALCULATE_EMAIL_VISIBILITY];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());
        if (!isset($data['email'])) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        $emailAddress = $data['email'];

        $jobName = sprintf('%s:%s', Topics::RECALCULATE_EMAIL_VISIBILITY, md5($emailAddress));

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobName,
            function (JobRunner $jobRunner) use ($jobName, $emailAddress) {
                $chunkNumber = 1;
                $chunks = $this->getChunks($emailAddress);
                foreach ($chunks as $ids) {
                    $this->scheduleRecalculateVisibilities(
                        $jobRunner,
                        $jobName,
                        $ids,
                        $chunkNumber
                    );
                    $chunkNumber++;
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function scheduleRecalculateVisibilities(
        JobRunner $jobRunner,
        string $jobName,
        array $ids,
        int $chunkNumber
    ): void {
        $jobRunner->createDelayed(
            sprintf('%s:%d', $jobName, $chunkNumber),
            function (JobRunner $jobRunner, Job $childJob) use ($ids) {
                $this->producer->send(
                    Topics::RECALCULATE_EMAIL_VISIBILITY_CHUNK,
                    [
                        'jobId' => $childJob->getId(),
                        'ids'   => $ids
                    ]
                );
            }
        );
    }

    private function getChunks(string $emailAddress): iterable
    {
        /** @var EntityManagerInterface $em */
        $iterator = new BufferedQueryResultIterator(
            $this->doctrine->getRepository(Email::class)->getEmailUserIdsByEmailAddressQb($emailAddress)
        );
        $iterator->setBufferSize(self::CHUNK_SIZE);
        $rowNumber = 0;
        $resultIds = [];
        foreach ($iterator as $row) {
            $rowNumber++;
            $resultIds[] = $row['id'];
            if (($rowNumber % self::CHUNK_SIZE) === 0) {
                yield $resultIds;
                $resultIds = [];
            }
        }

        if (count($resultIds)) {
            yield $resultIds;
        }
    }
}
