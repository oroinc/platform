<?php

namespace Oro\Bundle\CronBundle\Async;

use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * This processor is responsible for executing passed command with arguments
 * inside provided delayed job.
 */
class CommandRunnerProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var CommandRunnerInterface */
    private $commandRunner;

    /** @var LoggerInterface */
    private $logger;

    /** @var JobRunner */
    private $jobRunner;

    /**
     * @param CommandRunnerInterface $commandRunner
     * @param JobRunner              $jobRunner
     * @param LoggerInterface        $logger
     */
    public function __construct(
        CommandRunnerInterface $commandRunner,
        JobRunner $jobRunner,
        LoggerInterface $logger
    ) {
        $this->commandRunner = $commandRunner;
        $this->jobRunner     = $jobRunner;
        $this->logger        = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (!isset($body['command'])) {
            $this->logger->critical('Got invalid message: empty command');

            return self::REJECT;
        }
        if (!isset($body['jobId'])) {
            $this->logger->critical('Got invalid message: empty jobId');

            return self::REJECT;
        }
        $commandArguments = [];
        if (isset($body['arguments'])) {
            $commandArguments = $body['arguments'];
        }
        if (!is_array($commandArguments)) {
            $this->logger->critical('Got invalid message: "arguments" must be of type array');

            return self::REJECT;
        }

        $result = $this->runDelayedJob($body, $body['command'], $commandArguments);

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param array  $body
     * @param string $commandName
     * @param array  $commandArguments
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function runDelayedJob(array $body, $commandName, array $commandArguments)
    {
        $result = $this->jobRunner->runDelayed(
            $body['jobId'],
            function () use ($commandName, $commandArguments) {
                $output = $this->commandRunner->run($commandName, $commandArguments);
                $this->logger->info(
                    sprintf(
                        'Ran command %s. Got output %s',
                        $commandName,
                        $output
                    ),
                    [
                        'command'   => $commandName,
                        'arguments' => $commandArguments,
                        'output'    => $output
                    ]
                );

                return true;
            }
        );

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RUN_COMMAND_DELAYED];
    }
}
