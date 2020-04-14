<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Async;

use Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension\TestLogger;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessDefinitions;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;

class ExecuteProcessJobProcessorTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProcessDefinitions::class,
        ]);
    }

    public function testProcessJobWithEntityManagerCleanup()
    {
        /** @var UserManager $userManager */
        $userManager = $this->getContainer()->get('oro_user.manager');

        /** @var User $user */
        $user = $userManager->findUserByUsername('admin');
        $user->setFirstName('New First Name');
        $userManager->updateUser($user);

        $messageProcessor = $this->getContainer()->get('oro_message_queue.client.delegate_message_processor');
        $consumer = $this->getContainer()->get('oro_message_queue.consumption.queue_consumer');
        $logger = new TestLogger();

        $consumer->bind('oro.default', $messageProcessor);
        $consumer->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime('+5 seconds')),
            new LoggerExtension($logger)
        ]));

        $this->assertFalse($logger->hasErrorRecords());
    }
}
