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
     *
     * @param Reminder $reminder
     */
    public function push(Reminder $reminder);

    /**
     * Process reminder send
     */
    public function process();

    /**
     * Gets unique name of processor
     *
     * @param string
     */
    public function getName();

    /**
     * Gets label of processor
     *
     * @param string
     */
    public function getLabel();
}
