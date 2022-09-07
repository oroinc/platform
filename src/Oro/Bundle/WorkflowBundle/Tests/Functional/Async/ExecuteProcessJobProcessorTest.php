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
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExecuteProcessJobProcessorTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->clearMessages();
        $this->loadFixtures([
            LoadProcessDefinitions::class,
        ]);
    }

    public function testProcessJobWithEntityManagerCleanup(): void
    {
        /** @var UserManager $userManager */
        $userManager = self::getContainer()->get('oro_user.manager');

        /** @var User $user */
        $user = $userManager->findUserByUsername('admin');
        $user->setFirstName('New First Name');
        $userManager->updateUser($user);

        $consumer = self::getContainer()->get('oro_message_queue.consumption.queue_consumer');
        $logger = new TestLogger();

        $consumer->bind('oro.default');
        $consumer->consume(new ChainExtension([
            new LimitConsumptionTimeExtension(new \DateTime('+5 seconds')),
            new LoggerExtension($logger)
        ]));

        self::assertFalse(
            $logger->hasErrorRecords(),
            sprintf(
                'Error log records were expected to be empty, got %s',
                var_export($logger->getLogsByLevel('error'), true)
            )
        );
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
