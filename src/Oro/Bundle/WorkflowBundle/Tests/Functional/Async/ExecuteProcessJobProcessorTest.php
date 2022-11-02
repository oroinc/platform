<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Async;

use Oro\Bundle\EntityBundle\Event\OroEventManager;
use Oro\Bundle\EntityBundle\ORM\Event\PreClearEventArgs;
use Oro\Bundle\EntityBundle\ORM\Events;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Async\Topic\ExecuteProcessJobTopic;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessDefinitions;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class ExecuteProcessJobProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadProcessDefinitions::class]);

        // Enables optional listener that is disabled in functional tests.
        self::getContainer()
            ->get('oro_workflow.listener.event_trigger_collector')
            ->setEnabled(true);
    }

    public function testProcessJobWithEntityManagerCleanup(): void
    {
        $userManager = self::getContainer()->get('oro_user.manager');
        /** @var OroEventManager $eventManager */
        $eventManager = self::getContainer()->get('doctrine')->getConnection()->getEventManager();
        $isCleared = false;
        $preClearListener = static function (PreClearEventArgs $event) use (&$isCleared) {
            $isCleared = true;
        };
        $eventManager->addEventListener(Events::preClear, $preClearListener);

        try {
            /** @var User $user */
            $user = $userManager->findUserByUsername('admin');
            $user->setFirstName('New First Name');
            $userManager->updateUser($user);

            $sentMessage = self::getSentMessage(ExecuteProcessJobTopic::getName(), false);
            self::assertFalse($isCleared);

            self::consume();

            self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
            self::assertProcessedMessageProcessor('oro_workflow.async.execute_process_job', $sentMessage);

            self::assertTrue($isCleared, 'Trigger "entity_manager_cleanup" was expected to be processed');
            self::assertFalse(self::getLoggerTestHandler()->hasErrorRecords());
        } finally {
            $eventManager->removeEventListener(Events::preClear, $preClearListener);
        }
    }

    public function testProcessJobWithoutEntityManagerCleanup(): void
    {
        $userManager = self::getContainer()->get('oro_user.manager');

        /** @var User $user */
        $user = $userManager->findUserByUsername('admin');
        $user->setLastName('New Last Name');
        $userManager->updateUser($user);

        self::assertMessagesEmpty(ExecuteProcessJobTopic::getName());
    }
}
