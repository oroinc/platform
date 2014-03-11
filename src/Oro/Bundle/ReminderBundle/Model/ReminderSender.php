<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * Sends reminders using delegate send processors.
 */
class ReminderSender implements ReminderSenderInterface
{
    /**
     * @var SendProcessorInterface[]
     */
    protected $registry;

    /**
     * @param SendProcessorRegistry $registry
     */
    public function __construct(SendProcessorRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Handle reminder sending
     *
     * @param Reminder $reminder
     */
    public function send(Reminder $reminder)
    {
        $processor = $this->registry->getProcessor($reminder->getMethod());
        $processor->process($reminder);

        $state = $reminder->getState();

        if (Reminder::STATE_SENT == $state) {
            $reminder->setSentAt(new \DateTime());
        }
    }
}
