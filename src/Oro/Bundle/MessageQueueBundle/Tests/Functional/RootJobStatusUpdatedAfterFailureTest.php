<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadStuckRootJobData;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadStuckRootJobDependentData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Test\Async\DependentMessageProcessor;
use Oro\Component\MessageQueue\Test\Async\UniqueMessageProcessor;

class RootJobStatusUpdatedAfterFailureTest extends WebTestCase
{
    /** @var * MessageProducerInterface */
    private $messageProcessor;

    /** @var QueueConsumer */
    private $consumer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadStuckRootJobData::class,
            LoadStuckRootJobDependentData::class
        ]);

        $container = self::getContainer();
        $this->messageProcessor = $container->get('oro_message_queue.client.delegate_message_processor');
        $this->consumer = $container->get('oro_message_queue.consumption.queue_consumer');
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

        $this->consumer->bind('oro.default', $this->messageProcessor);
        $this->consumer->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime('+5 seconds'))
        ]));

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
        return $this->getContainer()->get('doctrine')->getManagerForClass(Job::class);
    }
}
