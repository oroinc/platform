<?php

namespace Oro\Bundle\CronBundle\Async;

use Oro\Bundle\CronBundle\Async\Topic\RunCommandTopic;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends a {@see RunCommandTopic} message to message queue.
 */
class CommandRunner implements CommandRunnerInterface
{
    private MessageProducerInterface $producer;

    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param string $commandName
     * @param array $commandArguments
     */
    public function run($commandName, $commandArguments = [])
    {
        $this->producer->send(
            RunCommandTopic::getName(),
            [
                'command' => $commandName,
                'arguments' => $commandArguments
            ]
        );
    }
}
