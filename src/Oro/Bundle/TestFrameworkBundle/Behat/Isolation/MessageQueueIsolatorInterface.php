<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

interface MessageQueueIsolatorInterface extends IsolatorInterface
{
    /**
     * @param int $timeLimit Time in seconds
     * @return void
     * @throws RuntimeException If massages not processed during time limit
     */
    public function waitWhileProcessingMessages($timeLimit = 60);

    /**
     * @return Process|null
     */
    public function getProcess();
}
