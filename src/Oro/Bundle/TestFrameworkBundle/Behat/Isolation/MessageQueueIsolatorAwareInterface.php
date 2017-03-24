<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

interface MessageQueueIsolatorAwareInterface
{
    /**
     * @param MessageQueueIsolatorInterface $messageQueueIsolator
     */
    public function setMessageQueueIsolator(MessageQueueIsolatorInterface $messageQueueIsolator);
}
