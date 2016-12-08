<?php

namespace Oro\Bundle\CronBundle\Async;

use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class CommandRunner implements CommandRunnerInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @param MessageProducerInterface $producer
     */
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
            Topics::RUN_COMMAND,
            [
                'command' => $commandName,
                'arguments' => $commandArguments
            ]
        );
    }
}
