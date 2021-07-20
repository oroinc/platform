<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * Class that is responsible for reminder send processing
 */
interface SendProcessorInterface
{
    /**
     * Push reminder to process queue
     */
    public function push(Reminder $reminder);

    /**
     * Process reminder send
     */
    public function process();

    /**
     * Gets label of processor
     *
     * @return string
     */
    public function getLabel();
}
