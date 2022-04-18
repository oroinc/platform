<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Updates visibilities for emails and email addresses in all organizations.
 */
class UpdateVisibilitiesProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
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
        return [Topics::UPDATE_VISIBILITIES];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $jobName = 'oro:email:update-visibilities:email-addresses';
        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobName,
            function (JobRunner $jobRunner) use ($jobName) {
                $organizationIds = $this->getOrganizationIds();
                foreach ($organizationIds as $organizationId) {
                    $this->scheduleUpdatingVisibilities($jobRunner, $jobName, $organizationId);
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function scheduleUpdatingVisibilities(JobRunner $jobRunner, string $jobName, int $organizationId): void
    {
        $jobRunner->createDelayed(
            sprintf('%s:%d', $jobName, $organizationId),
            function (JobRunner $jobRunner, Job $childJob) use ($organizationId) {
                $this->producer->send(
                    Topics::UPDATE_VISIBILITIES_FOR_ORGANIZATION,
                    ['jobId' => $childJob->getId(), 'organizationId' => $organizationId]
                );
            }
        );
    }

    private function getOrganizationIds(): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Organization::class);
        $rows = $em->createQueryBuilder()
            ->from(Organization::class, 'org')
            ->select('org.id')
            ->orderBy('org.id')
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }
}
