<?php

namespace Oro\Bundle\ReminderBundle\Tests\Behat\Context;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

/**
 * Behat context for manual reminders sending.
 */
class ReminderContext extends OroFeatureContext
{
    /**
     * @Given /^(?:|I )send all reminders notifications$/
     */
    public function iSendAllRemindersNotifications()
    {
        $this->sendReminders($this->getAllReminders());
    }

    private function getAllReminders(): array
    {
        return $this->getAppContainer()
            ->get('doctrine')
            ->getRepository(Reminder::class)
            ->findBy(['state' => Reminder::STATE_NOT_SENT]);
    }

    private function sendReminders(array $reminders): void
    {
        self::assertNotEmpty($reminders, 'No reminders to send.');

        $sender = $this->getAppContainer()->get('oro_reminder.behat.sender');

        foreach ($reminders as $reminder) {
            $sender->push($reminder);
        }

        $sender->send();
    }
}
