<?php

namespace Oro\Bundle\ReminderBundle\Model\Email;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\SendProcessorInterface;

class WebSocketSendProcessor implements SendProcessorInterface
{
    const NAME = 'web_socket';

    /**
     * Send reminder using WebSocket
     *
     * @param Reminder $reminder
     */
    public function process(Reminder $reminder)
    {
        // TODO: Implement process() method.
    }

    /**
     * If reminder should be sent using WebSocket
     *
     * @param Reminder $reminder
     */
    public function supports(Reminder $reminder)
    {
        // TODO: Implement supports() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
