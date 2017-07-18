<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

interface MessageQueueIsolatorInterface extends IsolatorInterface
{
    const TIMEOUT = 600;

    /**
     * @param int $timeLimit Limit queue processing, seconds
     * @return void
     */
    public function waitWhileProcessingMessages($timeLimit = self::TIMEOUT);

    /**
     * @return void
     */
    public function stopMessageQueue();

    /**
     * @return void
     */
    public function startMessageQueue();
}
