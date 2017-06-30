<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\Process\Process;

interface MessageQueueIsolatorInterface extends IsolatorInterface
{
    /**
     * @param int $timeLimit Limit queue processing, seconds
     */
    public function waitWhileProcessingMessages($timeLimit = 600);

    /**
     * @return Process|null
     */
    public function getProcess();
}
