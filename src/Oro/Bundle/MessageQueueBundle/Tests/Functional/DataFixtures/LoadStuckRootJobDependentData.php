<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Test\Async\DependentMessageProcessor;
use Oro\Component\MessageQueue\Test\Async\Topic\DependentMessageTestTopic;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadStuckRootJobDependentData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $jobName = DependentMessageProcessor::TEST_JOB_NAME;

        $rootJob = new Job();
        $rootJob->setOwnerId('oro.5b9252b531a7a6.89501276');
        $rootJob->setName($jobName);
        $rootJob->setStatus('oro.message_queue_job.status.new');
        $rootJob->setInterrupted(false);
        $rootJob->setUnique(true);
        $rootJob->setCreatedAt(new \DateTime());
        $rootJob->setStartedAt(new \DateTime());
        $rootJob->setLastActiveAt(new \DateTime());
        $rootJob->setJobProgress(0);
        $manager->persist($rootJob);

        $childJob = new Job();
        $childJob->setOwnerId(null);
        $childJob->setRootJob($rootJob);
        $childJob->setName($jobName);
        $childJob->setStatus('oro.message_queue_job.status.success');
        $childJob->setInterrupted(false);
        $childJob->setUnique(false);
        $childJob->setCreatedAt(new \DateTime());
        $childJob->setStartedAt(new \DateTime());
        $childJob->setStoppedAt(new \DateTime());
        $childJob->setJobProgress(1);
        $manager->persist($childJob);

        $rootJob->addChildJob($childJob);

        $manager->flush();

        $connection = $this->createConnection();
        $dbal = $connection->getDBALConnection();

        $sql = sprintf(
            'INSERT INTO %s (body, headers, properties, redelivered, queue, priority) VALUES '.
            '(:body, :headers, :properties, :redelivered, :queue, :priority)',
            $connection->getTableName()
        );

        $dbal->executeStatement(
            $sql,
            [
                'body' => [
                ],
                'headers' => [
                    'content_type' => 'application/json',
                    'message_id' => 'oro.5b9252b531a7a6.89501276',
                    'timestamp' => 1536316085,
                ],
                'properties' => [
                    'oro.message_queue.client.topic_name' => DependentMessageTestTopic::getName(),
                    'oro.message_queue.client.queue_name' => 'oro.default',
                ],
                'redelivered' => false,
                'queue' => 'oro.default',
                'priority' => 2,
            ],
            [
                'body' => Types::JSON_ARRAY,
                'headers' => Types::JSON_ARRAY,
                'properties' => Types::JSON_ARRAY,
                'redelivered' => Types::BOOLEAN,
                'queue' => Types::TEXT,
                'priority' => Types::SMALLINT,
            ]
        );
    }

    /**
     * @return DbalConnection
     */
    private function createConnection()
    {
        $dbal = $this->container->get('doctrine.dbal.message_queue_connection');

        return new DbalConnection($dbal, 'oro_message_queue');
    }
}
