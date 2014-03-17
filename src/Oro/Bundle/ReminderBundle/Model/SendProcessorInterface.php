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
