<?php

namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Job\Topics;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Send calculate root job status message to fix jobs that stuck in Running or New statuses
 */
class RecalculateRootJobStatuses extends AbstractFixture implements ContainerAwareInterface, VersionedFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $producer = $this->container->get('oro_message_queue.client.message_producer');

        /** @var EntityRepository $repository */
        $repository = $manager->getRepository(Job::class);
        $qb = $repository->createQueryBuilder('j');
        $qb->where($qb->expr()->andX(
            $qb->expr()->isNull('j.rootJob'),
            $qb->expr()->in('j.status', ':statuses')
        ));
        $qb->setParameter('statuses', [Job::STATUS_NEW, Job::STATUS_RUNNING]);

        /** @var Job[] $products */
        $jobs = new BufferedQueryResultIterator($qb->getQuery());
        foreach ($jobs as $job) {
            $producer->send(Topics::CALCULATE_ROOT_JOB_STATUS, new Message([
                'jobId' => $job->getId(),
                'calculateProgress' => true,
            ], MessagePriority::HIGH));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.1';
    }
}
