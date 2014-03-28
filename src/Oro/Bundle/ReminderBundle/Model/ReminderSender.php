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
     * Push reminder to processor
     *
     * @param Reminder $reminder
     */
    public function push(Reminder $reminder)
    {
        $processor = $this->registry->getProcessor($reminder->getMethod());
        $processor->push($reminder);
    }

    /**
     * Handle all reminders sending
     */
    public function send()
    {
        foreach ($this->registry->getProcessors() as $processor) {
            $processor->process();
        }
    }
}
