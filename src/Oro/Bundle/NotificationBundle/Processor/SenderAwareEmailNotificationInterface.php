<?php

namespace Oro\Bundle\NotificationBundle\Processor;

interface SenderAwareEmailNotificationInterface extends EmailNotificationInterface
{
    /**
     * @return string|null
     */
    public function getSenderEmail();

    /**
     * @return string|null
     */
    public function getSenderName();
}
