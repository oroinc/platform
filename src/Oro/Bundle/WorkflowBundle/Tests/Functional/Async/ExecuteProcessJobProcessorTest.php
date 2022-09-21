<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessDefinitions;

class ExecuteProcessJobProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadProcessDefinitions::class]);

        self::purgeMessageQueue();
    }

    public function testProcessJobWithEntityManagerCleanup(): void
    {
        $userManager = self::getContainer()->get('oro_user.manager');

        /** @var User $user */
        $user = $userManager->findUserByUsername('admin');
        $user->setFirstName('New First Name');
        $userManager->updateUser($user);

        $loggerTestHandler = self::getLoggerTestHandler();

        self::consume();

        self::assertFalse(
            $loggerTestHandler->hasErrorRecords(),
            sprintf(
                'Error log records were expected to be empty, got %s',
                var_export($loggerTestHandler->getRecords() ?? [], true)
            )
        );
    }
}
