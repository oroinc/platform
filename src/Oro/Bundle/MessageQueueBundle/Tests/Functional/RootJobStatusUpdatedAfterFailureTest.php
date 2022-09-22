<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadStuckRootJobData;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadStuckRootJobDependentData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Test\Async\DependentMessageProcessor;
use Oro\Component\MessageQueue\Test\Async\UniqueMessageProcessor;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RootJobStatusUpdatedAfterFailureTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
        $this->clearMessages();

        self::purgeMessageQueue();

        $this->loadFixtures([
            LoadStuckRootJobData::class,
            LoadStuckRootJobDependentData::class
        ]);
    }

    public function testMessageProcessionUpdatesRootJobAfterException(): void
    {
        $uniqueJobName = UniqueMessageProcessor::TEST_JOB_NAME;
        $dependentJobName = DependentMessageProcessor::TEST_JOB_NAME;

        $stuckUniqueRootJob = $this->getEntityManager()->getRepository(Job::class)
            ->findOneBy(['name' => $uniqueJobName, 'jobProgress' => 0]);
        self::assertNotEmpty($stuckUniqueRootJob);
        $stuckRootJobDependent = $this->getEntityManager()->getRepository(Job::class)
            ->findOneBy(['name' => $dependentJobName, 'jobProgress' => 0]);
        self::assertNotEmpty($stuckRootJobDependent);

        // oro.message_queue.unique_test_topic + oro.message_queue.job.root_job_stopped + oro.message_queue.test_topic.
        self::consume(3);

        $stuckUniqueRootJob = $this->getEntityManager()->getRepository(Job::class)
            ->findOneBy(['name' => $uniqueJobName, 'jobProgress' => 0]);
        self::assertEmpty($stuckUniqueRootJob);
        $stuckRootJobDependent = $this->getEntityManager()->getRepository(Job::class)
            ->findOneBy(['name' => $dependentJobName, 'jobProgress' => 0]);
        self::assertEmpty($stuckRootJobDependent);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataFixturesExecutorEntityManager()
    {
        return $this->getEntityManager();
    }

    private function getEntityManager(): EntityManager
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Job::class);
    }

    private function clearMessages(): void
    {
        $connection = self::getContainer()->get(
            'oro_message_queue.transport.dbal.connection',
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );

        if ($connection instanceof DbalConnection) {
            $connection->getDBALConnection()->executeQuery('DELETE FROM ' . $connection->getTableName());
        }
    }
}
