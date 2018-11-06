<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadStuckRootJobData;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadStuckRootJobDependentData;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Stub\DependentMessageProcessorStub;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Stub\UniqueMessageProcessorStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;

class RootJobStatusUpdatedAfterFailureTest extends WebTestCase
{
    /** @var * MessageProducerInterface */
    protected $messageProcessor;

    /** @var QueueConsumer */
    protected $consumer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            LoadStuckRootJobData::class,
            LoadStuckRootJobDependentData::class
        ]);

        $container = self::getContainer();
        $this->messageProcessor = $container->get('oro_message_queue.client.delegate_message_processor');
        $this->consumer = $container->get('oro_test.consumption.queue_consumer');
    }

    public function testMessageProcessionUpdatesRootJobAfterException()
    {
        $uniqueJobName = UniqueMessageProcessorStub::TEST_JOB_NAME;
        $dependentJobName = DependentMessageProcessorStub::TEST_JOB_NAME;

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
     * @param $classManager
     * @return EntityManager
     */
    private function getEntityManager($classManager = Job::class)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($classManager);
    }
}
