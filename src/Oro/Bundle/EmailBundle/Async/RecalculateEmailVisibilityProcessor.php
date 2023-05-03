<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityChunkTopic;
use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

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

    public function __construct(
        ManagerRegistry $doctrine,
        JobRunner $jobRunner,
        MessageProducerInterface $producer
    ) {
        $this->doctrine = $doctrine;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [RecalculateEmailVisibilityTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();
        $emailAddress = $data['email'];

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner, Job $job) use ($emailAddress) {
                $chunkNumber = 1;
                $chunks = $this->getChunks($emailAddress);
                $jobName = $job->getName();
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
                    RecalculateEmailVisibilityChunkTopic::getName(),
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
