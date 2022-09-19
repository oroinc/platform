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

        self::consume();
        self::assertFalse(self::getLoggerTestHandler()->hasErrorRecords());
    }
}
