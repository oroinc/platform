<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailVisibilitiesForOrganizationChunkTopic;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailVisibilitiesForOrganizationTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Updates visibilities for emails for a specific organization.
 */
class UpdateEmailVisibilitiesForOrganizationProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface
{
    private const CHUNK_SIZE = 10000;

    private ManagerRegistry $doctrine;
    private MessageProducerInterface $producer;
    private JobRunner $jobRunner;

    public function __construct(
        ManagerRegistry $doctrine,
        MessageProducerInterface $producer,
        JobRunner $jobRunner
    ) {
        $this->doctrine = $doctrine;
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [UpdateEmailVisibilitiesForOrganizationTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();

        $organizationId = $data['organizationId'];

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner, Job $job) use ($organizationId) {
                $chunkNumber = 1;
                $chunks = $this->getChunks($organizationId);
                $jobName = $job->getName();
                foreach ($chunks as [$firstEmailId, $lastEmailId]) {
                    $this->scheduleSettingVisibilities(
                        $jobRunner,
                        $jobName,
                        $organizationId,
                        $chunkNumber,
                        $firstEmailId,
                        $lastEmailId
                    );
                    $chunkNumber++;
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function scheduleSettingVisibilities(
        JobRunner $jobRunner,
        string $jobName,
        int $organizationId,
        int $chunkNumber,
        int $firstEmailId,
        ?int $lastEmailId
    ): void {
        $jobRunner->createDelayed(
            sprintf('%s:%d', $jobName, $chunkNumber),
            function (JobRunner $jobRunner, Job $childJob) use ($organizationId, $firstEmailId, $lastEmailId) {
                $chunkMessageBody = [
                    'jobId'          => $childJob->getId(),
                    'organizationId' => $organizationId,
                    'firstEmailId'   => $firstEmailId
                ];
                if (null !== $lastEmailId) {
                    $chunkMessageBody['lastEmailId'] = $lastEmailId;
                }
                $this->producer->send(
                    UpdateEmailVisibilitiesForOrganizationChunkTopic::getName(),
                    $chunkMessageBody
                );
            }
        );
    }

    private function getChunks(int $organizationId): iterable
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Email::class);
        $qb = $em->createQueryBuilder()
            ->from(Email::class, 'e')
            ->select('e.id')
            ->join('e.emailUsers', 'eu')
            ->where('eu.organization = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->orderBy('e.id');
        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setBufferSize(self::CHUNK_SIZE);
        $rowCount = $iterator->count();
        $rowNumber = 0;
        $firstEmailId = null;
        foreach ($iterator as $row) {
            $rowNumber++;
            $emailId = $row['id'];
            if (null === $firstEmailId) {
                $firstEmailId = $emailId;
            }
            if (($rowNumber % self::CHUNK_SIZE) === 0) {
                yield [$firstEmailId, $rowNumber !== $rowCount ? $emailId : null];
                $firstEmailId = null;
            }
        }
        if (null !== $firstEmailId) {
            yield [$firstEmailId, null];
        }
    }
}
