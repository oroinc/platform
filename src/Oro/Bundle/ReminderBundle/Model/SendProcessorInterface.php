<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * Class that is responsible for reminder send processing
 */
interface SendProcessorInterface
{
    /**
     * Process reminder send
     *
     * @param Reminder $reminder
     */
    public function process(Reminder $reminder);

    /**
     * Checks if reminder sending should be processed
     *
     * @param Reminder $reminder
     */
    public function supports(Reminder $reminder);
}
