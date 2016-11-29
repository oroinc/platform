<?php

namespace Oro\Bundle\LoggerBundle\Mailer;

use Swift_Events_SendEvent;

class NoRecipientPlugin implements \Swift_Events_SendListener
{
    /**
     * {@inheritDoc}
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt)
    {
        if (!$evt->getMessage()->getTo()) {
            $evt->cancelBubble();
        }
    }

    /**
     * Not used.
     * {@inheritDoc}
     */
    public function sendPerformed(Swift_Events_SendEvent $evt)
    {
    }
}
