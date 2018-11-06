<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Stub\DependentMessageProcessorStub;
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
        $jobName = DependentMessageProcessorStub::TEST_JOB_NAME;

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

        $dbal->executeUpdate(
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
                    'oro.message_queue.client.topic_name' => 'oro.message_queue.test_topic',
                    'oro.message_queue.client.processor_name' =>
                        'oro_message_queue.async.dependent_message_processor.stub',
                    'oro.message_queue.client.queue_name' => 'oro.default',
                ],
                'redelivered' => false,
                'queue' => 'oro.default',
                'priority' => 2,
            ],
            [
                'body' => Type::JSON_ARRAY,
                'headers' => Type::JSON_ARRAY,
                'properties' => Type::JSON_ARRAY,
                'redelivered' => Type::BOOLEAN,
                'queue' => Type::TEXT,
                'priority' => Type::SMALLINT,
            ]
        );
    }

    /**
     * @return DbalConnection
     */
    private function createConnection()
    {
        $dbal = $this->container->get('doctrine.dbal.default_connection');

        return new DbalConnection($dbal, 'oro_message_queue');
    }
}
