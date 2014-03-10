<?php

namespace Oro\Bundle\ReminderBundle\Model\WebSocket;

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
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.reminder.processor.web_socket.label';
    }
}
