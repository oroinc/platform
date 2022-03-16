<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Email;
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
 * Updates visibilities for emails in a specific organization.
 */
class UpdateEmailVisibilitiesForOrganizationProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface
{
    private const CHUNK_SIZE = 10000;

    private ManagerRegistry $doctrine;
    private MessageProducerInterface $producer;
    private JobRunner $jobRunner;
    private LoggerInterface $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        MessageProducerInterface $producer,
        JobRunner $jobRunner,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::UPDATE_EMAIL_VISIBILITIES_FOR_ORGANIZATION];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        if (!isset($body['organizationId'])) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        $organizationId = $body['organizationId'];
        $jobName = sprintf('oro:email:update-visibilities:emails:%d', $organizationId);
        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobName,
            function (JobRunner $jobRunner) use ($jobName, $organizationId) {
                $chunkNumber = 1;
                $chunks = $this->getChunks($organizationId);
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
                $this->producer->send(Topics::UPDATE_EMAIL_VISIBILITIES_FOR_ORGANIZATION_CHUNK, $chunkMessageBody);
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
