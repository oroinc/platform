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

    public function __construct(SendProcessorRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Push reminder to processor
     */
    #[\Override]
    public function push(Reminder $reminder)
    {
        $processor = $this->registry->getProcessor($reminder->getMethod());
        $processor->push($reminder);
    }

    /**
     * Handle all reminders sending
     */
    #[\Override]
    public function send()
    {
        foreach ($this->registry->getProcessors() as $processor) {
            $processor->process();
        }
    }
}
